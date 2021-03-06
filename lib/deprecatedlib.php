<?php // $Id: deprecatedlib.php,v 1.108 2009/05/02 00:22:20 stronk7 Exp $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas, Moodle  http://moodle.com//
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
 * deprecatedlib.php - Old functions retained only for backward compatibility
 *
 * Old functions retained only for backward compatibility.  New code should not
 * use any of these functions.
 *
 * @author Martin Dougiamas
 * @version $Id: deprecatedlib.php,v 1.108 2009/05/02 00:22:20 stronk7 Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodlecore
 */

/**
 * Determines if a user is a teacher (or better)
 *
 * @uses $CFG
 * @param int $courseid The id of the course that is being viewed, if any
 * @param int $userid The id of the user that is being tested against. Set this to 0 if you would just like to test against the currently logged in user.
 * @param bool $obsolete_includeadmin Not used any more
 * @return bool
 */

function isteacher($courseid=0, $userid=0, $obsolete_includeadmin=true) {
/// Is the user able to access this course as a teacher?
    global $CFG;

    if ($courseid) {
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
    } else {
        $context = get_context_instance(CONTEXT_SYSTEM);
    }

    return (has_capability('moodle/legacy:teacher', $context, $userid, false)
         or has_capability('moodle/legacy:editingteacher', $context, $userid, false)
         or has_capability('moodle/legacy:admin', $context, $userid, false));
}

/**
 * Determines if a user is a teacher in any course, or an admin
 *
 * @uses $USER
 * @param int $userid The id of the user that is being tested against. Set this to 0 if you would just like to test against the currently logged in user.
 * @param bool $includeadmin Include anyone wo is an admin as well
 * @return bool
 */
function isteacherinanycourse($userid=0, $includeadmin=true) {
    global $USER, $CFG, $DB;

    if (!$userid) {
        if (empty($USER->id)) {
            return false;
        }
        $userid = $USER->id;
    }

    if (!$DB->record_exists('role_assignments', array('userid'=>$userid))) {    // Has no roles anywhere
        return false;
    }

/// If this user is assigned as an editing teacher anywhere then return true
    if ($roles = get_roles_with_capability('moodle/legacy:editingteacher', CAP_ALLOW)) {
        foreach ($roles as $role) {
            if ($DB->record_exists('role_assignments', array('roleid'=>$role->id, 'userid'=>$userid))) {
                return true;
            }
        }
    }

/// If this user is assigned as a non-editing teacher anywhere then return true
    if ($roles = get_roles_with_capability('moodle/legacy:teacher', CAP_ALLOW)) {
        foreach ($roles as $role) {
            if ($DB->record_exists('role_assignments', array('roleid'=>$role->id, 'userid'=>$userid))) {
                return true;
            }
        }
    }

/// Include admins if required
    if ($includeadmin) {
        $context = get_context_instance(CONTEXT_SYSTEM);
        if (has_capability('moodle/legacy:admin', $context, $userid, false)) {
            return true;
        }
    }

    return false;
}


/**
 * Determines if the specified user is logged in as guest.
 *
 * @param int $userid The user being tested. You can set this to 0 or leave it blank to test the currently logged in user.
 * @return bool
 */
function isguest($userid=0) {
    global $CFG;

    $context = get_context_instance(CONTEXT_SYSTEM);

    return has_capability('moodle/legacy:guest', $context, $userid, false);
}


/**
 * Get the guest user information from the database
 *
 * @return object(user) An associative array with the details of the guest user account.
 * @todo Is object(user) a correct return type? Or is array the proper return type with a note that the contents include all details for a user.
 */
function get_guest() {
    return get_complete_user_data('username', 'guest');
}

/**
 * Returns $user object of the main teacher for a course
 *
 * @uses $CFG
 * @param int $courseid The course in question.
 * @return user|false  A {@link $USER} record of the main teacher for the specified course or false if error.
 * @todo Finish documenting this function
 */
function get_teacher($courseid) {

    global $CFG;

    $context = get_context_instance(CONTEXT_COURSE, $courseid);

    // Pass $view=true to filter hidden caps if the user cannot see them
    if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                         '', '', '', '', false, true)) {
        $users = sort_by_roleassignment_authority($users, $context);
        return array_shift($users);
    }

    return false;
}

