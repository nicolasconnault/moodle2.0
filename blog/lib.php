<?php //$Id$

/**
 * Library of functions and constants for blog
 */
require_once($CFG->dirroot .'/blog/rsslib.php');
require_once($CFG->dirroot .'/blog/blogpage.php');
require_once($CFG->dirroot.'/tag/lib.php');

/**
 * Definition of blogcourse page type (blog page with course id present).
 */
//not used at the moment, and may not need to be
define('PAGE_BLOG_COURSE_VIEW', 'blog_course-view');


/**
 * Checks to see if user has visited blogpages before, if not, install 2
 * default blocks (blog_menu and blog_tags).
 */
function blog_check_and_install_blocks() {
    global $USER, $DB;

    if (isloggedin() && !isguest()) {

        // if this user has not visited this page before
        if (!get_user_preferences('blogpagesize')) {

            // find the correct ids for blog_menu and blog_from blocks
            $menublock = $DB->get_record('block', array('name'=>'blog_menu'));
            $tagsblock = $DB->get_record('block', array('name'=>'blog_tags'));

            // add those 2 into block_instance page

// Commmented out since the block changes broke it. Hopefully nico will fix it ;-)
//                // add blog_menu block
//                $newblock = new object();
//                $newblock->blockid  = $menublock->id;
//                $newblock->pageid   = $USER->id;
//                $newblock->pagetype = 'blog-view';
//                $newblock->position = 'r';
//                $newblock->weight   = 0;
//                $newblock->visible  = 1;
//                $DB->insert_record('block_instances', $newblock);
//
//                // add blog_tags menu
//                $newblock -> blockid = $tagsblock->id;
//                $newblock -> weight  = 1;
//                $DB->insert_record('block_instances', $newblock);

            // finally we set the page size pref
            set_user_preference('blogpagesize', 10);
        }
    }
}


/**
 * This function is in lib and not in BlogInfo because entries being searched
 * might be found in any number of blogs rather than just one.
 *
 * @param array filters
 */
function blog_print_html_formatted_entries($filters) {

    global $CFG, $USER, $PAGE;

    $blogpage  = optional_param('blogpage', 0, PARAM_INT);
    $bloglimit = optional_param('limit', get_user_preferences('blogpagesize', 10), PARAM_INT);
    $start     = $blogpage * $bloglimit;

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);

    $morelink = '<br />&nbsp;&nbsp;';

    $blogEntries = blog_fetch_entries($bloglimit, $start, $filters, $sort='created DESC', true);
    $totalentries = blog_get_viewable_entry_count($filters);

    print_paging_bar($totalentries, $blogpage, $bloglimit, blog_get_blogs_url($filters), 'blogpage');

    if ($CFG->enablerssfeeds) {
        blog_rss_print_link($filters);
    }

    if (has_capability('moodle/blog:create', $sitecontext)) {
        //the user's blog is enabled and they are viewing their own blog
        $coursearg = '';

        if (!empty($PAGE->course)) {
            $coursearg = '&amp;courseid='.$PAGE->course->id;
            if (!empty($PAGE->module)) {
                $coursearg .= '&amp;modid='.$PAGE->module->id;
            }
        }

        $addlink = '<div class="addbloglink">';
        $addlink .= '<a href="'.$CFG->wwwroot .'/blog/edit.php?action=add'.$coursearg.'">'
                 . get_string('addnewentry', 'blog').'</a>';
        $addlink .= '</div>';
        echo $addlink;
    }

    if ($blogEntries) {

        $count = 0;
        foreach ($blog_entries as $blog_entry) {
            blog_print_entry($blog_entry, 'list', $filters); //print this entry.
            $count++;
        }

        print_paging_bar($totalentries, $blogpage, $bloglimit, blog_get_blogs_url($filters), 'blogpage');

        if (!$count) {
            print '<br /><div style="text-align:center">'. get_string('noentriesyet', 'blog') .'</div><br />';

        }

        print $morelink.'<br />'."\n";
        return;
    }

    $output = '<br /><div style="text-align:center">'. get_string('noentriesyet', 'blog') .'</div><br />';

    print $output;

}


