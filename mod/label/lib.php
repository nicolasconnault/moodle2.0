<?php  // $Id: lib.php,v 1.29 2009/04/22 07:14:20 skodak Exp $

/// Library of functions and constants for module label


define("LABEL_MAX_NAME_LENGTH", 50);

function get_label_name($label) {
    $textlib = textlib_get_instance();

    $name = strip_tags(format_string($label->intro,true));
    if ($textlib->strlen($name) > LABEL_MAX_NAME_LENGTH) {
        $name = $textlib->substr($name, 0, LABEL_MAX_NAME_LENGTH)."...";
    }

    if (empty($name)) {
        // arbitrary name
        $name = get_string('modulename','label');
    }

    return $name;
}

function label_add_instance($label) {
    global $DB;
/// Given an object containing all the necessary data, 
/// (defined by the form in mod_form.php) this function 
/// will create a new instance and return the id number 
/// of the new instance.

    $label->name = get_label_name($label);
    $label->timemodified = time();

    return $DB->insert_record("label", $label);
}


function label_update_instance($label) {
    global $DB;
/// Given an object containing all the necessary data, 
/// (defined by the form in mod_form.php) this function 
/// will update an existing instance with new data.

    $label->name = get_label_name($label);
    $label->timemodified = time();
    $label->id = $label->instance;

    return $DB->update_record("label", $label);
}


function label_delete_instance($id) {
    global $DB;
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  

    if (! $label = $DB->get_record("label", array("id"=>$id))) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records("label", array("id"=>$label->id))) {
        $result = false;
    }

    return $result;
}

function label_get_participants($labelid) {
//Returns the users with data in one resource
//(NONE, but must exist on EVERY mod !!)

    return false;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 */
function label_get_coursemodule_info($coursemodule) {
    global $DB;

    if ($label = $DB->get_record('label', array('id'=>$coursemodule->instance), 'id, name, intro, introformat')) {
        if (empty($label->name)) {
            // label name missing, fix it
            $label->name = "label{$label->id}";
            $DB->set_field('label', 'name', $label->name, array('id'=>$label->id));
        }
        $info = new object();
        // no filtering hre because this info is cached and filtered later
        $info->extra = urlencode(format_module_intro('label', $label, $coursemodule->id, false));
        $info->name  = urlencode($label->name);
        return $info;
    } else {
        return null;
    }
}

function label_get_view_actions() {
    return array();
}

function label_get_post_actions() {
    return array();
}

function label_get_types() {
    $types = array();

    $type = new object();
    $type->modclass = MOD_CLASS_RESOURCE;
    $type->type = "label";
    $type->typestr = get_string('resourcetypelabel', 'resource');
    $types[] = $type;

    return $types;
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function label_reset_userdata($data) {
    return array();
}

/**
 * Returns all other caps used in module
 */
function lable_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function label_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:                return false;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;

        default: return null;
    }
}

?>
