<?php  //$Id: install.php,v 1.2 2009/01/29 19:58:48 skodak Exp $

// This file replaces:
//   * STATEMENTS section in db/install.xml
//   * lib.php/modulename_install() post installation hook
//   * partially defaults.php

function xmldb_lesson_install() {
    global $DB;

/// Install logging support
    update_log_display_entry('lesson', 'start', 'lesson', 'name');
    update_log_display_entry('lesson', 'end', 'lesson', 'name');
    update_log_display_entry('lesson', 'view', 'lesson_pages', 'title');

}
