<?php
// $Id$
require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->libdir . '/adminlib.php');

// id of repository
$edit    = optional_param('edit', 0, PARAM_INT);
$new     = optional_param('new', '', PARAM_FORMAT);
$hide    = optional_param('hide', 0, PARAM_INT);
$delete  = optional_param('delete', 0, PARAM_INT);
$sure    = optional_param('sure', '', PARAM_ALPHA);
$move    = optional_param('move', '', PARAM_ALPHA);
$type    = optional_param('type', '', PARAM_ALPHA);

$display = true; // fall through to normal display

$pagename = 'repositorycontroller';

if ($delete) {
    $pagename = 'repositorydelete';
} else if ($new) {
    $pagename = 'repositorynew';
}

admin_externalpage_setup($pagename);
require_login(SITEID, false);
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$sesskeyurl = $CFG->wwwroot . '/' . $CFG->admin . '/repositoryinstance.php?sesskey=' . sesskey();
$baseurl    = $CFG->wwwroot . '/admin/repository.php?session='. sesskey() .'&amp;edit=';
if ($new) {
    $baseurl .= $new;
}
else {
    $baseurl .= $type;
}
$configstr  = get_string('managerepositories', 'repository');

$return = true;

if (!empty($edit) || !empty($new)) {
    if (!empty($edit)) {
        $instance = repository_get_instance($edit);
        $instancetype = repository_get_type_by_id($instance->typeid);
        $classname = 'repository_' . $instancetype->get_typename();
        $configs  = $instance->get_instance_option_names();
        $plugin = $instancetype->get_typename();
        $typeid = $instance->typeid;
    } else {
        $plugin = $new;
        $typeid = $new;
        $instance = null;
    }

    // display the edit form for this instance
    $mform = new repository_instance_form('', array('plugin' => $plugin, 'typeid' => $typeid,'instance' => $instance));
    // end setup, begin output

    if ($mform->is_cancelled()){
        redirect($baseurl);
        exit;
    } else if ($fromform = $mform->get_data()){
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', '', $baseurl);
        }
        if ($edit) {
            $settings = array();
            $settings['name'] = $fromform->name;
            foreach($configs as $config) {
                $settings[$config] = $fromform->$config;
            }
            $success = $instance->set_option($settings);
        } else {
            $success = repository_static_function($plugin, 'create', $plugin, 0, get_system_context(), $fromform);
            $data = data_submitted();
        }
        if ($success) {
            $savedstr = get_string('configsaved', 'repository');
            admin_externalpage_print_header();
            print_heading($savedstr);
            redirect($baseurl, $savedstr, 3);
        } else {
            print_error('instancenotsaved', 'repository', $baseurl);
        }
        exit;
    } else {
        admin_externalpage_print_header();
        print_heading(get_string('configplugin', 'repository_'.$plugin));
        print_simple_box_start();
        $mform->display();
        print_simple_box_end();
        $return = false;
    }
} else if (!empty($hide)) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', '', $baseurl);
    }
    $instance = repository_get_type_by_typename($hide);
    var_dump($instance);
    var_dump($hide);
    $instance->hide();
    $return = true;
} else if (!empty($delete)) { 
    admin_externalpage_print_header();
    $instance = repository_get_instance($delete);
    if ($sure) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', '', $baseurl);
        }
        if ($instance->delete()) {
            $deletedstr = get_string('instancedeleted', 'repository');
            print_heading($deletedstr);
            redirect($baseurl, $deletedstr, 3);
        } else {
            print_error('instancenotdeleted', 'repository', $baseurl);
        }
        exit;
    }
    notice_yesno(get_string('confirmdelete', 'repository', $instance->name), $sesskeyurl . '&amp;type=' . $type . '&amp;delete=' . $delete . '&amp;sure=yes', $CFG->wwwroot . '/admin/repositoryinstance.php?session='. sesskey());
    $return = false;
}

if (!empty($return)) {
    
    redirect($baseurl);
}
admin_externalpage_print_footer();