/**
 * This function is in lib and not in BlogInfo because entries being searched
 * might be found in any number of blogs rather than just one.
 *
 * This function builds an array which can be used by the included
 * template file, making predefined and nicely formatted variables available
 * to the template. Template creators will not need to become intimate
 * with the internal objects and vars of moodle blog nor will they need to worry
 * about properly formatting their data
 *
 * @param blog_entry blog_entry - a hopefully fully populated blog_entry object
 * @param string viewtype Default is 'full'. If 'full' then display this blog entry
 *                        in its complete form (eg. archive page). If anything other than 'full'
 *                        display the entry in its abbreviated format (eg. index page)
 * @return void This function only prints HTML
 */
function blog_print_entry($blog_entry, $viewtype='full', $filters=array(), $mode='loud') {
    global $USER, $CFG, $COURSE, $DB;

    $template['body'] = format_text($blog_entry->summary, $blog_entry->format);
    $template['title'] = '<a id="b'. s($blog_entry->id) .'" />';
    //enclose the title in nolink tags so that moodle formatting doesn't autolink the text
    $template['title'] .= '<span class="nolink">'. format_string($blog_entry->subject) .'</span>';
    $template['userid'] = $blog_entry->userid;
    $template['author'] = fullname($DB->get_record('user', array('id'=>$blog_entry->userid)));
    $template['created'] = userdate($blog_entry->created);

    if ($blog_entry->created != $blog_entry->lastmodified) {
        $template['lastmod'] = userdate($blog_entry->lastmodified);
    }

    $template['publishstate'] = $blog_entry->publishstate;

    /// preventing user to browse blogs that they aren't supposed to see
    /// This might not be too good since there are multiple calls per page

    /*
    if (!blog_user_can_view_user_post($template['userid'])) {
        print_error('cannotviewuserblog', 'blog');
    }*/

    $stredit = get_string('edit');
    $strdelete = get_string('delete');

    $user = $DB->get_record('user', array('id'=>$template['userid']));

    //check to see if the post is unassociated with group/course level access
    $unassociatedpost = false;

    if (!empty($CFG->useassoc) && ($blog_entry->publishstate == 'group' || $blog_entry->publishstate == 'course')) {
        if (!$DB->record_exists('blog_association', array('blogid' => $blog_entry->id))) {
            $unassociatedpost = true;
        }
    }

    /// Start printing of the blog
    echo '<table cellspacing="0" class="forumpost blogpost blog'
        . ($unassociatedpost ? 'draft' : $template['publishstate']).'" width="100%">';

    echo '<tr class="header"><td class="picture left">';
    print_user_picture($user, SITEID, $user->picture);
    echo '</td>';

    echo '<td class="topic starter"><div class="subject">'.$template['title'].'</div><div class="author">';
    $fullname = fullname($user, has_capability('moodle/site:viewfullnames',
                                               get_context_instance(CONTEXT_COURSE, $COURSE->id)));
    $by = new object();
    $by->name =  '<a href="'.$CFG->wwwroot.'/user/view.php?id='.
                $user->id.'&amp;course='.$COURSE->id.'">'.$fullname.'</a>';
    $by->date = $template['created'];

    print_string('bynameondate', 'forum', $by);
    echo '</div></td></tr>';

    echo '<tr><td class="left side">';

/// Actual content

    echo '</td><td class="content">'."\n";

    if ($blog_entry->attachment) {
        echo '<div class="attachments">';
        $attachedimages = blog_print_attachments($blog_entry);
        echo '</div>';
    } else {
        $attachedimages = '';
    }

    switch ($template['publishstate']) {
        case 'draft':
            $blogtype = get_string('publishtonoone', 'blog');
        break;
        case 'course':
            $blogtype = !empty($CFG->useassoc) ?
                get_string('publishtocourseassoc', 'blog') :
                get_string('publishtocourse', 'blog');
        break;
        case 'group':
            $blogtype = !empty($CFG->useassoc) ?
                get_string('publishtogroupassoc', 'blog') :
                get_string('publishtogroup', 'blog');
        break;
        case 'site':
            $blogtype = get_string('publishtosite', 'blog');
        break;
        case 'public':
            $blogtype = get_string('publishtoworld', 'blog');
        break;
        default:
            $blogtype = '';
        break;

    }

    echo '<div class="audience">'.$blogtype.'</div>';

    // Print whole message
    echo $template['body'];

    /// Print attachments
    echo $attachedimages;
/// Links to tags

    if (!empty($CFG->usetags) && ($blogtags = tag_get_tags_csv('post', $blog_entry->id))) {
        echo '<div class="tags">';
        if ($blogtags) {
            print(get_string('tags', 'tag') .': '. $blogtags);
        }
        echo '</div>';
    }

    //add associations
    $blog_associations = $DB->get_records('blog_association', array('blogid' => $blog_entry->id));

    if (!empty($CFG->useassoc) && $blog_associations) {
        echo '<div clas="tags">';
        $assoc_str = '';

        foreach ($blog_associations as $assoc_rec) {  //first find and show the associated course
            $context_rec = $DB->get_record('context', array('id' => $assoc_rec->contextid));

            if ($context_rec->contextlevel ==  CONTEXT_COURSE) {
                $assoc_str .= '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$context_rec->instanceid.'">';
                $assoc_str .= '<img src="'.$CFG->pixpath.'/i/course.gif" border=0  alt="">';
                $assoc_str .= $DB->get_field('course', 'shortname', array('id' => $context_rec->instanceid));
                $assoc_str .= '</a>';
            }
        }

        foreach ($blog_associations as $assoc_rec) {  //now show each mod association
            $context_rec = $DB->get_record('context', array('id' => $assoc_rec->contextid));

            if ($context_rec->contextlevel ==  CONTEXT_MODULE) {
                $modinfo = $DB->get_record('course_modules', array('id' => $context_rec->instanceid));
                $modname = $DB->get_field('modules', 'name', array('id' => $modinfo->module));
                $assoc_str .= ', ';
                $assoc_str .= '<a href="'.$CFG->wwwroot.'/mod/'.$modname.'/view.php?id='.$modinfo->id.'">';
                $assoc_str .= '<img src="'.$CFG->wwwroot.'/mod/'.$modname.'/icon.gif" border=0 alt="">';
                $assoc_str .= $DB->get_field($modname, 'name', array('id' => $modinfo->instance));
                $assoc_str .= '</a>';
            }
        }
        echo get_string('associations', 'blog') . ': '. $assoc_str;

        echo '</div>';
    }

    if ($unassociatedpost) {
        echo '<div class="noticebox">'.get_string('associationunviewable', 'blog').'</div>';
    }

/// Commands

    echo '<div class="commands">';

    if (blog_user_can_edit_post($blog_entry)) {
        echo '<a href="'.$CFG->wwwroot.'/blog/edit.php?action=edit&amp;id='.$blog_entry->id.'">'.$stredit.'</a>';

        $sql = "SELECT a.timedue, a.preventlate, a.emailteachers, a.var2, asub.grade
                FROM {assignment} a, {assignment_submissions} as asub
                WHERE a.id = asub.assignment AND userid = ? AND a.assignmenttype = 'blog' AND asub.data1 = '?'";

        if (!$DB->record_exists_sql($sql, array($USER->id, $blog_entry->id))) {
            echo '| <a href="'.$CFG->wwwroot.'/blog/edit.php?action=delete&amp;id='.$blog_entry->id.'">'.$strdelete.'</a>';
        }

        echo ' | ';
    }

    echo '<a href="'.$CFG->wwwroot.'/blog/index.php?postid='.$blog_entry->id.'">'.get_string('permalink', 'blog').'</a>';

    echo '</div>';

    if( isset($template['lastmod']) ){
        echo '<div style="font-size: 55%;">';
        echo ' [ '.get_string('modified').': '.$template['lastmod'].' ]';
        echo '</div>';
    }

    echo '</td></tr></table>'."\n\n";

}