/**
 * Searches logs to find all enrolments since a certain date
 *
 * used to print recent activity
 *
 * @uses $CFG
 * @param int $courseid The course in question.
 * @return object|false  {@link $USER} records or false if error.
 * @todo Finish documenting this function
 */
function get_recent_enrolments($courseid, $timestart) {
    global $DB;

    $context = get_context_instance(CONTEXT_COURSE, $courseid);

    $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, l.time
              FROM {user} u, {role_assignments} ra, {log} l
             WHERE l.time > ?
                   AND l.course = ?
                   AND l.module = 'course'
                   AND l.action = 'enrol'
                   AND ".$DB->sql_cast_char2int('l.info')." = u.id
                   AND u.id = ra.userid
                   AND ra.contextid ".get_related_contexts_string($context)."
          ORDER BY l.time ASC";
    $params = array($timestart, $courseid);
    return $DB->get_records_sql($sql, $params);
}

########### FROM weblib.php ##########################################################################


/**
 * Print a message in a standard themed box.
 * This old function used to implement boxes using tables.  Now it uses a DIV, but the old
 * parameters remain.  If possible, $align, $width and $color should not be defined at all.
 * Preferably just use print_box() in weblib.php
 *
 * @param string $align, alignment of the box, not the text (default center, left, right).
 * @param string $width, width of the box, including units %, for example '100%'.
 * @param string $color, background colour of the box, for example '#eee'.
 * @param int $padding, padding in pixels, specified without units.
 * @param string $class, space-separated class names.
 * @param string $id, space-separated id names.
 * @param boolean $return, return as string or just print it
 */
function print_simple_box($message, $align='', $width='', $color='', $padding=5, $class='generalbox', $id='', $return=false) {
    $output = '';
    $output .= print_simple_box_start($align, $width, $color, $padding, $class, $id, true);
    $output .= $message;
    $output .= print_simple_box_end(true);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}



/**
 * This old function used to implement boxes using tables.  Now it uses a DIV, but the old
 * parameters remain.  If possible, $align, $width and $color should not be defined at all.
 * Even better, please use print_box_start() in weblib.php
 *
 * @param string $align, alignment of the box, not the text (default center, left, right).   DEPRECATED
 * @param string $width, width of the box, including % units, for example '100%'.            DEPRECATED
 * @param string $color, background colour of the box, for example '#eee'.                   DEPRECATED
 * @param int $padding, padding in pixels, specified without units.                          OBSOLETE
 * @param string $class, space-separated class names.
 * @param string $id, space-separated id names.
 * @param boolean $return, return as string or just print it
 */
