<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/questionlib.php');

// Define constants if not already defined
if (!defined('FORMAT_HTML')) {
    define('FORMAT_HTML', 1);
}
if (!defined('IGNORE_MISSING')) {
    define('IGNORE_MISSING', 1);
}

/**
 * Adds an instance of the quizgenerator activity to the database
 *
 * @param object $quizgenerator An object from the form in mod_form.php
 * @param mod_quizgenerator_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted quizgenerator record
 */
function quizgenerator_add_instance(stdClass $quizgenerator, mod_quizgenerator_mod_form $mform = null)
{
    global $DB;

    $quizgenerator->timecreated = time();

    // You may have to add extra stuff in here.

    $quizgenerator->id = $DB->insert_record('quizgenerator', $quizgenerator);

    return $quizgenerator->id;
}

/**
 * Updates an instance of the quizgenerator activity in the database
 *
 * @param object $quizgenerator An object from the form in mod_form.php
 * @param mod_quizgenerator_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function quizgenerator_update_instance(stdClass $quizgenerator, mod_quizgenerator_mod_form $mform = null)
{
    global $DB;

    $quizgenerator->timemodified = time();
    $quizgenerator->id = $quizgenerator->instance;

    // You may have to add extra stuff in here.

    $result = $DB->update_record('quizgenerator', $quizgenerator);

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every quizgenerator event in the site is checked, else
 * only quizgenerator events belonging to the course specified are checked.
 * This is only required if the module is generating calendar events.
 *
 * @param int $courseid Course ID
 * @return bool
 */
function quizgenerator_refresh_events($courseid = 0)
{
    global $DB;

    if ($courseid == 0) {
        if (!$quizgenerators = $DB->get_records('quizgenerator')) {
            return true;
        }
    } else {
        if (!$quizgenerators = $DB->get_records('quizgenerator', array('course' => $courseid))) {
            return true;
        }
    }

    foreach ($quizgenerators as $quizgenerator) {
        // Create a function such as the one below to deal with updating calendar events.
        // quizgenerator_update_events($quizgenerator);
    }

    return true;
}

