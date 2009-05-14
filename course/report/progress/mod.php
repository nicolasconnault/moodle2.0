<?php  //$Id: mod.php,v 1.4 2008/11/29 16:41:20 skodak Exp $

    if (!defined('MOODLE_INTERNAL')) {
        die('Direct access to this script is forbidden.'); // It must be included from a Moodle page
    }

    if (has_capability('coursereport/progress:view', $context)) {
        $completion = new completion_info($course);
        if ($completion->is_enabled()) {
            echo '<p>';
            echo '<a href="'.$CFG->wwwroot.'/course/report/progress/?course='.$course->id.'">'.get_string('completionreport','completion').'</a>';
            echo '</p>';
        }
    }
?>
