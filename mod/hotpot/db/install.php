<?php  //$Id: install.php,v 1.1 2009/01/12 21:13:31 skodak Exp $

// This file replaces:
//   * STATEMENTS section in db/install.xml
//   * lib.php/modulename_install() post installation hook
//   * partially defaults.php

function xmldb_hotpot_install() {
    global $DB;

/// Disable it by default
    $DB->set_field('modules', 'visible', 0, array('name'=>'hotpot'));

/// Install logging support here
    

}
