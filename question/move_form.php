<?php  //$Id: move_form.php,v 1.2 2007/08/09 22:44:16 jamiesensei Exp $

require_once($CFG->libdir.'/formslib.php');

class question_move_form extends moodleform {

    function definition() {
        $mform    =& $this->_form;

        $currentcat   = $this->_customdata['currentcat'];
        $contexts   = $this->_customdata['contexts'];
//--------------------------------------------------------------------------------

        $mform->addElement('questioncategory', 'category', get_string('category','quiz'), compact('contexts', 'currentcat'));


//--------------------------------------------------------------------------------
        $this->add_action_buttons(true, get_string('categorymoveto', 'quiz'));
//--------------------------------------------------------------------------------
        $mform->addElement('hidden', 'delete', $currentcat);
    }
}
?>
