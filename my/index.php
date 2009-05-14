<?php  // $Id: index.php,v 1.28 2009/05/06 09:29:06 tjhunt Exp $

    // this is the 'my moodle' page

    require_once(dirname(__FILE__) . '/../config.php');
    require_once($CFG->dirroot.'/course/lib.php');

    require_login();

    $strmymoodle = get_string('mymoodle','my');

    if (isguest()) {
        print_header($strmymoodle);
        notice_yesno(get_string('noguest', 'my') . '<br /><br />' .
                get_string('liketologin'), get_login_url(), $CFG->wwwroot);
        print_footer();
        die;
    }

    $edit = optional_param('edit', -1, PARAM_BOOL);
    $blockaction = optional_param('blockaction', '', PARAM_ALPHA);

    $PAGE->set_context(get_context_instance(CONTEXT_USER, $USER->id));
    $PAGE->set_url('my/index.php');
    $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');

    // Note: MDL-19010 there will be further changes to printing header and blocks.
    // The code will be much nicer than this eventually.
    $pageblocks = blocks_setup($PAGE,BLOCKS_PINNED_BOTH);

    if (($edit != -1) and $PAGE->user_allowed_editing()) {
        $USER->editing = $edit;
    }

    $button = update_mymoodle_icon($USER->id);
    $header = $SITE->shortname . ': ' . $strmymoodle;
    $navigation = build_navigation($strmymoodle);
    $loggedinas = user_login_string();

    if (empty($CFG->langmenu)) {
        $langmenu = '';
    } else {
        $currlang = current_language();
        $langs = get_list_of_languages();
        $langlabel = get_accesshide(get_string('language'));
        $langmenu = popup_form($CFG->wwwroot . '/my/index.php?lang=', $langs,
                'chooselang', $currlang, '', '', '', true, 'self', $langlabel);
    }

    print_header($strmymoodle, $header, $navigation, '', '', true, $button, $loggedinas . $langmenu);

    echo '<table id="layout-table">';
    echo '<tr valign="top">';

    $lt = (empty($THEME->layouttable)) ? array('left', 'middle', 'right') : $THEME->layouttable;
    foreach ($lt as $column) {
        switch ($column) {
            case 'left':

    $blocks_preferred_width = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]), 210);

    if(blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $PAGE->user_is_editing()) {
        echo '<td style="vertical-align: top; width: '.$blocks_preferred_width.'px;" id="left-column">';
        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
        print_container_end();
        echo '</td>';
    }
    
            break;
            case 'middle':
    
    echo '<td valign="top" id="middle-column">';
    print_container_start(TRUE);

/// The main overview in the middle of the page
    
    // limits the number of courses showing up
    $courses_limit = 21;
    if (!empty($CFG->mycoursesperpage)) {
        $courses_limit = $CFG->mycoursesperpage;
    }
    $courses = get_my_courses($USER->id, 'visible DESC,sortorder ASC', '*', false, $courses_limit);
    $site = get_site();
    $course = $site; //just in case we need the old global $course hack

    if (array_key_exists($site->id,$courses)) {
        unset($courses[$site->id]);
    }

    foreach ($courses as $c) {
        if (isset($USER->lastcourseaccess[$c->id])) {
            $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
        } else {
            $courses[$c->id]->lastaccess = 0;
        }
    }
    
    if (empty($courses)) {
        print_simple_box(get_string('nocourses','my'),'center');
    } else {
        print_overview($courses);
    }
    
    // if more than 20 courses
    if (count($courses) > 20) {
        echo '<br />...';  
    }
    
    print_container_end();
    echo '</td>';
    
            break;
            case 'right':
            
    $blocks_preferred_width = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]), 210);

    if (blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $PAGE->user_is_editing()) {
        echo '<td style="vertical-align: top; width: '.$blocks_preferred_width.'px;" id="right-column">';
        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
        print_container_end();
        echo '</td>';
    }
            break;
        }
    }

    /// Finish the page
    echo '</tr></table>';

    print_footer();

?>
