<?php // $Id: loginas.php,v 1.55 2009/01/02 20:32:07 skodak Exp $
      // Allows a teacher/admin to login as another user (in stealth mode)

    require_once('../config.php');
    require_once('lib.php');

/// Reset user back to their real self if needed
    $return = optional_param('return', 0, PARAM_BOOL);   // return to the page we came from

    if (session_is_loggedinas()) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad');
        }

        session_unloginas();

        if ($return and isset($_SERVER["HTTP_REFERER"])) { // That's all we wanted to do, so let's go back
            redirect($_SERVER["HTTP_REFERER"]);
        } else {
            redirect($CFG->wwwroot);
        }
    }

///-------------------------------------
/// We are trying to log in as this user in the first place

    $id     = optional_param('id', SITEID, PARAM_INT);   // course id
    $userid = required_param('user', PARAM_INT);         // login as this user

    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad');
    }

    if (!$course = $DB->get_record('course', array('id'=>$id))) {
        print_error("invalidcourseid");
    }

/// User must be logged in

    $systemcontext = get_context_instance(CONTEXT_SYSTEM);
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

    require_login();

    if (has_capability('moodle/user:loginas', $systemcontext)) {
        if (has_capability('moodle/site:doanything', $systemcontext, $userid, false)) {
            print_error('nologinas');
        }
        $context = $systemcontext;
    } else {
        require_login($course);
        require_capability('moodle/user:loginas', $coursecontext);
        if (!has_capability('moodle/course:view', $coursecontext, $userid, false)) {
            print_error('usernotincourse');
        }
        if (has_capability('moodle/site:doanything', $coursecontext, $userid, false)) {
            print_error('nologinas');
        }
        $context = $coursecontext;
    }

/// Login as this user and return to course home page.
    $oldfullname = fullname($USER, true);
    session_loginas($userid, $context);
    $newfullname = fullname($USER, true);

    add_to_log($course->id, "course", "loginas", "../user/view.php?id=$course->id&amp;user=$userid", "$oldfullname -> $newfullname");

    $strloginas    = get_string('loginas');
    $strloggedinas = get_string('loggedinas', '', $newfullname);

    print_header_simple($strloggedinas, '', build_navigation(array(array('name'=>$strloggedinas, 'link'=>'','type'=>'misc'))),
            '', '', true, '&nbsp;', navmenu($course));
    notice($strloggedinas, "$CFG->wwwroot/course/view.php?id=$course->id");


?>
