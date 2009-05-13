<?php //$Id$

/// Sets up blocks and navigation for index.php

require_once($CFG->dirroot .'/blog/lib.php');
require_once($CFG->dirroot .'/blog/blogpage.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->dirroot .'/tag/lib.php');

$blockaction = optional_param('blockaction','', PARAM_ALPHA);
$instanceid  = optional_param('instanceid', 0, PARAM_INT);
$blockid     = optional_param('blockid',    0, PARAM_INT);

/// If user has never visited this page before, install 2 blocks for him
blog_check_and_install_blocks();


if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
    print_error('invalidcourseid', '', '', $courseid);
}

// Bounds for block widths
// more flexible for theme designers taken from theme config.php
$lmin = (empty($THEME->block_l_min_width)) ? 160 : $THEME->block_l_min_width;
$lmax = (empty($THEME->block_l_max_width)) ? 210 : $THEME->block_l_max_width;
$rmin = (empty($THEME->block_r_min_width)) ? 160 : $THEME->block_r_min_width;
$rmax = (empty($THEME->block_r_max_width)) ? 210 : $THEME->block_r_max_width;

define('BLOCK_L_MIN_WIDTH', $lmin);
define('BLOCK_L_MAX_WIDTH', $lmax);
define('BLOCK_R_MIN_WIDTH', $rmin);
define('BLOCK_R_MAX_WIDTH', $rmax);

//_____________ new page class code ________
$pagetype = PAGE_BLOG_VIEW;
$pageclass = 'page_blog';

// map our page identifier to the actual name
// of the class which will be handling its operations.
page_map_class($pagetype, $pageclass);

// Now, create our page object.
if (empty($USER->id)) {
    $PAGE = page_create_object($pagetype);
} else {
    $PAGE = page_create_object($pagetype, $USER->id);
}
$PAGE->set_course($course);
$PAGE->filtertype   = $filtertype;
$PAGE->filterselect = $filterselect;
$PAGE->tagid        = $tagid;
$PAGE->filters      = $filters;

$array = array();
if (!empty($course->id)) {
    $array['courseid'] = $course->id;
}
if (!empty($filtertype)) {
    $array['filtertype'] = $filtertype;
}
if (!empty($filterselect)) {
    $array['filterselect'] = $filterselect;
}
if (!empty($tagid)) {
    $array['tagid'] = $tagid;
}
$PAGE->set_url('blog/index.php', $array);
$PAGE->set_blocks_editing_capability('moodle/blog:create');
$PAGE->init_full(); //init the BlogInfo object and the courserecord object

$editing = false;
if ($PAGE->user_allowed_editing()) {
    $editing = $PAGE->user_is_editing();
}

// Calculate the preferred width for left, right and center (both center positions will use the same)
$preferredwidthleft  = bounded_number(BLOCK_L_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]),
                                        BLOCK_L_MAX_WIDTH);
$preferredwidthright = bounded_number(BLOCK_R_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]),
                                        BLOCK_R_MAX_WIDTH);

/// navigations
/// course blogs - sitefullname -> course fullname -> (?participants->user/group) -> blogs -> (?tag)
/// mod blogs    - sitefullname -> course fullname -> mod name -> (?user/group) -> blogs -> (?tag)
/// group blogs - sitefullname -> course fullname ->group ->(?tag)
/// user blogs   - sitefullname -> (?coursefullname) -> (?mod name) -> participants -> blogs -> (?tag)

$blogstring = get_string('blogs','blog');
$tagstring = get_string('tag');

// needed also for user tabs later
if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
    print_error('invalidcourseid', '', '', $courseid);
}

$navlinks = array();

//tabs compatibility
$filtertype = 'site';
$filterselect = $USER->id;

if(!empty($courseid)) {
    $COURSE = $DB->get_record('course', array('id'=>$courseid));
    if (empty($groupid) and has_capability('moodle/course:viewparticipants', $coursecontext))    {
        $navlinks[] = array('name' => get_string('participants'),
            'link' => "$CFG->wwwroot/user/index.php?id=$courseid",
                                    'type' => 'misc');
            }
    //tabs compatibility
    $filtertype = 'course';
    $filterselect = $courseid;
}

if(!empty($modid)) { //mod
    $cm = $DB->get_record('course_modules', array('id' => $modid));
    $cm->modname = $DB->get_field('modules', 'name', array('id' => $cm->module));
    $cm->name = $DB->get_field($cm->modname, 'name', array('id' => $cm->instance));

    //tabs compatibility
    $filtertype = 'course';
    $filterselect = $cm->course;
}

if(!empty($groupid)) {
    if ($thisgroup = groups_get_group($groupid, false)) { //TODO:
                    $navlinks[] = array('name' => $thisgroup->name,
                                        'link' => "$CFG->wwwroot/user/index.php?id=$course->id&amp;group=$groupid",
                                        'type' => 'misc');
            } else {
                print_error('cannotfindgroup');
            }

    //tabs compatibility
    $filtertype = 'group';
    $filterselect = $thisgroup->id;
            }

if(!empty($userid)) {
    $user = $DB->get_record('user', array('id'=>$userid));
                $navlinks[] = array('name' => fullname($user),
                                    'link' => "$CFG->wwwroot/user/view.php?id=$userid".(empty($courseid)?'':"&amp;course=$courseid"),
                                    'type' => 'misc');

    //tabs compatibility
    $filtertype = 'user';
    $filterselect = $user->id;
                }
$navlinks[] = array('name' => $blogstring, 'link' => null, 'type' => 'misc');

if(!empty($tagid)) {
    $tagrec = $DB->get_record('tag', array('id'=>$tagid));
    $navlinks[] = array('name' => $tagrec->name,
        'link' => "index.php",
                                        'type' => 'misc');
                }
if(isset($cm)) $navigation = build_navigation($navlinks, $cm);
else $navigation = build_navigation($navlinks);

print_header("$COURSE->shortname: $blogstring", $COURSE->fullname, $navigation,'','',true,$PAGE->get_extra_header_string());

// prints the tabs
$showroles = !empty($userid);
$currenttab = 'blogs';

$user = $USER;
require_once($CFG->dirroot .'/user/tabs.php');


/// Layout the whole page as three big columns.
print '<table border="0" cellpadding="3" cellspacing="0" width="100%" id="layout-table">' . "\n";
print '<tr valign="top">' . "\n";

/// The left column ...
if (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing) {
    print '<td style="vertical-align: top; width: '. $preferredwidthleft .'px;" id="left-column">' . "\n";
    print '<!-- Begin left side blocks -->' . "\n";
    print_container_start();
    blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
    print_container_end();
    print '<!-- End left side blocks -->' . "\n";
    print '</td>' . "\n";
}

/// Start main column
print '<!-- Begin page content -->' . "\n";
print '<td>';
print_container_start();
?>
<table width="100%">
<tr>
<td valign="top">
