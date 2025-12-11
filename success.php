<?php
require_once('../../config.php');
require_once('lib.php');

// Get the correct course ID from course module context
$id = optional_param('id', 0, PARAM_INT); // This is the course module ID

// Try to get from session if not in parameter
if (!$id && isset($_SESSION['quiz_course_module_id'])) {
    $id = $_SESSION['quiz_course_module_id'];
}

if ($id) {
    $cm = get_coursemodule_from_id('quizgenerator', $id, 0, false, MUST_EXIST);
    $course = get_course($cm->course);
    $course_id = $course->id;
    require_login($course, true, $cm);
} else {
    // Fallback - try session or parameter
    $course_id = optional_param('course_id', 0, PARAM_INT);
    if (!$course_id && isset($_SESSION['quiz_course_id'])) {
        $course_id = $_SESSION['quiz_course_id'];
    }
    if (!$course_id) {
        $course_id = 1; // Final fallback
    }
    $course = get_course($course_id);
    require_login($course);
}

// Validate course exists
if (!$course || !$course_id) {
    echo '<div class="alert alert-danger">';
    echo '<h4>Course Error</h4>';
    echo '<p>Invalid course or course not found.</p>';
    echo '<p>Debug: Course ID = ' . $course_id . ', Course Module ID = ' . $id . '</p>';
    echo '</div>';
    return;
}

// Get question category using the existing function
$categoryid = quizgenerator_get_question_category($course_id);
if (!$categoryid) {
    echo '<div class="alert alert-danger">';
    echo '<h4>Question Category Error</h4>';
    echo '<p>Could not find or create question category for this course.</p>';
    echo '<p>Debug: Course ID = ' . $course_id . ', Course = ' . $course->fullname . '</p>';
    echo '</div>';
    return;
}

// Update answers from form
if (isset($_POST['answers'])) {
    foreach ($_POST['answers'] as $questionKey => $answers) {
        foreach ($answers as $answerIndex => $answerData) {
            if (isset($_SESSION['generated_questions'][$questionKey]['answers'][$answerIndex])) {
                $_SESSION['generated_questions'][$questionKey]['answers'][$answerIndex]['text'] = $answerData['text'];
            }
        }
    }
}

// Handle correct answers - support both single and multiple answers
if (isset($_POST['correct_answer'])) {
    foreach ($_POST['correct_answer'] as $questionKey => $correctAnswers) {
        // Reset all answers to incorrect first
        if (isset($_SESSION['generated_questions'][$questionKey]['answers'])) {
            foreach ($_SESSION['generated_questions'][$questionKey]['answers'] as $answerIndex => &$answer) {
                $answer['fraction'] = 0.0;
            }

            // Handle multiple correct answers (checkbox) vs single (radio)
            if (is_array($correctAnswers)) {
                // Multiple answers from checkboxes
                foreach ($correctAnswers as $correctIndex) {
                    if (isset($_SESSION['generated_questions'][$questionKey]['answers'][$correctIndex])) {
                        $_SESSION['generated_questions'][$questionKey]['answers'][$correctIndex]['fraction'] = 1.0;
                    }
                }
            } else {
                // Single answer from radio button
                if (isset($_SESSION['generated_questions'][$questionKey]['answers'][$correctAnswers])) {
                    $_SESSION['generated_questions'][$questionKey]['answers'][$correctAnswers]['fraction'] = 1.0;
                }
            }
        }
    }
}

$selected = isset($_POST['selected_questions']) ? $_POST['selected_questions'] : [];
$generated = $_SESSION['generated_questions'] ?? [];
$saved_ids = [];

// If session is empty but we have POST data, try to reconstruct questions from POST
if (empty($generated) && !empty($_POST['answers'])) {
    $generated = [];
    foreach ($_POST['answers'] as $questionKey => $answers) {
        $question_name = isset($_POST['question_names'][$questionKey]) ? $_POST['question_names'][$questionKey] : "Question " . $questionKey;
        $question_text = isset($_POST['question_texts'][$questionKey]) ? $_POST['question_texts'][$questionKey] : "Question from POST data " . $questionKey;

        $generated[$questionKey] = [
            'name' => $question_name,
            'text' => $question_text,
            'answers' => [],
            'type' => 'multiplechoice',
            'is_multiple_answer' => false
        ];

        foreach ($answers as $answerIndex => $answerData) {
            $generated[$questionKey]['answers'][$answerIndex] = [
                'text' => $answerData['text'],
                'fraction' => 0.0 // Will be set below
            ];
        }
    }

    // Set correct answers
    if (isset($_POST['correct_answer'])) {
        foreach ($_POST['correct_answer'] as $questionKey => $correctAnswers) {
            if (is_array($correctAnswers)) {
                foreach ($correctAnswers as $correctIndex) {
                    if (isset($generated[$questionKey]['answers'][$correctIndex])) {
                        $generated[$questionKey]['answers'][$correctIndex]['fraction'] = 1.0;
                    }
                }
            } else {
                if (isset($generated[$questionKey]['answers'][$correctAnswers])) {
                    $generated[$questionKey]['answers'][$correctAnswers]['fraction'] = 1.0;
                }
            }
        }
    }
}

if (!empty($selected) && !empty($generated)) {
    foreach ($generated as $key => $qdata) {
        if (in_array($key, $selected)) {
            $qdataobj = new stdClass();
            $qdataobj->name = isset($qdata['name']) ? $qdata['name'] : "Generated Question {$key}";
            $qdataobj->text = isset($qdata['text']) ? $qdata['text'] : "Question text {$key}";
            $qdataobj->answers = isset($qdata['answers']) ? $qdata['answers'] : [];
            $qdataobj->is_multiple_answer = isset($qdata['is_multiple_answer']) ? $qdata['is_multiple_answer'] : false;

            $questionid = quizgenerator_create_question($categoryid, $qdataobj);
            if ($questionid) {
                $saved_ids[] = $questionid;
            }
        }
    }
    unset($_SESSION['generated_questions']);

    if (count($saved_ids) > 0) {
        echo '<div style="padding:20px; background:#d4edda; border:1px solid #c3e6cb; color:#155724; margin:20px;">';
        echo '<h3>Success!</h3>';
        echo '<p>' . count($saved_ids) . ' questions were successfully saved to the question bank!</p>';
        echo '<p>Course: ' . htmlspecialchars($course->fullname) . ' (ID: ' . $course_id . ')</p>';
        echo '<p>Question Category ID: ' . $categoryid . '</p>';
        echo '<p><a href="' . $CFG->wwwroot . '/question/edit.php?courseid=' . $course_id . '">View Question Bank</a></p>';
        echo '</div>';
    } else {
        echo '<div style="padding:20px; background:#f8d7da; border:1px solid #f5c6cb; color:#721c24; margin:20px;">';
        echo '<h3>Error</h3>';
        echo '<p>No questions were saved. Please try again.</p>';
        echo '<p>Debug: Selected questions count = ' . count($selected) . ', Generated questions count = ' . count($generated) . '</p>';
        echo '</div>';
    }
} else {
    echo '<div style="padding:20px; background:#fff3cd; border:1px solid #ffeaa7; color:#856404; margin:20px;">';
    echo '<h3>Warning</h3>';
    echo '<p>No questions were selected.</p>';
    echo '</div>';
}