function print_simple_box_start($align='', $width='', $color='', $padding=5, $class='generalbox', $id='', $return=false) {

    $output = '';

    $divclasses = 'box '.$class.' '.$class.'content';
    $divstyles  = '';

    if ($align) {
        $divclasses .= ' boxalign'.$align;    // Implement alignment using a class
    }
    if ($width) {    // Hopefully we can eliminate these in calls to this function (inline styles are bad)
        if (substr($width, -1, 1) == '%') {    // Width is a % value
            $width = (int) substr($width, 0, -1);    // Extract just the number
            if ($width < 40) {
                $divclasses .= ' boxwidthnarrow';    // Approx 30% depending on theme
            } else if ($width > 60) {
                $divclasses .= ' boxwidthwide';      // Approx 80% depending on theme
            } else {
                $divclasses .= ' boxwidthnormal';    // Approx 50% depending on theme
            }
        } else {
            $divstyles  .= ' width:'.$width.';';     // Last resort
        }
    }
    if ($color) {    // Hopefully we can eliminate these in calls to this function (inline styles are bad)
        $divstyles  .= ' background:'.$color.';';
    }
    if ($divstyles) {
        $divstyles = ' style="'.$divstyles.'"';
    }

    if ($id) {
        $id = ' id="'.$id.'"';
    }

    $output .= '<div'.$id.$divstyles.' class="'.$divclasses.'">';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Print the end portion of a standard themed box.
 * Preferably just use print_box_end() in weblib.php
 */
function print_simple_box_end($return=false) {
    $output = '</div>';
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * deprecated - use clean_param($string, PARAM_FILE); instead
 * Check for bad characters ?
 *
 * @param string $string ?
 * @param int $allowdots ?
 * @todo Finish documenting this function - more detail needed in description as well as details on arguments
 */
function detect_munged_arguments($string, $allowdots=1) {
    if (substr_count($string, '..') > $allowdots) {   // Sometimes we allow dots in references
        return true;
    }
    if (ereg('[\|\`]', $string)) {  // check for other bad characters
        return true;
    }
    if (empty($string) or $string == '/') {
        return true;
    }

    return false;
}


/**
 * Unzip one zip file to a destination dir
 * Both parameters must be FULL paths
 * If destination isn't specified, it will be the
 * SAME directory where the zip file resides.
 */
function unzip_file($zipfile, $destination = '', $showstatus_ignored = true) {
    global $CFG;

    //Extract everything from zipfile
    $path_parts = pathinfo(cleardoubleslashes($zipfile));
    $zippath = $path_parts["dirname"];       //The path of the zip file
    $zipfilename = $path_parts["basename"];  //The name of the zip file
    $extension = $path_parts["extension"];    //The extension of the file

    //If no file, error
    if (empty($zipfilename)) {
        return false;
    }

    //If no extension, error
    if (empty($extension)) {
        return false;
    }

    //Clear $zipfile
    $zipfile = cleardoubleslashes($zipfile);

    //Check zipfile exists
    if (!file_exists($zipfile)) {
        return false;
    }

    //If no destination, passed let's go with the same directory
    if (empty($destination)) {
        $destination = $zippath;
    }

    //Clear $destination
    $destpath = rtrim(cleardoubleslashes($destination), "/");

    //Check destination path exists
    if (!is_dir($destpath)) {
        return false;
    }

    $packer = get_file_packer('application/zip');

    $result = $packer->extract_to_pathname($zipfile, $destpath);

    if ($result === false) {
        return false;
    }

    foreach ($result as $status) {
        if ($status !== true) {
            return false;
        }
    }

    return true;
}

/**
 * Zip an array of files/dirs to a destination zip file
 * Both parameters must be FULL paths to the files/dirs
 */
function zip_files ($originalfiles, $destination) {
    global $CFG;

    //Extract everything from destination
    $path_parts = pathinfo(cleardoubleslashes($destination));
    $destpath = $path_parts["dirname"];       //The path of the zip file
    $destfilename = $path_parts["basename"];  //The name of the zip file
    $extension = $path_parts["extension"];    //The extension of the file

    //If no file, error
    if (empty($destfilename)) {
        return false;
    }

    //If no extension, add it
    if (empty($extension)) {
        $extension = 'zip';
        $destfilename = $destfilename.'.'.$extension;
    }

    //Check destination path exists
    if (!is_dir($destpath)) {
        return false;
    }

    //Check destination path is writable. TODO!!

    //Clean destination filename
    $destfilename = clean_filename($destfilename);

    //Now check and prepare every file
    $files = array();
    $origpath = NULL;

    foreach ($originalfiles as $file) {  //Iterate over each file
        //Check for every file
        $tempfile = cleardoubleslashes($file); // no doubleslashes!
        //Calculate the base path for all files if it isn't set
        if ($origpath === NULL) {
            $origpath = rtrim(cleardoubleslashes(dirname($tempfile)), "/");
        }
        //See if the file is readable
        if (!is_readable($tempfile)) {  //Is readable
            continue;
        }
        //See if the file/dir is in the same directory than the rest
        if (rtrim(cleardoubleslashes(dirname($tempfile)), "/") != $origpath) {
            continue;
        }
        //Add the file to the array
        $files[] = $tempfile;
    }

    $zipfiles = array();
    $start = strlen($origpath)+1;
    foreach($files as $file) {
        $zipfiles[substr($file, $start)] = $file;
    }

    $packer = get_file_packer('application/zip');

    return $packer->archive_to_pathname($zipfiles, $destpath . '/' . $destfilename);
}

/////////////////////////////////////////////////////////////
/// Old functions not used anymore - candidates for removal
/////////////////////////////////////////////////////////////


/** various deprecated groups function **/


/**
 * Get the IDs for the user's groups in the given course.
 *
 * @uses $USER
 * @param int $courseid The course being examined - the 'course' table id field.
 * @return array An _array_ of groupids.
 * (Was return $groupids[0] - consequences!)
 */
function mygroupid($courseid) {
    global $USER;
    if ($groups = groups_get_all_groups($courseid, $USER->id)) {
        return array_keys($groups);
    } else {
        return false;
    }
}


/**
 * Returns the current group mode for a given course or activity module
 *
 * Could be false, SEPARATEGROUPS or VISIBLEGROUPS    (<-- Martin)
 */
function groupmode($course, $cm=null) {

    if (isset($cm->groupmode) && empty($course->groupmodeforce)) {
        return $cm->groupmode;
    }
    return $course->groupmode;
}

/**
 * Sets the current group in the session variable
 * When $SESSION->currentgroup[$courseid] is set to 0 it means, show all groups.
 * Sets currentgroup[$courseid] in the session variable appropriately.
 * Does not do any permission checking.
 * @uses $SESSION
 * @param int $courseid The course being examined - relates to id field in
 * 'course' table.
 * @param int $groupid The group being examined.
 * @return int Current group id which was set by this function
 */
function set_current_group($courseid, $groupid) {
    global $SESSION;
    return $SESSION->currentgroup[$courseid] = $groupid;
}


/**
 * Gets the current group - either from the session variable or from the database.
 *
 * @uses $USER
 * @uses $SESSION
 * @param int $courseid The course being examined - relates to id field in
 * 'course' table.
 * @param bool $full If true, the return value is a full record object.
 * If false, just the id of the record.
 */
function get_current_group($courseid, $full = false) {
    global $SESSION;

    if (isset($SESSION->currentgroup[$courseid])) {
        if ($full) {
            return groups_get_group($SESSION->currentgroup[$courseid]);
        } else {
            return $SESSION->currentgroup[$courseid];
        }
    }

    $mygroupid = mygroupid($courseid);
    if (is_array($mygroupid)) {
        $mygroupid = array_shift($mygroupid);
        set_current_group($courseid, $mygroupid);
        if ($full) {
            return groups_get_group($mygroupid);
        } else {
            return $mygroupid;
        }
    }

    if ($full) {
        return false;
    } else {
        return 0;
    }
}


/**
 * Print an error page displaying an error message.
 * Old method, don't call directly in new code - use print_error instead.
 *
 * @param string $message The message to display to the user about the error.
 * @param string $link The url where the user will be prompted to continue. If no url is provided the user will be directed to the site index page.
 * @return terminates script, does not return!
 */
function error($message, $link='') {
    global $UNITTEST;

    // If unittest running, throw exception instead
    if (!empty($UNITTEST->running)) {
        // Errors in unit test become exceptions, so you can unit test
        // code that might call error().
        throw new moodle_exception('notlocalisederrormessage', 'error', $link, $message);
    }

    _print_normal_error('notlocalisederrormessage', 'error', $message, $link, debug_backtrace(), null, true); // show debug warning
}


/// Deprecated DDL functions, to be removed soon ///

function table_exists($table) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    return $DB->get_manager()->table_exists($table);
}

function field_exists($table, $field) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    return $DB->get_manager()->field_exists($table, $field);
}

