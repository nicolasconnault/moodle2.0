<?php  //$Id: install.php,v 1.2 2009/01/29 19:58:48 skodak Exp $

// This file replaces:
//   * STATEMENTS section in db/install.xml
//   * lib.php/modulename_install() post installation hook
//   * partially defaults.php

function xmldb_choice_install() {
    global $DB;

/// Install logging support
    update_log_display_entry('choice', 'view', 'choice', 'name');
    update_log_display_entry('choice', 'update', 'choice', 'name');
    update_log_display_entry('choice', 'add', 'choice', 'name');
    update_log_display_entry('choice', 'report', 'choice', 'name');
    update_log_display_entry('choice', 'choose', 'choice', 'name');
    update_log_display_entry('choice', 'choose again', 'choice', 'name');

}
