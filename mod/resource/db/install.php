<?php  //$Id: install.php,v 1.2 2009/01/29 19:58:49 skodak Exp $

// This file replaces:
//   * STATEMENTS section in db/install.xml
//   * lib.php/modulename_install() post installation hook
//   * partially defaults.php

function xmldb_resource_install() {
    global $DB;

/// Install logging support

    update_log_display_entry('resource', 'view', 'resource', 'name');
    update_log_display_entry('resource', 'update', 'resource', 'name');
    update_log_display_entry('resource', 'add', 'resource', 'name');

}
