<?php // $Id: delete_item_form.php,v 1.1 2008/04/23 09:33:55 moodler Exp $
/**
* prints the form to confirm delete a completed
*
* @version $Id: delete_item_form.php,v 1.1 2008/04/23 09:33:55 moodler Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

require_once $CFG->libdir.'/formslib.php';

class mod_feedback_delete_item_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        //headline
        //$mform->addElement('header', 'general', '');
        
        // hidden elements
        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'deleteitem');
        $mform->addElement('hidden', 'confirmdelete');

        //-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons(true, get_string('yes'));

    }
}
?>
