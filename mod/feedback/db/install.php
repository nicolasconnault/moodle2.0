<?php  //$Id: install.php,v 1.2 2009/01/29 19:58:48 skodak Exp $

// This file replaces:
//   * STATEMENTS section in db/install.xml
//   * lib.php/modulename_install() post installation hook
//   * partially defaults.php

function xmldb_feedback_install() {
    global $DB;

/// Install logging support
    update_log_display_entry('feedback', 'startcomplete', 'feedback', 'name');
    update_log_display_entry('feedback', 'submit', 'feedback', 'name');
    update_log_display_entry('feedback', 'delete', 'feedback', 'name');
    update_log_display_entry('feedback', 'view', 'feedback', 'name');
    update_log_display_entry('feedback', 'view all', 'course', 'shortname');

}
