<?php  // $Id: view.php,v 1.120 2009/05/08 09:00:41 tjhunt Exp $

    require_once("../../config.php");
    require_once("lib.php");
    require_once($CFG->libdir . '/completionlib.php');

    $id         = required_param('id', PARAM_INT);                 // Course Module ID
    $action     = optional_param('action', '', PARAM_ALPHA);
    $attemptids = optional_param('attemptid', array(), PARAM_INT); // array of attempt ids for delete action

    if (! $cm = get_coursemodule_from_id('choice', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }

    require_course_login($course, false, $cm);

    if (!$choice = choice_get_choice($cm->instance)) {
        print_error('invalidcoursemodule');
    }

    $strchoice = get_string('modulename', 'choice');
    $strchoices = get_string('modulenameplural', 'choice');

    if (!$context = get_context_instance(CONTEXT_MODULE, $cm->id)) {
        print_error('badcontext');
    }

    if ($action == 'delchoice') {
        if ($answer = $DB->get_record('choice_answers', array('choiceid' => $choice->id, 'userid' => $USER->id))) {
            //print_object($answer);
            $DB->delete_records('choice_answers', array('id' => $answer->id));
        }
    }

    $navigation = build_navigation('', $cm);
    print_header_simple(format_string($choice->name), "", $navigation, "", "", true,
                  update_module_button($cm->id, $course->id, $strchoice), navmenu($course, $cm));

/// Submit any new data if there is any
    if ($form = data_submitted() && has_capability('mod/choice:choose', $context)) {
        $timenow = time();
        if (has_capability('mod/choice:deleteresponses', $context)) {
            if ($action == 'delete') { //some responses need to be deleted     
                choice_delete_responses($attemptids, $choice->id); //delete responses.
                redirect("view.php?id=$cm->id");
            }
        }
        $answer = optional_param('answer', '', PARAM_INT);

        if (empty($answer)) {
            redirect("view.php?id=$cm->id", get_string('mustchooseone', 'choice'));
        } else {
            choice_user_submit_response($answer, $choice, $USER->id, $course->id, $cm);
        }
        notify(get_string('choicesaved', 'choice'),'notifysuccess');
    }


/// Display the choice and possibly results
    add_to_log($course->id, "choice", "view", "view.php?id=$cm->id", $choice->id, $cm->id);

    /// Check to see if groups are being used in this choice
    $groupmode = groups_get_activity_groupmode($cm);

    if ($groupmode) {
        groups_get_activity_group($cm, true);
        groups_print_activity_menu($cm, 'view.php?id='.$id);
    }
    $allresponses = choice_get_response_data($choice, $cm, $groupmode);   // Big function, approx 6 SQL calls per user


    if (has_capability('mod/choice:readresponses', $context)) {
        choice_show_reportlink($allresponses, $cm);
    }

    echo '<div class="clearer"></div>';

    if ($choice->intro) {
        print_box(format_module_intro('choice', $choice, $cm->id), 'generalbox', 'intro');
    }

    $current = false;  // Initialise for later
    //if user has already made a selection, and they are not allowed to update it, show their selected answer.
    if (!empty($USER->id) && ($current = $DB->get_record('choice_answers', array('choiceid' => $choice->id, 'userid' => $USER->id))) &&
        empty($choice->allowupdate) ) {
        print_simple_box(get_string("yourselection", "choice", userdate($choice->timeopen)).": ".format_string(choice_get_option_text($choice, $current->optionid)), "center");
    }

/// Print the form
    $choiceopen = true;
    $timenow = time();
    if ($choice->timeclose !=0) {
        if ($choice->timeopen > $timenow ) {
            print_simple_box(get_string("notopenyet", "choice", userdate($choice->timeopen)), "center");
            print_footer($course);
            exit;
        } else if ($timenow > $choice->timeclose) {
            print_simple_box(get_string("expired", "choice", userdate($choice->timeclose)), "center");
            $choiceopen = false;
        }
    }

    if ( (!$current or $choice->allowupdate) and $choiceopen and
          has_capability('mod/choice:choose', $context) ) {
    // They haven't made their choice yet or updates allowed and choice is open

        echo '<form id="form" method="post" action="view.php">';

        choice_show_form($choice, $USER, $cm, $allresponses);

        echo '</form>';

        $choiceformshown = true;
    } else {
        $choiceformshown = false;
    }



    if (!$choiceformshown) {

        $sitecontext = get_context_instance(CONTEXT_SYSTEM);

        if (has_capability('moodle/legacy:guest', $sitecontext, NULL, false)) {      // Guest on whole site
            notice_yesno(get_string('noguestchoose', 'choice').'<br /><br />'.get_string('liketologin'),
                         get_login_url(), $_SERVER['HTTP_REFERER']);

        } else if (has_capability('moodle/legacy:guest', $context, NULL, false)) {   // Guest in this course only
            $SESSION->wantsurl = $FULLME;
            $SESSION->enrolcancel = $_SERVER['HTTP_REFERER'];

            print_simple_box_start('center', '60%', '', 5, 'generalbox', 'notice');
            echo '<p align="center">'. get_string('noguestchoose', 'choice') .'</p>';
            echo '<div class="continuebutton">';
            print_single_button($CFG->wwwroot.'/course/enrol.php?id='.$course->id, NULL,
                                get_string('enrolme', '', format_string($course->shortname)), 'post', $CFG->framename);
            echo '</div>'."\n";
            print_simple_box_end();

        }
    }

    // print the results at the bottom of the screen

    if ( $choice->showresults == CHOICE_SHOWRESULTS_ALWAYS or
        ($choice->showresults == CHOICE_SHOWRESULTS_AFTER_ANSWER and $current ) or
        ($choice->showresults == CHOICE_SHOWRESULTS_AFTER_CLOSE and !$choiceopen ) )  {

        choice_show_results($choice, $course, $cm, $allresponses); //show table with students responses.

    } else if (!$choiceformshown) {
        print_simple_box(get_string('noresultsviewable', 'choice'), 'center');
    }

    print_footer($course);

/// Mark as viewed
    $completion=new completion_info($course);
    $completion->set_module_viewed($cm);
?>