function find_index_name($table, $index) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    return $DB->get_manager()->find_index_name($table, $index);
}

function index_exists($table, $index) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    return $DB->get_manager()->index_exists($table, $index);
}

function find_check_constraint_name($table, $field) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    return $DB->get_manager()->find_check_constraint_name($table, $field);
}

function check_constraint_exists($table, $field) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    return $DB->get_manager()->check_constraint_exists($table, $field);
}

function find_key_name($table, $xmldb_key) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    return $DB->get_manager()->find_key_name($table, $xmldb_key);
}

function find_sequence_name($table) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    return $DB->get_manager()->find_sequence_name($table);
}

function drop_table($table) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->drop_table($table);
    return true;
}

function install_from_xmldb_file($file) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->install_from_xmldb_file($file);
    return true;
}

function create_table($table) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->create_table($table);
    return true;
}

function create_temp_table($table) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->create_temp_table($table);
    return true;
}

function rename_table($table, $newname) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->rename_table($table, $newname);
    return true;
}

function add_field($table, $field) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->add_field($table, $field);
    return true;
}

function drop_field($table, $field) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->drop_field($table, $field);
    return true;
}

function change_field_type($table, $field) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->change_field_type($table, $field);
    return true;
}

function change_field_precision($table, $field) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->change_field_precision($table, $field);
    return true;
}

function change_field_unsigned($table, $field) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->change_field_unsigned($table, $field);
    return true;
}

function change_field_notnull($table, $field) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->change_field_notnull($table, $field);
    return true;
}

