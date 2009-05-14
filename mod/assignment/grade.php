<?php  // $Id: grade.php,v 1.5 2009/04/20 18:28:49 skodak Exp $

    require_once("../../config.php");

    $id   = required_param('id', PARAM_INT);          // Course module ID

    if (! $cm = get_coursemodule_from_id('assignment', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $assignment = $DB->get_record("assignment", array("id"=>$cm->instance))) {
        print_error('invalidid', 'assignment');
    }

    if (! $course = $DB->get_record("course", array("id"=>$assignment->course))) {
        print_error('coursemisconf', 'assignment');
    }

    require_login($course, false, $cm);

    if (has_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $cm->id))) {
        redirect('submissions.php?id='.$cm->id);
    } else {
        redirect('view.php?id='.$cm->id);
    }

?>