/**
 * Deletes all the user files in the attachments area for a post
 * @param object $post
 */
function blog_delete_attachments($post) {
    $fs = get_file_storage();
    $fs->delete_area_files(SYSCONTEXTID, 'blog', $post->id);
}

/**
 * Print blog entry attachments
 * @param object $blog_entry
 * @param string $return (html|text|NULL) if null: print HTML for non-images, and return image HTML
 */
function blog_print_attachments($blog_entry, $return=NULL) {
    global $CFG;

    require_once($CFG->libdir.'/filelib.php');

    $fs = get_file_storage();
    $browser = get_file_browser();

    $files = $fs->get_area_files(SYSCONTEXTID, 'blog', $blog_entry->id);

    $imagereturn = "";
    $output = "";

    $strattachment = get_string("attachment", "forum");

    foreach ($files as $file) {
        if ($file->is_directory()) {
            continue;
        }

        $filename = $file->get_filename();
        $ffurl    = $browser->encodepath($CFG->wwwroot.'/pluginfile.php', '/'
                  . SYSCONTEXTID.'/blog/'.$blog_entry->id.'/'.$filename);
        $type     = $file->get_mimetype();
        $icon     = mimeinfo_from_type("icon", $type);
        $type     = mimeinfo_from_type("type", $type);

        $image = '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="" />';

        if ($return == "html") {
            $output .= "<a href=\"$ffurl\">$image</a> ";
            $output .= "<a href=\"$ffurl\">$filename</a><br />";

        } else if ($return == "text") {
            $output .= "$strattachment $filename:\n$ffurl\n";

        } else {
            // Image attachments don't get printed as links
            if (in_array($type, array('image/gif', 'image/jpeg', 'image/png'))) {
                $imagereturn .= "<br /><img src=\"$ffurl\" alt=\"\" />";
            } else {
                echo "<a href=\"$ffurl\">$image</a> ";
                echo filter_text("<a href=\"$ffurl\">$filename</a><br />");
            }
        }
    }

    if ($return) {
        return $output;
    }

    return $imagereturn;
}


