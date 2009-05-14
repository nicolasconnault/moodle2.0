<?php  // $Id: managetabs.php,v 1.9 2009/03/25 02:17:14 tjhunt Exp $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Defines the tab bar used on the manage/allow assign/allow overrides pages.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package roles
 *//** */

    if (!defined('MOODLE_INTERNAL')) {
        die('Direct access to this script is forbidden.'); // It must be included from a Moodle page
    }

    $toprow = array();
    $toprow[] = new tabobject('manage', $CFG->wwwroot.'/'.$CFG->admin.'/roles/manage.php', get_string('manageroles', 'role'));
    $toprow[] = new tabobject('assign', $CFG->wwwroot.'/'.$CFG->admin.'/roles/allow.php?mode=assign', get_string('allowassign', 'role'));
    $toprow[] = new tabobject('override', $CFG->wwwroot.'/'.$CFG->admin.'/roles/allow.php?mode=override', get_string('allowoverride', 'role'));
    $toprow[] = new tabobject('switch', $CFG->wwwroot.'/'.$CFG->admin.'/roles/allow.php?mode=switch', get_string('allowswitch', 'role'));
    $tabs = array($toprow);

    print_tabs($tabs, $currenttab);

?>
