<?php
require_once('../../config.php');
require_once('lib.php');

// Get the correct course context
$id = required_param('id', PARAM_INT); // This is the course module ID
$cm = get_coursemodule_from_id('quizgenerator', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);

require_login($course, true, $cm);

// Get form data
$quizquery = isset($_POST['quizquery']) ? $_POST['quizquery'] : '';
$course_id = $course->id; // Use the actual course ID
$module_id = isset($_POST['module_id']) ? (int)$_POST['module_id'] : null;
$qtypes = isset($_POST['qtypes']) ? $_POST['qtypes'] : [];
$qnums = isset($_POST['qnums']) ? $_POST['qnums'] : [];

$selected_types = [];
$selected_nums = [];
$total_questions = 0;

foreach ($qtypes as $type) {
    if (isset($qnums[$type])) {
        $selected_types[] = $type;
        $selected_nums[] = $qnums[$type];
        $total_questions += (int)$qnums[$type];
    }
}

if (empty($selected_types)) {
    $question_type_str = 'Multiple Choice with One Answer';
    $number_of_question_str = '1';
    $total_questions = 1;
} else {
    $question_type_str = implode(', ', $selected_types);
    $number_of_question_str = implode(', ', $selected_nums);
}

// Validate module_id
if (!$module_id) {
    echo '<div class="alert alert-danger">';
    echo '<h4>Module Selection Required</h4>';
    echo '<p>Please select a module from the dropdown to generate questions.</p>';
    echo '<a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>';
    echo '</div>';
    return;
}

// Define variables for form  
$saveurl = new moodle_url('/mod/quizgenerator/success.php', array('id' => $id));

$api_data = array(
    'query' => $quizquery,
    'course_id' => $course_id,
    'module_id' => $module_id,
    'question_type' => $question_type_str,
    'number_of_question' => $number_of_question_str
);

// Call API to get questions
$api_response = quizgenerator_call_api($api_data);
$questionsdata = array();

if ($api_response && !isset($api_response['error'])) {
    // Get parsed questions from API response
    $questions = array();
    if (isset($api_response['parsed'])) {
        $questions = $api_response['parsed'];
    } elseif (isset($api_response['questions'])) {
        $questions = $api_response['questions'];
    }

    if (!empty($questions)) {
        $key = 1;
        foreach ($questions as $index => $question) {
            // Skip if we already have enough questions
            if ($key > $total_questions) {
                break;
            }

            // Skip invalid questions
            if (
                !isset($question['title']) ||
                empty(trim($question['title'])) ||
                $question['title'] === '```' ||
                strlen(trim($question['title'])) < 5
            ) {
                continue;
            }

            // Process questions based on choices
            if (isset($question['choices']) && is_array($question['choices'])) {
                // Multiple choice question
                $answers = array();
                $correct_answers = array();
                $choices_array = array();
                $is_multiple_answer = false;

                // Parse correct answers
                if (isset($question['answer'])) {
                    if (is_array($question['answer'])) {
                        $correct_answers = $question['answer'];
                        // If more than one correct answer, it's multiple answer question
                        $is_multiple_answer = count($question['answer']) > 1;
                    } else {
                        $correct_answers = array($question['answer']);
                    }
                }

                // Parse choices - handle pipe-separated format
                foreach ($question['choices'] as $choice) {
                    $choice = trim($choice);

                    // Check if choice contains pipe-separated values (like "a. option1|b. option2|c. option3")
                    if (strpos($choice, '|') !== false) {
                        $pipe_choices = explode('|', $choice);
                        foreach ($pipe_choices as $pipe_choice) {
                            $pipe_choice = trim($pipe_choice);
                            if (!empty($pipe_choice)) {
                                $choices_array[] = $pipe_choice;
                            }
                        }
                        // If we find pipe-separated choices, it's likely multiple answer
                        $is_multiple_answer = true;
                    } else {
                        if (!empty($choice)) {
                            $choices_array[] = $choice;
                        }
                    }
                }

                // Create answers array
                foreach ($choices_array as $choice) {
                    $is_correct = in_array($choice, $correct_answers);
                    $answers[] = array(
                        'text' => $choice,
                        'fraction' => $is_correct ? 1.0 : 0.0
                    );
                }

                $questionsdata[$key] = array(
                    'name' => $question['title'],
                    'text' => $question['title'],
                    'answers' => $answers,
                    'type' => 'multiplechoice',
                    'is_multiple_answer' => $is_multiple_answer
                );
            } else {
                // Essay question or fallback
                $questionsdata[$key] = array(
                    'name' => $question['title'],
                    'text' => $question['title'],
                    'answers' => null,
                    'type' => 'essay',
                    'is_multiple_answer' => false
                );
            }

            $key++;
        }
    }
} else {
    // Handle API errors
    $error_msg = 'Failed to generate questions from API.';
    if (isset($api_response['error'])) {
        $error_msg .= ' Error: ' . $api_response['error'];
    }
    echo $OUTPUT->notification($error_msg, 'notifyproblem');

    // Provide sample questions for testing
    $questionsdata = array();
    $questionsdata[1] = array(
        'name' => "Sample Question 1",
        'text' => "This is a sample question for testing (API call failed)",
        'answers' => (strpos($question_type_str, 'Multiple Choice') !== false) ? array(
            array('text' => 'Option A', 'fraction' => 1.0),
            array('text' => 'Option B', 'fraction' => 0.0),
            array('text' => 'Option C', 'fraction' => 0.0)
        ) : null,
        'type' => (strpos($question_type_str, 'Multiple Choice') !== false) ? 'multiplechoice' : 'essay'
    );
}