/**
 * Use this function to retrieve a list of publish states available for
 * the currently logged in user.
 * @param int $courseid
 * @return array This function returns an array ideal for sending to moodle's choose_from_menu function.
 */
function blog_applicable_publish_states($courseid='') {
    global $CFG;

    // everyone gets draft access
    if ($CFG->bloglevel >= BLOG_USER_LEVEL) {
        $options = array ( 'draft' => get_string('publishtonoone', 'blog') );
    }

    if ($CFG->bloglevel >= BLOG_GROUP_LEVEL) {
        $options['group'] = empty($CFG->useassoc) ? get_string('publishtogroupassoc', 'blog') : get_string('publishtogroup', 'blog');
    }

    if ($CFG->bloglevel >= BLOG_COURSE_LEVEL) {
        $options['course'] = !empty($CFG->useassoc) ? get_string('publishtocourseassoc', 'blog') : get_string('publishtocourse', 'blog');
    }

    if ($CFG->bloglevel >= BLOG_SITE_LEVEL) {
        $options['site'] = get_string('publishtosite', 'blog');
    }

    if ($CFG->bloglevel >= BLOG_GLOBAL_LEVEL) {
        $options['public'] = get_string('publishtoworld', 'blog');
    }

    return $options;
}


/**
 * User can edit a blog entry if this is their own blog post and they have
 * the capability moodle/blog:create, or if they have the capability
 * moodle/blog:manageentries.
 *
 * This also applies to deleting of posts.
 * @param object $blog_entry
 * @return boolean
 */
function blog_user_can_edit_post($blog_entry) {
    global $CFG, $USER;

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);

    if (has_capability('moodle/blog:manageentries', $sitecontext)) {
        return true; // can edit any blog post
    }

    if ($blog_entry->userid == $USER->id and has_capability('moodle/blog:create', $sitecontext)) {
        return true; // can edit own when having blog:create capability
    }

    return false;
}


/**
 * Checks to see if a user can view the blogs of another user.
 * Only blog level is checked here, the capabilities are enforced
 * in blog/index.php
 */
