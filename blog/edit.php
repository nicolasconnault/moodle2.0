<?php //$Id$

require_once('../config.php');
include_once('lib.php');
include_once($CFG->dirroot.'/tag/lib.php');

$action   = required_param('action', PARAM_ALPHA);
$id       = optional_param('id', 0, PARAM_INT);
$confirm  = optional_param('confirm', 0, PARAM_BOOL);
$modid    = optional_param('modid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT); // needed for user tab - does nothing here

require_login($courseid);

if (empty($CFG->bloglevel)) {
    print_error('blogdisable', 'blog');
}

if (isguest()) {
    print_error('noguestpost', 'blog');
}

$sitecontext = get_context_instance(CONTEXT_SYSTEM);
if (!has_capability('moodle/blog:create', $sitecontext) and !has_capability('moodle/blog:manageentries', $sitecontext)) {
    print_error('cannoteditpostorblog');
}

// Make sure that the person trying to edit have access right
if ($id) {
    if (!$existing = $DB->get_record('post', array('id'=>$id))) {
        print_error('wrongpostid', 'blog');
    }

    if (!blog_user_can_edit_post($existing)) {
        print_error('notallowedtoedit', 'blog');
    }
    $userid    = $existing->userid;
    $returnurl = $CFG->wwwroot.'/blog/index.php?userid='.$existing->userid;
} else {
    if (!has_capability('moodle/blog:create', $sitecontext)) {
        print_error('nopost', 'blog'); // manageentries is not enough for adding
    }
    $existing  = false;
    $userid    = $USER->id;
    $returnurl = 'index.php?userid='.$USER->id;
}
if (!empty($courseid)) {
    $returnurl .= '&amp;courseid='.$courseid;
}


$strblogs = get_string('blogs','blog');

if ($action === 'delete'){
    if (!$existing) {
        print_error('wrongpostid', 'blog');
    }
    if (data_submitted() and $confirm and confirm_sesskey()) {
        do_delete($existing);
        redirect($returnurl);
    } else {
        $optionsyes = array('id'=>$id, 'action'=>'delete', 'confirm'=>1, 'sesskey'=>sesskey(), 'courseid'=>$courseid);
        $optionsno = array('userid'=>$existing->userid, 'courseid'=>$courseid);
        print_header("$SITE->shortname: $strblogs", $SITE->fullname);
        blog_print_entry($existing);
        echo '<br />';
        notice_yesno(get_string('blogdeleteconfirm', 'blog'), 'edit.php', 'index.php', $optionsyes, $optionsno, 'post', 'get');
        print_footer();
        die;
    }
}

require_once('edit_form.php');

if(!empty($existing)) {
    $assignmentdata = $DB->get_record_sql('SELECT a.timedue, a.preventlate, a.emailteachers, a.var2, asub.grade
                                                   FROM {assignment} a, {assignment_submissions} as asub WHERE
                                                   a.id = asub.assignment AND userid = '.$USER->id.' AND a.assignmenttype = \'blog\'
                                                   AND asub.data1 = \''.$existing->id.'\'');
}

//add associations
if(!empty($existing)) {
    if ($blogassociations = $DB->get_records('blog_association', array('blogid' => $existing->id))) {
        foreach($blogassociations as $assocrec) {
            $contextrec = $DB->get_record('context', array('id' => $assocrec->contextid));
            switch($contextrec->contextlevel) {
                case CONTEXT_COURSE:
                    $existing->courseassoc = $assocrec->contextid;
                break;
                case CONTEXT_MODULE:
                    $existing->modassoc[] = $assocrec->contextid;
                break;
            }
        }
    }
}

if($action == 'add' and $courseid) {  //pre-select the course for associations
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $existing->courseassoc = $context->id;
}
if($action == 'add' and $modid) { //pre-select the mod for associations
    $context = get_context_instance(CONTEXT_MODULE, $modid);
    $existing->modassoc = array($context->id);
}

$blogeditform = new blog_edit_form(null, compact('existing', 'sitecontext', 'assignmentdata'));

if ($blogeditform->is_cancelled()){
    redirect($returnurl);
} else if ($fromform = $blogeditform->get_data()){
    //save stuff in db
    switch ($action) {
        case 'add':
            do_add($fromform, $blogeditform);
        break;

        case 'edit':
            if (!$existing) {
                print_error('wrongpostid', 'blog');
            }
            do_edit($fromform, $blogeditform);
        break;
        default :
            print_error('invalidaction');
    }
    redirect($returnurl);
}


// gui setup
switch ($action) {
    case 'add':
        // prepare new empty form
        $post->publishstate = 'site';
        $strformheading = get_string('addnewentry', 'blog');
        $post->action       = $action;

        if($courseid) {  //pre-select the course for associations
            $context = get_context_instance(CONTEXT_COURSE, $courseid);
            $post->courseassoc = $context->id;
        }

        if($modid) { //pre-select the mod for associations
            $context = get_context_instance(CONTEXT_MODULE, $modid);
            $post->modassoc = array($context->id);
        }
    break;

    case 'edit':
        if (!$existing) {
            print_error('wrongpostid', 'blog');
        }
        $post->id           = $existing->id;
        $post->subject      = $existing->subject;
        $post->fakesubject  = $existing->subject;
        $post->summary      = $existing->summary;
        $post->fakesummary  = $existing->summary;
        $post->publishstate = $existing->publishstate;
        $post->format       = $existing->format;
        $post->tags = tag_get_tags_array('post', $post->id);
        $post->action       = $action;

        if(!empty($existing->courseassoc)) {
            $post->courseassoc = $existing->courseassoc;
        }

        if(!empty($existing->modassoc)) {
            $post->modassoc = $existing->modassoc;
        }

        $strformheading = get_string('updateentrywithid', 'blog');

    break;
    default :
        print_error('unknowaction');
}

// done here in order to allow deleting of posts with wrong user id above
if (!$user = $DB->get_record('user', array('id'=>$userid))) {
    print_error('invaliduserid');
}
$navlinks = array();
$navlinks[] = array('name' => fullname($user), 'link' => "$CFG->wwwroot/user/view.php?id=$userid", 'type' => 'misc');
$navlinks[] = array('name' => $strblogs, 'link' => "$CFG->wwwroot/blog/index.php?userid=$userid", 'type' => 'misc');
$navlinks[] = array('name' => $strformheading, 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);

print_header("$SITE->shortname: $strblogs", $SITE->fullname, $navigation,'','',true);
$blogeditform->set_data($post);
$blogeditform->display();


print_footer();


die;

/*****************************   edit.php functions  ***************************/

/**
* Delete blog post from database
*/
function do_delete($post) {
    global $returnurl, $DB, $USER;

    //check to see if it's part of a submitted blog assignment
    if($blogassignment = $DB->get_record_sql('SELECT a.timedue, a.preventlate, a.emailteachers, asub.grade
                                          FROM {assignment} a, {assignment_submissions} as asub WHERE
                                          a.id = asub.assignment AND userid = '.$USER->id.' AND a.assignmenttype = \'blog\'
                                          AND asub.data1 = \''.$post->id.'\'')) {
        print_error('cantdeleteblogassignment', 'blog', $returnurl);
    }

    blog_delete_attachments($post);

    $status = $DB->delete_records('post', array('id'=>$post->id));
    tag_set('post', $post->id, array());

    blog_delete_old_attachments($post);

    blog_remove_associations_for_post($post->id);


    add_to_log(SITEID, 'blog', 'delete', 'index.php?userid='. $post->userid, 'deleted blog entry with entry id# '. $post->id);

    if (!$status) {
        print_error('deleteposterror', 'blog', $returnurl);
    }
}

/**
 * Write a new blog entry into database
 */
function do_add($post, $blogeditform) {
    global $CFG, $USER, $returnurl, $DB;

    $post->module       = 'blog';
    $post->userid       = $USER->id;
    $post->lastmodified = time();
    $post->created      = time();

    // Insert the new blog entry.
    if ($post->id = $DB->insert_record('post', $post)) {
        // Add blog attachment
        if ($blogeditform->get_new_filename('attachment')) {
            if ($blogeditform->save_stored_file('attachment', SYSCONTEXTID, 'blog', $post->id, '/', false, $USER->id)) {
                $DB->set_field("post", "attachment", 1, array("id"=>$post->id));
            }
        }

        // Update tags.
        tag_set('post', $post->id, $post->tags);

        if (!empty($CFG->useassoc)) {
            add_associations($post);
        }

        add_to_log(SITEID, 'blog', 'add', 'index.php?userid='.$post->userid.'&postid='.$post->id, $post->subject);

    } else {
        print_error('deleteposterror', 'blog', $returnurl);
    }

}

/**
 * @param . $post argument is a reference to the post object which is used to store information for the form
 * @param . $bloginfo_arg argument is reference to a blogInfo object.
 * @todo complete documenting this function. enable trackback and pingback between entries on the same server
 */
function do_edit($post, $blogeditform) {
    global $CFG, $USER, $returnurl, $DB;

    //check to see if it is a submitted assignment
    if ($blogassignment = $DB->get_record_sql('SELECT a.timedue, a.preventlate, a.emailteachers, a.var2, asi.grade, asi.id
                                          FROM {assignment} a, {assignment_submissions} as asi WHERE
                                          a.id = asi.assignment AND userid = '.$USER->id.' AND a.assignmenttype = \'blog\'
                                          AND asi.data1 = \''.$post->id.'\'')) {

        //email teachers if necessary
        if ($blogassignment->emailteachers) {
            email_teachers($DB->get_record('assignment_submissions', array('id'=>$blogassignment['id'])));
        }

    } else {  //only update the attachment and associations if it is not a submitted assignment
        if (!empty($CFG->useassoc)) {
            add_associations($post);
        }
    }

    $post->lastmodified = time();

    if ($blogeditform->get_new_filename('attachment')) {
        blog_delete_attachments($post);
        if ($blogeditform->save_stored_file('attachment', SYSCONTEXTID, 'blog', $post->id, '/', false, $USER->id)) {
            $post->attachment = 1;
        } else {
            $post->attachment = 1;
        }
    }

    // Update record
    if ($DB->update_record('post', $post)) {
        tag_set('post', $post->id, $post->tags);

        add_to_log(SITEID, 'blog', 'update', 'index.php?userid='.$USER->id.'&postid='.$post->id, $post->subject);

    } else {
        print_error('deleteposterror', 'blog', $returnurl);
    }
}


function add_associations($post) {
    global $DB, $USER;

    $allowaddcourseassoc = true;
    blog_remove_associations_for_post($post->id);

    if (!empty($post->courseassoc)) {
        blog_add_association($post->id, $post->courseassoc);
        $allowaddcourseassoc = false;
    }

    if (!empty($post->modassoc)) {
        foreach($post->modassoc as $modid) {
            blog_add_association($post->id, $modid, $allowaddcourseassoc);
            $allowaddcourseassoc = false;   //let the course be added the first time
        }
    }
}

?>
