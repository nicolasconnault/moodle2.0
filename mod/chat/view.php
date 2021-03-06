<?php  // $Id: view.php,v 1.83 2009/05/06 09:29:06 tjhunt Exp $

/// This page prints a particular instance of chat

    require_once(dirname(__FILE__) . '/../../config.php');
    require_once($CFG->dirroot . '/mod/chat/lib.php');

    $id   = optional_param('id', 0, PARAM_INT);
    $c    = optional_param('c', 0, PARAM_INT);
    $edit = optional_param('edit', -1, PARAM_BOOL);

    if ($id) {
        if (! $cm = get_coursemodule_from_id('chat', $id)) {
            print_error('invalidcoursemodule');
        }

        if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
            print_error('coursemisconf');
        }

        chat_update_chat_times($cm->instance);

        if (! $chat = $DB->get_record('chat', array('id'=>$cm->instance))) {
            print_error('invalidid', 'chat');
        }

    } else {
        chat_update_chat_times($c);

        if (! $chat = $DB->get_record('chat', array('id'=>$c))) {
            print_error('coursemisconf');
        }
        if (! $course = $DB->get_record('course', array('id'=>$chat->course))) {
            print_error('coursemisconf');
        }
        if (! $cm = get_coursemodule_from_instance('chat', $chat->id, $course->id)) {
            print_error('invalidcoursemodule');
        }
    }


    require_course_login($course, true, $cm);

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    // show some info for guests
    if (isguestuser()) {
        $navigation = build_navigation('', $cm);
        print_header_simple(format_string($chat->name), '', $navigation,
                      '', '', true, '', navmenu($course, $cm));

        notice_yesno(get_string('noguests', 'chat').'<br /><br />'.get_string('liketologin'),
                get_login_url(), $CFG->wwwroot.'/course/view.php?id='.$course->id);

        print_footer($course);
        exit;

    }

    add_to_log($course->id, 'chat', 'view', "view.php?id=$cm->id", $chat->id, $cm->id);

// Initialize $PAGE, compute blocks
    $PAGE->set_url('mod/chat/view.php', array('id' => $cm->id));

    // Note: MDL-19010 there will be further changes to printing header and blocks.
    // The code will be much nicer than this eventually.
    $pageblocks = blocks_setup($PAGE);
    $blocks_preferred_width = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]), 210);

