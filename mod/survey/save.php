<?php // $Id: save.php,v 1.33 2009/05/05 09:07:21 skodak Exp $

    require_once('../../config.php');
    require_once('lib.php');


// Make sure this is a legitimate posting

    if (!$formdata = data_submitted()) {
        print_error('cannotcallscript');
    }

    $id = required_param('id', PARAM_INT);    // Course Module ID

    if (! $cm = get_coursemodule_from_id('survey', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
        print_error('coursemisconf');
    }

    require_login($course->id, false, $cm);
    
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/survey:participate', $context);
    
    if (! $survey = $DB->get_record("survey", array("id"=>$cm->instance))) {
        print_error('invalidsurveyid', 'survey');
    }

    add_to_log($course->id, "survey", "submit", "view.php?id=$cm->id", "$survey->id", "$cm->id");

    $strsurveysaved = get_string('surveysaved', 'survey');

    $navigation = build_navigation('', $cm);
    print_header_simple("$strsurveysaved", "", $navigation, "");


    if (survey_already_done($survey->id, $USER->id)) {
        notice(get_string("alreadysubmitted", "survey"), $_SERVER["HTTP_REFERER"]);
        exit;
    }


// Sort through the data and arrange it
// This is necessary because some of the questions
// may have two answers, eg Question 1 -> 1 and P1

    $answers = array();

    foreach ($formdata as $key => $val) {
        if ($key <> "userid" && $key <> "id") {
            if ( substr($key,0,1) == "q") {
                $key = clean_param(substr($key,1), PARAM_ALPHANUM);   // keep everything but the 'q', number or Pnumber
            }
            if ( substr($key,0,1) == "P") {
                $realkey = (int) substr($key,1);
                $answers[$realkey][1] = $val;
            } else {
                $answers[$key][0] = $val;
            }
        }
    }


// Now store the data.

    $timenow = time();
    foreach ($answers as $key => $val) {

        $newdata->time = $timenow;
        $newdata->userid = $USER->id;
        $newdata->survey = $survey->id;
        $newdata->question = $key;
        if (!empty($val[0])) {
            $newdata->answer1 = $val[0];
        } else {
            $newdata->answer1 = "";
        }
        if (!empty($val[1])) {
            $newdata->answer2 = $val[1];
        } else {
            $newdata->answer2 = "";
        }

        $DB->insert_record("survey_answers", $newdata);
    }

// Print the page and finish up.

    notice(get_string("thanksforanswers","survey", $USER->firstname), "$CFG->wwwroot/course/view.php?id=$course->id");

    exit;


?>
