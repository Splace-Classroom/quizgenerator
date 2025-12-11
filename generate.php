<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('quizgenerator', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, true, $cm);

$PAGE->set_url('/mod/quizgenerator/generate.php', ['id' => $id]);
$PAGE->set_title('Generate Questions');
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

$categoryid = quizgenerator_get_question_category($course->id);
$course_modules = quizgenerator_get_course_modules($course->id);

if (optional_param('save', false, PARAM_BOOL)) {
    require_once('success.php');
} elseif (optional_param('generate', false, PARAM_BOOL)) {
    require_once('preview.php');
} else {
    require_once('form.php');
}

echo $OUTPUT->footer();

?>

<style>
    .activity-header {
        display: none;
    }
</style>