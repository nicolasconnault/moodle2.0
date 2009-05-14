<?php  //$Id: export_form.php,v 1.6 2008/07/31 23:03:51 skodak Exp $

require_once($CFG->libdir.'/formslib.php');

class question_export_form extends moodleform {

    function definition() {
        $mform    =& $this->_form;

        $defaultcategory   = $this->_customdata['defaultcategory'];
        $contexts   = $this->_customdata['contexts'];
        $defaultfilename = $this->_customdata['defaultfilename'];
//--------------------------------------------------------------------------------
        $mform->addElement('header','fileformat',get_string('fileformat','quiz'));
        $fileformatnames = get_import_export_formats('export');
        $radioarray = array();
        foreach ($fileformatnames as $shortname => $fileformatname) {
            $radioelement = &MoodleQuickForm::createElement('radio','format','',$fileformatname,$shortname);
            $radioelement->setHelpButton(array("$shortname",$fileformatname,"qformat_$shortname"));
            $radioarray[] = $radioelement;
        }
        $mform->addGroup($radioarray,'format','',array('<br />'),false);
        $mform->addRule('format',null,'required',null,'client'); 

//--------------------------------------------------------------------------------
        $mform->addElement('header','general', get_string('general', 'form'));

        $mform->addElement('questioncategory', 'category', get_string('category','quiz'), compact('contexts'));
        $mform->setDefault('category', $defaultcategory);
        $mform->setHelpButton('category', array('exportcategory', get_string('exportcategory','question'), 'quiz'));

        $categorygroup = array();
        $categorygroup[] =& $mform->createElement('checkbox', 'cattofile', '', get_string('tofilecategory', 'question'));
        $categorygroup[] =& $mform->createElement('checkbox', 'contexttofile', '', get_string('tofilecontext', 'question'));
        $mform->addGroup($categorygroup, 'categorygroup', '', '', false);
        $mform->disabledIf('categorygroup', 'cattofile', 'notchecked');
        $mform->setDefault('cattofile', 1);
        $mform->setDefault('contexttofile', 1);
        

//        $fileformatnames = get_import_export_formats('export');
//        $mform->addElement('select', 'format', get_string('fileformat','quiz'), $fileformatnames);
//        $mform->setDefault('format', 'gift');
//        $mform->setHelpButton('format', array('export', get_string('exportquestions', 'quiz'), 'quiz'));

        $mform->addElement('text', 'exportfilename', get_string('exportname', 'quiz'), array('size'=>40));
        $mform->setDefault('exportfilename', $defaultfilename);
        $mform->setType('exportfilename', PARAM_FILE);

        // set a template for the format select elements   
        $renderer =& $mform->defaultRenderer();
        $template = "{help} {element}\n";
        $renderer->setGroupElementTemplate($template, 'format');

//--------------------------------------------------------------------------------
        $this->add_action_buttons(false, get_string('exportquestions', 'quiz'));
//--------------------------------------------------------------------------------
    }
}
?>
