<?php // $Id: mod_form.php,v 1.15 2009/04/22 07:14:19 skodak Exp $
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_label_mod_form extends moodleform_mod {

    function definition() {

        $mform    =& $this->_form;

        $this->add_intro_editor(true, get_string('labeltext', 'label'));

        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
// buttons
        $this->add_action_buttons(true, false, null);

    }

}