function change_field_enum($table, $field) {
    global $DB;
    debugging('Deprecated ddllib function used! Only dropping of enums is allowed.');
    $DB->get_manager()->drop_enum_from_field($table, $field);
    return true;
}

function change_field_default($table, $field) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->change_field_default($table, $field);
    return true;
}

function rename_field($table, $field, $newname) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->rename_field($table, $field, $newname);
    return true;
}

function add_key($table, $key) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->add_key($table, $key);
    return true;
}

function drop_key($table, $key) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->drop_key($table, $key);
    return true;
}

function rename_key($table, $key, $newname) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->rename_key($table, $key, $newname);
    return true;
}

function add_index($table, $index) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->add_index($table, $index);
    return true;
}

function drop_index($table, $index) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->drop_index($table, $index);
    return true;
}

function rename_index($table, $index, $newname) {
    global $DB;
    debugging('Deprecated ddllib function used!');
    $DB->get_manager()->rename_index($table, $index, $newname);
    return true;
}


//////////////////////////
/// removed functions ////
//////////////////////////

function stripslashes_safe($mixed) {
    error('stripslashes_safe() not available anymore');
}

function stripslashes_recursive($var) {
    error('stripslashes_recursive() not available anymore');
}

function addslashes_object($dataobject) {
    error('addslashes_object() not available anymore');
}

function addslashes_recursive($var) {
    error('addslashes_recursive() not available anymore');
}

function execute_sql($command, $feedback=true) {
    error('execute_sql() not available anymore');
}

function record_exists_select($table, $select='') {
    error('record_exists_select() not available anymore');
}

function record_exists_sql($sql) {
    error('record_exists_sql() not available anymore');
}

function count_records_select($table, $select='', $countitem='COUNT(*)') {
    error('count_records_select() not available anymore');
}

function count_records_sql($sql) {
    error('count_records_sql() not available anymore');
}

function get_record_sql($sql, $expectmultiple=false, $nolimit=false) {
    error('get_record_sql() not available anymore');
}

function get_record_select($table, $select='', $fields='*') {
    error('get_record_select() not available anymore');
}

function get_recordset($table, $field='', $value='', $sort='', $fields='*', $limitfrom='', $limitnum='') {
    error('get_recordset() not available anymore');
}

function get_recordset_sql($sql, $limitfrom=null, $limitnum=null) {
    error('get_recordset_sql() not available anymore');
}

function rs_fetch_record(&$rs) {
    error('rs_fetch_record() not available anymore');
}

function rs_next_record(&$rs) {
    error('rs_next_record() not available anymore');
}

function rs_fetch_next_record(&$rs) {
    error('rs_fetch_next_record() not available anymore');
}

function rs_EOF($rs) {
    error('rs_EOF() not available anymore');
}

function rs_close(&$rs) {
    error('rs_close() not available anymore');
}

function get_records_select($table, $select='', $sort='', $fields='*', $limitfrom='', $limitnum='') {
    error('get_records_select() not available anymore');
}

function get_field_select($table, $return, $select) {
    error('get_field_select() not available anymore');
}

function get_field_sql($sql) {
    error('get_field_sql() not available anymore');
}

function delete_records_select($table, $select='') {
    error('get_field_sql() not available anymore');
}


function configure_dbconnection() {
    error('configure_dbconnection() removed');
}

function sql_max($field) {
    error('sql_max() removed - use normal sql MAX() instead');
}

function sql_as() {
    error('sql_as() removed - do not use AS for tables at all');
}

function sql_paging_limit($page, $recordsperpage) {
    error('Function sql_paging_limit() is deprecated. Replace it with the correct use of limitfrom, limitnum parameters');
}

function db_uppercase() {
    error('upper() removed - use normal sql UPPER()');
}

function db_lowercase() {
    error('upper() removed - use normal sql LOWER()');
}

function modify_database($sqlfile='', $sqlstring='') {
    error('modify_database() removed - use new XMLDB functions');
}

function where_clause($field1='', $value1='', $field2='', $value2='', $field3='', $value3='') {
    error('where_clause() removed - use new functions with $conditions parameter');
}

function execute_sql_arr($sqlarr, $continue=true, $feedback=true) {
    error('execute_sql_arr() removed');
}

function get_records_list($table, $field='', $values='', $sort='', $fields='*', $limitfrom='', $limitnum='') {
    error('get_records_list() removed');
}