function blog_user_can_view_user_post($target_userid, $blog_entry=null) {
    global $CFG, $USER, $DB;

    if (empty($CFG->bloglevel)) {
        return false; // blog system disabled
    }

    if (!empty($USER->id) and $USER->id == $target_userid) {
        return true; // can view own posts in any case
    }

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    if (has_capability('moodle/blog:manageentries', $sitecontext)) {
        return true; // can manage all posts
    }

    // coming for 1 post, make sure it's not a draft
    if ($blog_entry and $blog_entry->publishstate == 'draft') {
        return false;  // can not view draft of others
    }

    // coming for 1 post, make sure user is logged in, if not a public blog
    if ($blog_entry && $blog_entry->publishstate != 'public' && !isloggedin()) {
        return false;
    }

    switch ($CFG->bloglevel) {
        case BLOG_GLOBAL_LEVEL:
            return true;
        break;

        case BLOG_SITE_LEVEL:
            if (!empty($USER->id)) { // not logged in viewers forbidden
                return true;
            }
            return false;
        break;

        case BLOG_COURSE_LEVEL:
            $mycourses = array_keys(get_my_courses($USER->id));
            $usercourses = array_keys(get_my_courses($target_userid));
            $shared = array_intersect($mycourses, $usercourses);
            if (!empty($shared)) {
                return true;
            }
            return false;
        break;

        case BLOG_GROUP_LEVEL:
            $mycourses = array_keys(get_my_courses($USER->id));
            $usercourses = array_keys(get_my_courses($target_userid));
            $shared = array_intersect($mycourses, $usercourses);
            foreach ($shared as $courseid) {
                $course = $DB->get_record('course', array('id'=>$courseid));
                $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
                if (has_capability('moodle/site:accessallgroups', $coursecontext)
                  or groups_get_course_groupmode($course) != SEPARATEGROUPS) {
                    return true;
                } else {
                    if ($usergroups = groups_get_all_groups($courseid, $target_userid)) {
                        foreach ($usergroups as $usergroup) {
                            if (groups_is_member($usergroup->id)) {
                                return true;
                            }
                        }
                    }
                }
            }
            return false;
        break;

        case BLOG_USER_LEVEL:
        default:
            return has_capability('moodle/user:readuserblogs', get_context_instance(CONTEXT_USER, $target_userid));
        break;

    }
}


