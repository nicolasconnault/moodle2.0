<?php // $Id: edit_form.php,v 1.18 2009/05/11 18:55:03 skodak Exp $
require_once ($CFG->dirroot.'/lib/formslib.php');

class mod_glossary_entry_form extends moodleform {

    function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $currententry      = $this->_customdata['current'];
        $glossary          = $this->_customdata['glossary'];
        $cm                = $this->_customdata['cm'];
        $definitionoptions = $this->_customdata['definitionoptions'];
        $attachmentoptions = $this->_customdata['attachmentoptions'];

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'concept', get_string('concept', 'glossary'));
        $mform->setType('concept', PARAM_TEXT);
        $mform->addRule('concept', null, 'required', null, 'client');

        $mform->addElement('editor', 'definition_editor', get_string('definition', 'glossary'), null, $definitionoptions);
        $mform->setType('definition_editor', PARAM_RAW);
        $mform->addRule('definition_editor', get_string('required'), 'required', null, 'client');

        if ($categories = $DB->get_records_menu('glossary_categories', array('glossaryid'=>$glossary->id), 'name ASC', 'id, name')){
            $categories = array(0 => get_string('notcategorised', 'glossary')) + $categories;
            $categoriesEl = $mform->addElement('select', 'categories', get_string('categories', 'glossary'), $categories);
            $categoriesEl->setMultiple(true);
            $categoriesEl->setSize(5);
        }

        $mform->addElement('textarea', 'aliases', get_string('aliases', 'glossary'), 'rows="2" cols="40"');
        $mform->setType('aliases', PARAM_TEXT);
        $mform->setHelpButton('aliases', array('aliases2', strip_tags(get_string('aliases', 'glossary')), 'glossary'));

        $mform->addElement('filemanager', 'attachment_filemanager', get_string('attachment', 'glossary'), null, $attachmentoptions);
        $mform->setHelpButton('attachment_filemanager', array('attachment2', get_string('attachment', 'glossary'), 'glossary'));

        if (!$glossary->usedynalink) {
            $mform->addElement('hidden', 'usedynalink',   $CFG->glossary_linkentries);
            $mform->addElement('hidden', 'casesensitive', $CFG->glossary_casesensitive);
            $mform->addElement('hidden', 'fullmatch',     $CFG->glossary_fullmatch);

        } else {
//-------------------------------------------------------------------------------
            $mform->addElement('header', 'linkinghdr', get_string('linking', 'glossary'));

            $mform->addElement('checkbox', 'usedynalink', get_string('entryusedynalink', 'glossary'));
            $mform->setHelpButton('usedynalink', array('usedynalinkentry', strip_tags(get_string('usedynalink', 'glossary')), 'glossary'));
            $mform->setDefault('usedynalink', $CFG->glossary_linkentries);

            $mform->addElement('checkbox', 'casesensitive', get_string('casesensitive', 'glossary'));
            $mform->setHelpButton('casesensitive', array('casesensitive', strip_tags(get_string('casesensitive', 'glossary')), 'glossary'));
            $mform->disabledIf('casesensitive', 'usedynalink');
            $mform->setDefault('casesensitive', $CFG->glossary_casesensitive);

            $mform->addElement('checkbox', 'fullmatch', get_string('fullmatch', 'glossary'));
            $mform->setHelpButton('fullmatch', array('fullmatch', strip_tags(get_string('fullmatch', 'glossary')), 'glossary'));
            $mform->disabledIf('fullmatch', 'usedynalink');
            $mform->setDefault('fullmatch', $CFG->glossary_fullmatch);
        }

        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'cmid');

//-------------------------------------------------------------------------------
        $this->add_action_buttons();

//-------------------------------------------------------------------------------
        $this->set_data($currententry);
    }

    function validation($data, $files) {
        global $CFG, $USER, $DB;
        $errors = parent::validation($data, $files);

        $glossary = $this->_customdata['glossary'];
        $cm       = $this->_customdata['cm'];
        $context  = get_context_instance(CONTEXT_MODULE, $cm->id);

        $id = (int)$data['id'];
        $data['concept'] = trim($data['concept']);

        if ($id) {
            //We are updating an entry, so we compare current session user with
            //existing entry user to avoid some potential problems if secureforms=off
            //Perhaps too much security? Anyway thanks to skodak (Bug 1823)
            $old = $DB->get_record('glossary_entries', array('id'=>$id));
            $ineditperiod = ((time() - $old->timecreated <  $CFG->maxeditingtime) || $glossary->editalways);
            if ((!$ineditperiod || $USER->id != $old->userid) and !has_capability('mod/glossary:manageentries', $context)) {
                if ($USER->id != $old->userid) {
                    $errors['concept'] = get_string('errcannoteditothers', 'glossary');
                } elseif (!$ineditperiod) {
                    $errors['concept'] = get_string('erredittimeexpired', 'glossary');
                }
            }
            if (!$glossary->allowduplicatedentries) {
                if ($dupentries = $DB->get_records('glossary_entries', array('LOWER(concept)'=>moodle_strtolower($data['concept'])))) {
                    foreach ($dupentries as $curentry) {
                        if ($glossary->id == $curentry->glossaryid) {
                           if ($curentry->id != $id) {
                               $errors['concept'] = get_string('errconceptalreadyexists', 'glossary');
                               break;
                           }
                        }
                    }
                }
            }

        } else {
            if (!$glossary->allowduplicatedentries) {
                if ($dupentries = $DB->get_record('glossary_entries', array('LOWER(concept)'=>moodle_strtolower($data['concept']), 'glossaryid'=>$glossary->id))) {
                    $errors['concept'] = get_string('errconceptalreadyexists', 'glossary');
                }
            }
        }

        return $errors;
    }
}
?>