/// Print the page header
    $strenterchat    = get_string('enterchat', 'chat');
    $stridle         = get_string('idle', 'chat');
    $strcurrentusers = get_string('currentusers', 'chat');
    $strnextsession  = get_string('nextsession', 'chat');

    if (($edit != -1) and $PAGE->user_allowed_editing()) {
        $USER->editing = $edit;
    }

    $title = $course->shortname . ': ' . format_string($chat->name);

    $buttons = '<table><tr><td>'.update_module_button($cm->id, $course->id, get_string('modulename', 'chat')).'</td>';
    if ($PAGE->user_allowed_editing() && !empty($CFG->showblocksonmodpages)) {
        $buttons .= '<td><form '.$CFG->frametarget.' method="get" action="view.php"><div>'.
                '<input type="hidden" name="id" value="'.$cm->id.'" />'.
                '<input type="hidden" name="edit" value="'.($PAGE->user_is_editing()?'off':'on').'" />'.
                '<input type="submit" value="'.get_string($PAGE->user_is_editing()?'blockseditoff':'blocksediton').'" /></div></form></td>';
    }
    $buttons .= '</tr></table>';

    $navigation = build_navigation(array(), $cm);
    print_header($title, $course->fullname, $navigation, '', '', true, $buttons, navmenu($course, $cm));

    echo '<table id="layout-table"><tr>';

    $lt = (empty($THEME->layouttable)) ? array('left', 'middle', 'right') : $THEME->layouttable;
    foreach ($lt as $column) {
        switch ($column) {
            case 'left':

                if(!empty($CFG->showblocksonmodpages) && (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $PAGE->user_is_editing())) {
                    echo '<td style="width: '.$blocks_preferred_width.'px;" id="left-column">';
                    print_container_start();
                    blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
                    print_container_end();
                    echo '</td>';
                }
                break;

            case 'middle':

                echo '<td id="middle-column">';
                print_container_start();

                /// Check to see if groups are being used here
                $groupmode = groups_get_activity_groupmode($cm);
                $currentgroup = groups_get_activity_group($cm, true);
                groups_print_activity_menu($cm, "view.php?id=$cm->id");

                if ($currentgroup) {
                    $groupselect = " AND groupid = '$currentgroup'";
                    $groupparam = "&amp;groupid=$currentgroup";
                } else {
                    $groupselect = "";
                    $groupparam = "";
                }

                if ($chat->studentlogs or has_capability('mod/chat:readlog',$context)) {
                    if ($msg = $DB->get_records_select('chat_messages', "chatid = ? $groupselect", array($chat->id))) {
                        echo '<div class="reportlink">';
                        echo "<a href=\"report.php?id=$cm->id\">".
                            get_string('viewreport', 'chat').'</a>';
                        echo '</div>';
                    }
                }


                print_heading(format_string($chat->name));

                if (has_capability('mod/chat:chat',$context)) {
                    /// Print the main part of the page
                    print_box_start('generalbox', 'enterlink');
                    // users with screenreader set, will only see 1 link, to the manual refresh page
                    // for better accessibility
                    if (!empty($USER->screenreader)) {
                        $chattarget = "/mod/chat/gui_basic/index.php?id=$chat->id$groupparam";
                    } else {
                        $chattarget = "/mod/chat/gui_$CFG->chat_method/index.php?id=$chat->id$groupparam"; 
                    }

                    echo '<p>';
                    link_to_popup_window ($chattarget,
                            "chat$course->id$chat->id$groupparam", "$strenterchat", 500, 700, get_string('modulename', 'chat'));
                    echo '</p>';

                    if ($CFG->enableajax) {
                        echo '<p>';
                        link_to_popup_window ("/mod/chat/gui_ajax/index.php?id=$chat->id$groupparam",
                            "chat$course->id$chat->id$groupparam", get_string('ajax_gui', 'message'), 500, 700, get_string('modulename', 'chat'));
                        echo '</p>';
                    }

                    // if user is using screen reader, then there is no need to display this link again
                    if ($CFG->chat_method == 'header_js' && empty($USER->screenreader)) {
                        // show frame/js-less alternative
                        echo '<p>(';
                        link_to_popup_window ("/mod/chat/gui_basic/index.php?id=$chat->id$groupparam",
                            "chat$course->id$chat->id$groupparam", get_string('noframesjs', 'message'), 500, 700, get_string('modulename', 'chat'));
                        echo ')</p>';
                    }

                    print_box_end();

                } else {
                    print_box_start('generalbox', 'notallowenter');
                    echo '<p>'.get_string('notallowenter', 'chat').'</p>';
                    print_box_end();
                }

                if ($chat->chattime and $chat->schedule) {  // A chat is scheduled
                    echo "<p class=\"nextchatsession\">$strnextsession: ".userdate($chat->chattime).' ('.usertimezone($USER->timezone).')</p>';
                } else {
                    echo '<br />';
                }

                if ($chat->intro) {
                    print_box(format_module_intro('chat', $chat, $cm->id), 'generalbox', 'intro');
                }

                chat_delete_old_users();

                if ($chatusers = chat_get_users($chat->id, $currentgroup, $cm->groupingid)) {
                    $timenow = time();
                    print_simple_box_start('center');
                    print_heading($strcurrentusers);
                    echo '<table id="chatcurrentusers">';
                    foreach ($chatusers as $chatuser) {
                        $lastping = $timenow - $chatuser->lastmessageping;
                        echo '<tr><td class="chatuserimage">';
                        echo "<a href=\"$CFG->wwwroot/user/view.php?id=$chatuser->id&amp;course=$chat->course\">";
                        print_user_picture($chatuser, 0, $chatuser->picture, false, false, false);
                        echo '</a></td><td class="chatuserdetails">';
                        echo '<p>';
                        echo fullname($chatuser).'<br />';
                        echo "<span class=\"idletime\">$stridle: ".format_time($lastping)."</span>";
                        echo '</p>';
                        echo '</td></tr>';
                    }
                    echo '</table>';
                    print_simple_box_end();
                }

                print_container_end();
                echo '</td>';

                break;
        }
    }

    echo '</tr></table>';

    print_footer($course);

?>