/**
 * Generates an SQL query for retrieving blog entries
 */
    function blog_fetch_entries_sql($fetchlimit=10, $fetchstart='', $filters=array(), $sort='lastmodified DESC', $limit=true) {
    global $CFG, $USER, $DB;

    // If we have specified a post ID
    // Just return 1 entry

    if (!empty($filters['post'])) {
        if ($post = $DB->get_record('post', array('id'=>$filters['post']))) {

            if (blog_user_can_view_user_post($post->userid, $post)) {

                if ($user = $DB->get_record('user', array('id'=>$post->userid))) {
                    $post->email = $user->email;
                    $post->firstname = $user->firstname;
                    $post->lastname = $user->lastname;
                }
                $retarray[] = $post;
                return $retarray;
            } else {
                return null;
            }

        } else { // bad postid
            return null;
        }
    }

    // The query used to locate blog entries is complicated.  It will be built from the following components:
    $requiredfields = "p.*, u.firstname,u.lastname,u.email";  // the SELECT clause
    $tables = array('p' => 'post', 'u' => 'user');   // components of the FROM clause (table_id => table_name)
    $conditions = array('u.deleted = 0', 'p.userid = u.id', "p.module = 'blog'");  // components of the WHERE clause (conjunction)

    if (!empty($filters['tag'])) {
        $tables['ti'] = 'tag_instance';
        $conditions[] = 'ti.itemid = p.id';
        $conditions[] = 'ti.tagid = '.$filters['tag'];
        $conditions[] = "ti.itemtype = 'post'";
    }

    // build up a clause for permission constraints

    // fix for MDL-9165, use with readuserblogs capability in a user context can read that user's private blogs
    // admins can see all blogs regardless of publish states, as described on the help page


    if (has_capability('moodle/user:readuserblogs', get_context_instance(CONTEXT_SYSTEM))) {
        // don't add permission constraints

    } else if(!empty($filters['user']) && has_capability('moodle/user:readuserblogs', get_context_instance(CONTEXT_USER, $filters['user']))) {
        // don't add permission constraints

    } else {

        if (isloggedin() && !has_capability('moodle/legacy:guest', get_context_instance(CONTEXT_SYSTEM, SITEID), $USER->id, false)) {
            $usergroups = '';
            $usercourses = '';
            $assocexists = $DB->record_exists('blog_association', array());  //dont check association records if there aren't any

            foreach($DB->get_records('groups_members', array('userid' => $USER->id)) as $rec) {
                $usergroups .= ($usergroups ? ', ' : '') . $rec->groupid;
            }

            foreach(get_my_courses($USER->id) as $course) {
                $usercourses .= ($usercourses ? ', ' : '') . $course->context->id;
            }

            if (!empty($filter['course'])) { //optimization to make searches faster
                $filtercontext = get_context_instance(CONTEXT_COURSE, $filter['course']);
                if (!in_array($filtercontext->id, $usercourses)) {
                    return array();
                }

                if (!empty($filter['group'])) {
                    if (!in_array($filters['group'], $usergroups)) {
                        return array();
                    }
                }
            }

            $permissionsql =  '(p.userid = '.$USER->id.' ';

            if ($CFG->bloglevel >= BLOG_SITE_LEVEL) { // add permission to view site-level posts
                $permissionsql .= " OR p.publishstate = 'site' ";
            }

            if ($CFG->bloglevel >= BLOG_GLOBAL_LEVEL) {
                $permissionsql .= " OR p.publishstate = 'public' ";
            }

            if (empty($CFG->useassoc)) {  // insure viewer shares *any* course/group with the poster
                if ($usergroups and $CFG->bloglevel >= BLOG_GROUP_LEVEL) {
                    $tables['gm'] = 'groups_members';
                    $permissionsql .= " OR (p.publishstate = 'group' ".
                                      "     AND gm.userid = p.userid AND gm.groupid IN ($usergroups))";
                }
                if ($usercourses and $CFG->bloglevel >= BLOG_COURSE_LEVEL) {
                    $tables['ra'] = 'role_assignments';
                    $permissionsql .= " OR (p.publishstate = 'course' ".
                                      "     AND p.userid = ra.userid AND ra.contextid IN ('.$usercourses.'))";
                }
            } else if ($assocexists) { // insure viewer has access to the associated course (if course or group level access is used)
                if ($usercourses and $CFG->bloglevel >= BLOG_COURSE_LEVEL) {
                    $tables['ba'] = 'blog_association';
                    $permissionsql .=" OR (p.publishstate = 'course' AND p.id = ba.blogid AND ba.contextid IN ('.$usercourses.'))";
                }
                if ($usergroups and $CFG->bloglevel >= BLOG_GROUP_LEVEL) {
                    $tables['gma'] = 'groups_members';
                    $tables['gmb'] = 'groups_members';
                    $tables['ba'] = 'blog_association';
                    $permissionsql .= " OR (p.publishstate = 'group' AND p.id = ba.blogid AND ba.contextid IN ('.$usercourses.')
                                    AND gma.groupid = gmb.groupid AND gma.userid = '.$USER->id.' AND gmb.userid = p.userid) ";
                }
            }
            $permissionsql .= ') ';
        } else {
            $permissionsql = "p.publishstate = 'site'";
        }
        $conditions[] = $permissionsql;  //add permission constraints
    }

    if (!empty($filters['course'])) {
        if($filters['course'] == SITEID) {  // Really a site
            $filters['site'] = true;
            unset($filters['course']);
        }
    }

    $specificsql = '';
    if(!empty($filters['site'])) {  //view posts for the whole site
        //no constraints to add in this case
    }

    if(!empty($filters['mod'])) {  //only view posts associated with a particular mod
        $context = get_context_instance(CONTEXT_MODULE, $filters['mod']);
        $tables['ba'] = 'blog_association';
        $conditions[] = 'p.id = ba.blogid';
        $conditions[] = 'ba.contextid = '.$context->id;
    }

    if(!empty($filters['course'])) {  // view posts for all members of a course
        $tables['ra'] = 'role_assignments';
        $context = get_context_instance(CONTEXT_COURSE, $filters['course']);

        // MDL-10037, hidden users' blogs should not appear
        if (!has_capability('moodle/role:viewhiddenassigns', $context)) {
            $conditions[] = 'ra.hidden = 0';
        }

        $conditions[] = 'p.userid = ra.userid';
        $conditions[] = 'ra.contextid '.get_related_contexts_string($context);
        if(!empty($CFG->useassoc) && empty($filters['mod'])) {  // only show blog entries associated with this course
            $tables['ba'] = 'blog_association';
            $conditions[] = 'p.id = ba.blogid';
            $conditions[] = 'ba.contextid = '.$context->id;
        }
    }

    if(!empty($filters['group'])) {  // view posts for all members of a group
        $tables['gm'] = 'groups_members';
        $conditions[] = 'p.userid = gm.userid';
        $conditions[] = 'gm.groupid = '.$filters['group'];
        if(!empty($CFG->useassoc)) {  // only show blog entries associated with this course
            $tables['ba'] = 'blog_association';
            $course_context = get_context_instance(CONTEXT_COURSE, $DB->get_field('groups', 'courseid', array('id' => $filters['group'])));
            $conditions[] = 'gm.groupid = '.$filters['group'];
            $conditions[] = 'ba.contextid = '.$course_context->id;
            $conditions[] = 'ba.blogid = p.id';
        }
    }

    if(!empty($filters['user'])) {  // view posts for a single user
        $conditions[] = 'u.id = '.$filters['user'];
    }

    $limitfrom = 0;
    $limitnum = 0;

    if ($fetchstart !== '' && $limit) {
        $limitfrom = $fetchstart;
        $limitnum = $fetchlimit;
    }

    $tablessql = '';  // build up the FROM clause
    foreach($tables as $tablename => $table) {
        $tablessql .= ($tablessql ? ', ' : '').'{'.$table.'} '.$tablename;
    }

    $conditionssql = ''; // build up the WHERE clause
    foreach($conditions as $condition) {
        $conditionssql .= ($conditionssql ? ' AND ' : '').$condition;
        return 'SELECT '.$requiredfields.' FROM '.$tablessql.' WHERE '.$conditionssql.' GROUP BY p.id ORDER BY '. $sort;
    }
}