// Store questions in session
$_SESSION['generated_questions'] = $questionsdata;
$_SESSION['quiz_course_module_id'] = $id;
$_SESSION['quiz_course_id'] = $course_id;
?>

<script>
    console.log('=== QUIZ GENERATOR DEBUG ===');
    console.log('Target Questions:', <?= (int)$total_questions ?>);
    console.log('Questions Generated:', <?= count($questionsdata) ?>);
    <?php if (isset($questions) && !empty($questions)): ?>
        console.log('parsed:', <?= json_encode($questions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
    <?php endif; ?>
    console.log('=== END DEBUG ===');
</script>

<h3>Generated Questions Preview</h3>
<p><strong>Requested:</strong> <?= $total_questions ?> questions | <strong>Generated:</strong> <?= count($questionsdata) ?> questions | <strong>Type:</strong> <?= $question_type_str ?></p>
<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px;">
    <small><strong>Debug Info:</strong> Check browser console (F12) and server error log for detailed API response comparison</small>
</div>
<button type="button" id="toggleSelectAll" class="btn btn-primary">Select All</button>
<form action="<?= $saveurl ?>" method="post" class="mform">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="course_id" value="<?= $course_id ?>">
    <?php foreach ($questionsdata as $key => $qdata) : ?>
        <div class="card">
            <div class="card-body">
                <input type="checkbox" name="selected_questions[]" value="<?= $key ?>">
                <input type="hidden" name="question_names[<?= $key ?>]" value="<?= htmlspecialchars($qdata['name']) ?>">
                <input type="hidden" name="question_texts[<?= $key ?>]" value="<?= htmlspecialchars($qdata['text']) ?>">
                <strong><?= htmlspecialchars($qdata['name']) ?></strong>
                <p><?= htmlspecialchars($qdata['text']) ?></p>
                <p><strong>Question Type:</strong> <?= ucfirst($qdata['type']) ?></p>
                <?php if ($qdata['type'] === 'multiplechoice' && !empty($qdata['answers'])) : ?>
                    <?php $is_multiple = isset($qdata['is_multiple_answer']) && $qdata['is_multiple_answer']; ?>
                    <p><strong>Answer Type:</strong> <?= $is_multiple ? 'Multiple Answer (Checkbox)' : 'Single Answer (Radio)' ?></p>
                    <ul>
                        <?php foreach ($qdata['answers'] as $index => $answer) : ?>
                            <li style="display: flex; align-items: center; margin: 10px 0;">
                                <?php if ($is_multiple) : ?>
                                    <input type="checkbox" name="correct_answer[<?= $key ?>][]"
                                        value="<?= $index ?>" <?= $answer['fraction'] > 0 ? 'checked' : '' ?>
                                        style="margin-right: 10px;">
                                <?php else : ?>
                                    <input type="radio" name="correct_answer[<?= $key ?>]"
                                        value="<?= $index ?>" <?= $answer['fraction'] > 0 ? 'checked' : '' ?>
                                        style="margin-right: 10px;">
                                <?php endif; ?>
                                <input type="text" name="answers[<?= $key ?>][<?= $index ?>][text]"
                                    value="<?= htmlspecialchars($answer['text']) ?>"
                                    class="form-control"
                                    style="flex: 1;">
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p><em>(Essay Question - Requires written answer)</em></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="fitem">
        <div class="felement">
            <input type="submit" name="save" value="Save Selected Questions" class="btn btn-success">
        </div>
    </div>
</form>

<script>
    document.getElementById('toggleSelectAll').addEventListener('click', function() {
        let checkboxes = document.querySelectorAll('input[name="selected_questions[]"]');
        let allChecked = [...checkboxes].every(checkbox => checkbox.checked);

        checkboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
        });

        this.textContent = allChecked ? "Select All" : "Deselect All";
    });
</script>

<style>
    .card {
        margin: 1rem 0;
        padding: 1rem;
        border: 1px solid #ddd;
        border-radius: 8px;
    }

    ul {
        list-style-type: none;
        padding-left: 0;
    }

    li {
        margin: 0.5rem 0;
    }
</style>