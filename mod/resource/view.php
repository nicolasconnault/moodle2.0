<?php  // $Id: view.php,v 1.66 2009/05/08 09:00:42 tjhunt Exp $

    require_once("../../config.php");
    require_once("lib.php");
    require_once($CFG->libdir . '/completionlib.php');
 
    $id = optional_param('id', 0, PARAM_INT);    // Course Module ID
    $r  = optional_param('r', 0, PARAM_INT);  // Resource

    if ($r) {  // Two ways to specify the resource
        if (! $resource = $DB->get_record('resource', array('id'=>$r))) {
            print_error('invalidid', 'resource');
        }

        if (! $cm = get_coursemodule_from_instance('resource', $resource->id, $resource->course)) {
            print_error('invalidcoursemodule');
        }

    } else if ($id) {
        if (! $cm = get_coursemodule_from_id('resource', $id)) {
            print_error('invalidcoursemodule');
        }

        if (! $resource = $DB->get_record('resource', array('id'=>$cm->instance))) {
            print_error('invalidid', 'resource');
        }
    } else {
        print_error('invalidaccessparameter');
    }

    if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
        print_error('invalidcourseid');
    }

    require_course_login($course, true, $cm);

    require ($CFG->dirroot.'/mod/resource/type/'.$resource->type.'/resource.class.php');
    $resourceclass = 'resource_'.$resource->type;
    $resourceinstance = new $resourceclass($cm->id);

    // Mark activity viewed before we display it because some resource types
    // do not return from display()
    $completion=new completion_info($course);
    $completion->set_module_viewed($cm);

    $resourceinstance->display();
?>
