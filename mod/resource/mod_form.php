<?php
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_resource_mod_form extends moodleform_mod {
    var $_resinstance;

    function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        // this hack is needed for different settings of each subtype
        if (!empty($this->_instance)) {
            if($res = $DB->get_record('resource', array('id'=>$this->_instance))) {
                $type = $res->type;
            } else {
                print_error('invalidassignment', 'resource');
            }
        } else {
            $type = required_param('type', PARAM_ALPHA);
        }
        $mform->addElement('hidden', 'type', $type);
        $mform->setDefault('type', $type);

        require($CFG->dirroot.'/mod/resource/type/'.$type.'/resource.class.php');
        $resclass = 'resource_'.$type;
        $this->_resinstance = new $resclass();

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(false);

        $mform->addElement('header', 'typedesc', resource_get_name($type));
        $this->_resinstance->setup_elements($mform);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values){
        $this->_resinstance->setup_preprocessing($default_values);
    }

}
?>
