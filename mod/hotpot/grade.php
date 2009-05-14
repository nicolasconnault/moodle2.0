<?php  // $Id: grade.php,v 1.1 2007/08/06 05:31:17 moodler Exp $

    require_once("../../config.php");

    $id   = required_param('id', PARAM_INT);          // Course module ID

    if (! $cm = get_coursemodule_from_id('hotpot', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $hotpot = $DB->get_record("hotpot", array("id"=>$cm->instance))) {
        print_error('invalidhotpotid', 'hotpot');
    }

    if (! $course = $DB->get_record("course", array("id"=>$hotpot->course))) {
        print_error("invalidcourse");
    }

    require_login($course->id, false, $cm);

    if (has_capability('mod/hotpot:grade', get_context_instance(CONTEXT_MODULE, $cm->id))) {
        redirect('report.php?id='.$cm->id);
    } else {
        redirect('view.php?id='.$cm->id);
    }

?>