function get_recordset_list($table, $field='', $values='', $sort='', $fields='*', $limitfrom='', $limitnum='') {
    error('get_recordset_list() removed');
}

function get_records_menu($table, $field='', $value='', $sort='', $fields='*', $limitfrom='', $limitnum='') {
    error('get_records_menu() removed');
}

function get_records_select_menu($table, $select='', $sort='', $fields='*', $limitfrom='', $limitnum='') {
    error('get_records_select_menu() removed');
}

function get_records_sql_menu($sql, $limitfrom='', $limitnum='') {
    error('get_records_sql_menu() removed');
}

function column_type($table, $column) {
    error('column_type() removed');
}

function recordset_to_menu($rs) {
    error('recordset_to_menu() removed');
}

function records_to_menu($records, $field1, $field2) {
    error('records_to_menu() removed');
}

function set_field_select($table, $newfield, $newvalue, $select, $localcall = false) {
    error('set_field_select() removed');
}

function get_fieldset_select($table, $return, $select) {
    error('get_fieldset_select() removed');
}

function get_fieldset_sql($sql) {
    error('get_fieldset_sql() removed');
}

function sql_ilike() {
    error('sql_ilike() not available anymore');
}

function sql_fullname($first='firstname', $last='lastname') {
    error('sql_fullname() not available anymore');
}

function sql_concat() {
    error('sql_concat() not available anymore');
}

function sql_empty() {
    error('sql_empty() not available anymore');
}

function sql_substr() {
    error('sql_substr() not available anymore');
}

function sql_bitand($int1, $int2) {
    error('sql_bitand() not available anymore');
}

function sql_bitnot($int1) {
    error('sql_bitnot() not available anymore');
}

function sql_bitor($int1, $int2) {
    error('sql_bitor() not available anymore');
}

function sql_bitxor($int1, $int2) {
    error('sql_bitxor() not available anymore');
}

function sql_cast_char2int($fieldname, $text=false) {
    error('sql_cast_char2int() not available anymore');
}

function sql_compare_text($fieldname, $numchars=32) {
    error('sql_compare_text() not available anymore');
}

function sql_order_by_text($fieldname, $numchars=32) {
    error('sql_order_by_text() not available anymore');
}

function sql_length($fieldname) {
    error('sql_length() not available anymore');
}

function sql_concat_join($separator="' '", $elements=array()) {
    error('sql_concat_join() not available anymore');
}

function sql_isempty($tablename, $fieldname, $nullablefield, $textfield) {
    error('sql_isempty() not available anymore');
}

function sql_isnotempty($tablename, $fieldname, $nullablefield, $textfield) {
    error('sql_isnotempty() not available anymore');
}

function begin_sql() {
    error('begin_sql() not available anymore');
}

function commit_sql() {
    error('commit_sql() not available anymore');
}

function rollback_sql() {
    error('rollback_sql() not available anymore');
}

function insert_record($table, $dataobject, $returnid=true, $primarykey='id') {
    error('insert_record() not available anymore');
}

function update_record($table, $dataobject) {
    error('update_record() not available anymore');
}

function get_records($table, $field='', $value='', $sort='', $fields='*', $limitfrom='', $limitnum='') {
    error('get_records() not available anymore');
}

function get_record($table, $field1, $value1, $field2='', $value2='', $field3='', $value3='', $fields='*') {
    error('get_record() not available anymore');
}

function set_field($table, $newfield, $newvalue, $field1, $value1, $field2='', $value2='', $field3='', $value3='') {
    error('set_field() not available anymore');
}

function count_records($table, $field1='', $value1='', $field2='', $value2='', $field3='', $value3='') {
    error('count_records() not available anymore');
}

function record_exists($table, $field1='', $value1='', $field2='', $value2='', $field3='', $value3='') {
    error('record_exists() not available anymore');
}

function delete_records($table, $field1='', $value1='', $field2='', $value2='', $field3='', $value3='') {
    error('delete_records() not available anymore');
}

function get_field($table, $return, $field1, $value1, $field2='', $value2='', $field3='', $value3='') {
    error('get_field() not available anymore');
}

function table_column($table, $oldfield, $field, $type='integer', $size='10',
                      $signed='unsigned', $default='0', $null='not null', $after='') {
    error('table_column() was removed, please use new ddl functions');
}

function use_html_editor($name='', $editorhidebuttons='', $id='') {
    error('use_html_editor() not available anymore');
}
