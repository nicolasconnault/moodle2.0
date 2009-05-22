<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * On-the-fly conversion of Moodle lang strings to TinyMCE expected JS format.
 *
 * @package    moodlecore
 * @subpackage editor
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_UPGRADE_CHECK', true);

require_once('../../../../config.php');

$lang  = optional_param('elanguage', 'en', PARAM_SAFEDIR);
$theme = optional_param('etheme', 'advanced', PARAM_SAFEDIR);

if (file_exists($CFG->dataroot .'/lang/'. $lang) or file_exists($CFG->dirroot .'/lang/'. $lang)) {
    //ok
} else if (file_exists($CFG->dataroot.'/lang/'.$lang.'_utf8') or
           file_exists($CFG->dirroot .'/lang/'.$lang.'_utf8')) {
    $lang = $lang.'_utf8';
} else {
    $lang = 'en_utf8';
}

// load english defaults
$string = array();
foreach (get_langpack_locations('en_utf8') as $location) {
    if (!file_exists($location)) {
        continue;
    }
    include($location);
}

// find parent language
if ($parent = get_parent_language($lang)) {
    foreach (get_langpack_locations($parent) as $location) {
        if (!file_exists($location)) {
            continue;
        }
        include($location);
    }
}

// load wanted language
if ($lang !== 'en_utf8') {
    foreach (get_langpack_locations($lang) as $location) {
        if (!file_exists($location)) {
            continue;
        }
        include($location);
    }
}

//process the $strings to match expected tinymce lang array stucture
$result = array();

foreach ($string as $key=>$value) {
    $parts = explode(':', $key);
    if (count($parts) != 2) {
        // incorrect string - ignore
        continue;
    }
    $value = str_replace("%%","%",$value);              // Unescape % characters

    $result[$parts[0]][$parts[1]] = $value;
}

$lang = str_replace('_utf8', '', $lang); // use more standard language codes

$output = 'tinyMCE.addI18n({'.$lang.':'.json_encode($result).'});';

$lifetime = '10'; // TODO: increase later
@header('Content-type: text/javascript; charset=utf-8');
@header('Content-length: '.strlen($output));
@header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
@header('Cache-control: max-age='.$lifetime);
@header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .'GMT');
@header('Pragma: ');

echo $output;


/// ======= Functions =================

function get_langpack_locations($lang) {
    global $CFG;

    $result = array();
    $result[] = "$CFG->dirroot/lang/$lang/editor_tinymce.php";
    $result[] = "$CFG->dataroot/lang/$lang/editor_tinymce.php";
    $result[] = "$CFG->dataroot/lang/{$lang}_local/editor_tinymce.php";

    return $result;
}