/**
 * Main filter function
 */
function blog_fetch_entries($fetchlimit=10, $fetchstart='', $filters=array(), $sort='lastmodified DESC', $limit=true) {
    global $DB;
    $limitfrom = 0;
    $limitnum = 0;

    if ($fetchstart !== '' && $limit) {
        $limitfrom = $fetchstart;
        $limitnum = $fetchlimit;
    }

    $SQL = blog_fetch_entries_sql($fetchlimit, $fetchstart, $filters, $sort, $limit);
    $records = $DB->get_records_sql($SQL, array(), $limitfrom, $limitnum);

    if (empty($records)) {
        return array();
    }

    return $records;
}

function blog_get_viewable_entry_count($filters=array()) {
    global $DB;
    //cut out the select statement and the group by and order by statements:
    $chunks = split('(FROM)|(GROUP)', $SQL);  //the middle chunk (id: 1) is the one we want
    //the following groups all rows together, since all 'module' values will be 'blog'
    $SQL = 'SELECT COUNT(*) FROM ' . $chunks[1] . ' GROUP BY module';
    return $DB->count_records_sql($SQL);
}

function blog_get_blogs_url($filters) {
    global $CFG;
    return $CFG->wwwroot.'/blog/index.php?'.
        (empty($filters['course']) ? '' : 'courseid='.$filters['course'].'&amp;').
        (empty($filters['mod']) ? '' : 'modid='.$filters['mod'].'&amp;').
        (empty($filters['group']) ? '' : 'groupid='.$filters['group'].'&amp;').
        (empty($filters['user']) ? '' : 'userid='.$filters['user'].'&amp;').
        (empty($filters['post']) ? '' : 'postid='.$filters['post'].'&amp;').
        (empty($filters['tag']) ? '' : 'tagid='.$filters['tag'].'&amp;').
        (empty($filters['tagtext']) ? '' : 'tag='.$filters['tagtext']);

}

/**
 * Returns a list of all user ids who have used blogs in the site
 * Used in backup of site courses.
 */
function blog_get_participants() {
    global $CFG, $DB;

    return $DB->get_records_sql("SELECT userid AS id
                                   FROM {post}
                                  WHERE module = 'blog' AND courseid = 0");
}

/**
 * add a single association for a blog entry
 * @param int blogid - id of blog post
 */
function blog_add_association($blogid, $contextid, $allow_add_course = true) {
    global $DB;

    $assoc_object = new StdClass;
    $assoc_object->contextid = $contextid;
    $assoc_object->blogid = $blogid;
    $DB->insert_record('blog_association', $assoc_object);
}

/**
 * remove all associations for a blog post
 * @param int blogid - id of the blog post
 */
function blog_remove_associations_for_post($blogid) {
    global $DB;
    $DB->delete_records('blog_association', array('blogid' => $blogid));
}

/**
 * remove all associations for the blog posts of a particular user
 * @param int userid - id of user whose blog associations will be deleted
 */
function blog_remove_associations_for_user($userid) {
     global $DB;
     foreach(blog_fetch_entries(0,0,array('user' => $userid), 'lasmodified DESC', false) as $post) {
         blog_remove_associations_for_post($post->id);
     }
 }

?>