/**
 * Removes an instance of the quizgenerator from the database
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function quizgenerator_delete_instance($id)
{
    global $DB;

    if (! $quizgenerator = $DB->get_record('quizgenerator', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.

    $DB->delete_records('quizgenerator', array('id' => $quizgenerator->id));

    return true;
}

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function quizgenerator_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        default:
            return null;
    }
}

function quizgenerator_get_question_category($courseid)
{
    global $DB, $CFG;
    
    try {
        // Ambil context course (contextlevel 50 adalah untuk course)
        $context = context_course::instance($courseid);
        
        // Cari kategori yang sudah ada
        $category = $DB->get_record('question_categories', ['contextid' => $context->id], '*', IGNORE_MISSING);
        
        if (!$category) {
            // Jika belum ada, buat kategori default
            
            // Buat kategori default untuk course ini
            $categorydata = new stdClass();
            $categorydata->name = 'Default for ' . format_string(get_course($courseid)->shortname);
            $categorydata->contextid = $context->id;
            $categorydata->info = 'The default category for questions shared in context \'' . $context->get_context_name() . '\'.';
            $categorydata->infoformat = FORMAT_HTML;
            $categorydata->stamp = uniqid();
            $categorydata->parent = 0;
            $categorydata->sortorder = 999;
            
            $categoryid = $DB->insert_record('question_categories', $categorydata);
            return $categoryid;
        }
        
        return $category->id;
    } catch (Exception $e) {
        // Log error and return null
        error_log('Error getting question category for course ' . $courseid . ': ' . $e->getMessage());
        return null;
    }
}

function quizgenerator_create_question($categoryid, $questiondata)
{
    global $DB, $USER;

    // Ensure we have a valid user ID
    $userid = (isset($USER->id) && $USER->id > 0) ? $USER->id : 2; // Use admin user (id=2) as fallback

    if (!$categoryid) {
        return false;
    }

    // Tentukan tipe soal (multichoice atau essay)
    $qtype = $questiondata->answers == NULL ? 'essay' : 'multichoice';

    // Buat objek pertanyaan
    $question = new stdClass();
    $question->category               = $categoryid;
    $question->name                   = $questiondata->name;
    $question->questiontext           = $questiondata->text;
    $question->questiontextformat     = FORMAT_HTML;
    $question->generalfeedback        = '';
    $question->generalfeedbackformat  = FORMAT_HTML;
    $question->qtype                  = $qtype;
    $question->defaultmark            = 1;
    $question->penalty                = 0.3333333;
    $question->penaltyformat          = FORMAT_HTML;
    $question->createdby              = $userid;
    $question->modifiedby             = $userid;
    $question->stamp                  = uniqid();
    $question->version                = 1;

    // Insert soal ke tabel 'question'
    try {
        $questionid = $DB->insert_record('question', $question);
    } catch (Exception $e) {
        return false;
    }

    if (!$questionid) {
        return false;
    }

    if ($qtype === 'essay') {
        $essayOptions = new stdClass();
        $essayOptions->questionid = $questionid;
        $essayOptions->responseformat = 'editor';
        $essayOptions->responserequired = 1;
        $essayOptions->responsefieldlines = 15;
        $essayOptions->minwordlimit = NULL;
        $essayOptions->maxwordlimit = NULL;
        $essayOptions->attachments = 0;
        $essayOptions->attachmentsrequired = 0;
        $essayOptions->graderinfo = NULL;
        $essayOptions->graderinfoformat = 0;
        $essayOptions->responsetemplate = NULL;
        $essayOptions->responsetemplateformat = 0;
        $essayOptions->maxbytes = 0;
        $essayOptions->filetypeslist = NULL;

        $DB->insert_record('qtype_essay_options', $essayOptions);
    }

    // Insert ke 'question_bank_entries'
    $qbe = new stdClass();
    $qbe->questioncategoryid = $categoryid;
    $qbe->ownerid = $userid;
    try {
        $questionbankentryid = $DB->insert_record('question_bank_entries', $qbe);
    } catch (Exception $e) {
        return false;
    }

    if (!$questionbankentryid) {
        return false;
    }

    // Insert ke 'question_versions'
    $qv = new stdClass();
    $qv->questionbankentryid = $questionbankentryid;
    $qv->version = 1;
    $qv->questionid = $questionid;
    $qv->status = "ready";
    $questionversionid = $DB->insert_record('question_versions', $qv);
    if (!$questionversionid) {
        return false;
    }

    // Hanya tambahkan jawaban jika tipe soal adalah multiple choice
    if ($qtype === 'multichoice') {
        // Insert ke 'qtype_multichoice_options' dulu
        $mcOptions = new stdClass();
        $mcOptions->questionid = $questionid;
        $mcOptions->layout = 0; // Vertical layout
        $mcOptions->single = 1; // Single answer (can be changed to 0 for multiple answers)
        $mcOptions->shuffleanswers = 1;
        $mcOptions->correctfeedback = '';
        $mcOptions->correctfeedbackformat = FORMAT_HTML;
        $mcOptions->partiallycorrectfeedback = '';
        $mcOptions->partiallycorrectfeedbackformat = FORMAT_HTML;
        $mcOptions->incorrectfeedback = '';
        $mcOptions->incorrectfeedbackformat = FORMAT_HTML;
        $mcOptions->answernumbering = 'abc';
        $mcOptions->shownumcorrect = 1;

        // Check if this is multiple answer question
        if (isset($questiondata->is_multiple_answer) && $questiondata->is_multiple_answer) {
            $mcOptions->single = 0; // Multiple answers allowed
        }

        $DB->insert_record('qtype_multichoice_options', $mcOptions);

        // Kemudian tambahkan jawaban
        foreach ($questiondata->answers as $answer) {
            $answerobj = new stdClass();
            $answerobj->question = $questionid;
            $answerobj->answer = $answer['text'];
            $answerobj->answerformat = FORMAT_HTML;
            $answerobj->fraction = $answer['fraction'];
            $answerobj->feedback = '';
            $answerobj->feedbackformat = FORMAT_HTML;
            $DB->insert_record('question_answers', $answerobj);
        }
    }

    return $questionid;
}

/**
 * Call external API to generate questions
 *
 * @param array $data Data to send to API
 * @return array|false Array of questions or false on error
 */
function quizgenerator_call_api($data)
{
    global $CFG;

    // Get API endpoint from settings
    $api_url = get_config('mod_quizgenerator', 'api_endpoint');
    if (empty($api_url)) {
        $api_url = 'http://103.155.224.67:5200/quiz'; // Fallback to default
    }

    // Get timeout from settings
    $timeout = get_config('mod_quizgenerator', 'api_timeout');
    if (empty($timeout)) {
        $timeout = 30; // Default timeout
    }

    // Get SSL verification setting
    $ssl_verify = get_config('mod_quizgenerator', 'ssl_verify');

    // Get API Key setting
    $api_key = get_config('mod_quizgenerator', 'api_key');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $headers = [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($data))
    ];
    if (!empty($api_key)) {
        $headers[] = 'X-API-Key: ' . trim($api_key);
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);

    if ($curl_error) {
        curl_close($ch);
        return ['error' => 'Connection error: ' . $curl_error];
    }

    curl_close($ch);

    if ($http_code === 200) {
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        } else {
            return ['error' => 'Invalid JSON response'];
        }
    }

    return ['error' => 'HTTP Error ' . $http_code . ': ' . $response];
}

/**
 * Mengambil daftar semua modules/activities di course
 *
 * @param int $courseid
 * @return array Array berisi modules dengan key: 'id', 'name', 'modname'
 */
function quizgenerator_get_course_modules($courseid)
{
    global $DB;
    
    $modinfo = get_fast_modinfo($courseid);
    $cms = $modinfo->get_cms();
    $modules = [];
    
    foreach ($cms as $cm) {
        if (!$cm->uservisible) {
            continue;
        }
        
        // Skip hidden modules
        if (!$cm->visible) {
            continue;
        }
        
        $modules[] = [
            'id' => $cm->id,
            'name' => format_string($cm->name),
            'modname' => $cm->modname,
            'section' => $cm->sectionnum
        ];
    }
    
    return $modules;
}
