<?php  // $Id$

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/repository/lib.php');

class blog_edit_form extends moodleform {

    function definition() {
        global $CFG, $COURSE, $USER, $DB;

        $mform    =& $this->_form;

        if(!empty($this->_customdata['assignmentdata'])) {
	        $assignmentdata = $this->_customdata['assignmentdata'];
        }
        
        $existing = $this->_customdata['existing'];
        $sitecontext = $this->_customdata['sitecontext'];
        //determine if content elements should be deactivated for a past due blog assignment
        $noedit = false;
        if (!empty($assignmentdata)) {
            if ((time() > $assignmentdata->timedue and $assignmentdata->preventlate) or $assignmentdata->grade != -1) {
                $noedit = true;
            }
        }


        $mform->addElement('header', 'general', get_string('general', 'form'));

        if ($noedit) { //show disabled form elements, but provide hidden elements so that the data is transferred
            $mform->addElement('text', 'fakesubject', get_string('entrytitle', 'blog'), array('size'=>60, 'disabled'=>'disabled'));
            $mform->addElement('textarea', 'fakesummary', get_string('entrybody', 'blog'), array('rows'=>25, 'cols'=>40, 'disabled'=>'disabled'));
            $mform->setHelpButton('fakesummary', array('writing', 'richtext'), false, 'editorhelpbutton');
            $mform->addElement('hidden', 'subject');
            $mform->addElement('hidden', 'summary');
        } else {  //insert normal form elements
            $mform->addElement('text', 'subject', get_string('entrytitle', 'blog'), 'size="60"');
            $mform->setType('subject', PARAM_TEXT);
            $mform->addRule('subject', get_string('emptytitle', 'blog'), 'required', null, 'client');
            $mform->addElement('htmleditor', 'summary', get_string('entrybody', 'blog'), array('rows'=>25));
            $mform->setType('summary', PARAM_RAW);
            $mform->addRule('summary', get_string('emptybody', 'blog'), 'required', null, 'client');
            $mform->setHelpButton('summary', array('writing', 'richtext2'), false, 'editorhelpbutton');

            $mform->addElement('format', 'format', get_string('format'));

        }

        $mform->addElement('filepicker', 'attachment', get_string('attachment', 'forum'));

        //disable publishstate options that are not allowed
        $publishstates = array();
        $i = 0;
        
        foreach (blog_applicable_publish_states() as $state => $desc) {
            if (!empty($assignmentdata)) {
                if ($i <= $assignmentdata->var2) { //var2 is the maximum publish state allowed
                    $publishstates[$state] = $desc;
                }
            } else {
                $publishstates[$state] = $desc;   //no maximum was set
            }

            $i++;
        }

        $mform->addElement('select', 'publishstate', get_string('publishto', 'blog'), $publishstates);
        $mform->setHelpButton('publishstate', array('publish_state', get_string('publishto', 'blog'), 'blog'));


        if (!empty($CFG->usetags)) {
            $mform->addElement('header', 'tagshdr', get_string('tags', 'tag'));
            $mform->addElement('tags', 'tags', get_string('tags'));
        }

        $allmodnames = array();
        
        if (!empty($CFG->useassoc)) {
            $mform->addElement('header', 'assochdr', get_string('associations', 'blog'));
            $courses = get_my_courses($USER->id, 'visible DESC, fullname ASC');
            $course_names[0] = 'none';
            
            if(!empty($courses)) {
	        
                foreach ($courses as $course) {
                    $course_names[$course->context->id] = $course->fullname;
                    $modinfo = get_fast_modinfo($course, $USER->id);
                    $course_context_path = $DB->get_field('context', 'path', array('id' => $course->context->id));

                    foreach($modinfo->instances as $modname => $instances) {
                        
                        foreach($instances as $modid => $mod) {
                            $mod_context_id = $DB->get_field_select('context', 'id',
                                'instanceid = '.$mod->id.' AND ' .
                                'contextlevel = ' . CONTEXT_MODULE . ' AND ' .
                                'path LIKE \''.$course_context_path.'/%\'');
                            $this->modnames[$course->context->id][$mod_context_id] = $modname . ": ".$mod->name;
                            $allmodnames[$mod_context_id] = $course->shortname . " - " . $modname . ": ".$mod->name;
                        }
                    }
                }
            }
            $mform->addElement('select', 'courseassoc', get_string('course'), $course_names, 'onchange="addCourseAssociations()"');
            $selectassoc = &$mform->addElement('select', 'modassoc', get_string('managemodules'), $allmodnames);
            $selectassoc->setMultiple(true);
        }

        $this->add_action_buttons();

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ACTION);
        $mform->setDefault('action', '');

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        if(!empty($assignmentdata)) {   //dont allow associations for blog assignments
            $courseassoc = $mform->getElement('courseassoc');
            $modassoc = $mform->getElement('modassoc');
            $courseassoc->updateAttributes(array('disabled' => 'disabled'));
            $modassoc->updateAttributes(array('disabled' => 'disabled'));
        }
        
        if($noedit) {  //disable some other fields when editing is not allowed
            $subject = $mform->getElement('subject');
            $summary = $mform->getElement('summary');
            $attachment = $mform->getElement('attachment');
            $format = $mform->getElement('format');
            $attachment->updateAttributes(array('disabled' => 'disabled'));
            $format->updateAttributes(array('disabled' => 'disabled'));
        } 
    } 

    function validation($data, $files) {
        global $CFG, $DB, $USER;

        $errors = array();

        //check to see if it's part of a submitted blog assignment
        $sql = "SELECT a.timedue, a.preventlate, a.emailteachers, a.var2, asub.grade
                FROM {assignment} a, {assignment_submissions} as asub 
                WHERE a.id = asub.assignment AND userid = ? 
                AND a.assignmenttype = 'blog' AND asub.data1 = '?'";

        $blogassignment = $DB->get_record_sql($sql, array($USER->id, $data['id']));

        if ($blogassignment) {

            $original = $DB->get_record('post', array('id' => $data['id']));
            
            //don't allow updates of the sumamry, subject, or attachment
            $changed = ($original->summary != $data['summary'] ||
                        $original->subject != $data['subject'] ||
                        !empty($files)); 

            //send an error if improper changes are being made
            if (($changed and time() > $blogassignment->timedue and $blogassignment->preventlate = 1) or
                ($changed and $blogassignment->grade != -1) or
                (time() < $blogassignment->timedue and ($postaccess > $blogassignment->var2 || $postaccess == -1))) {
                
                //too late to edit this post
                if ($original->subject != $data['subject']) {
                    $errors['subject'] = get_string('canteditblogassignment', 'blog');
                }

                if ($original->summary != $data['summary']) {
                    $errors['summary'] = get_string('canteditblogassignment', 'blog');
                }

                if (!empty($files)) {
                    $errors['attachment'] = get_string('canteditblogassignment', 'blog');
                }
            }

            //insure the publishto value is within proper constraints
            $publishstates = array();
            $postaccess = -1;
            $i=0;
            
            foreach (blog_applicable_publish_states() as $state => $desc) {
                if ($state == $data['publishstate']) {
                    $postaccess = $i;
                }
                $publishstates[$i++] = $state;
            }
            
            if (time() < $blogassignment->timedue and ($postaccess > $blogassignment->var2 || $postaccess == -1)) {
                $errors['publishto'] = get_string('canteditblogassignment', 'blog');
            }
            
        } else {
            if (!$data['courseassoc'] && ($data['publishstate'] == 'course' || $data['publishstate'] == 'group') && !empty($CFG->useassoc)) {
                return array('publishstate' => get_string('mustassociatecourse', 'blog'));
            }
        }


        //validate course association
        if (!empty($data['courseassoc'])) {
            $coursecontext = $DB->get_record('context', array('id' => $data['courseassoc'], 'contextlevel' => CONTEXT_COURSE));
            
            if ($coursecontext)  {    //insure associated course has a valid context id
            //insure the user has access to this course
                if (!has_capability('moodle/course:view', $coursecontext, $USER->id)) {
                    $errors['courseassoc'] = get_string('studentnotallowed', '', fullname($USER, true));
                } else {
                    $errors['courseassoc'] = get_string('invalidcontextid', 'blog');
                } 
            }

            //validate mod associations
            if (!empty($data['modassoc'])) {
                //insure mods are valid 
                foreach ($data['modassoc'] as $modid) {
                    $modcontext = $DB->get_record('context', array('id' => $modid, 'contextlevel' => CONTEXT_MODULE));

                    if ($modcontext) {  //insure associated mod has a valid context id
                        //get context of the mod's course
                        $path = split('/', $modcontext->path);
                        $coursecontext = $DB->get_record('context', array('id' => $path[3]));

                        //insure only one course is associated
                        if (!empty($data['courseassoc'])) {
                            if ($data['courseassoc'] != $coursecontext->id) {
                                $errors['modassoc'] = get_string('onlyassociateonecourse', 'blog');
                            }
                        } else {
                            $data['courseassoc'] = $coursecontext->id;
                        }
                    }

                    //insure the user has access to each mod's course
                    if(!has_capability('moodle/course:view', $coursecontext, $USER->realuser)) {
                        $errors['modassoc'] = get_string('studentnotallowed', '', fullname($USER, true));
                    } else {
                        $errors['modassoc'] = get_string('invalidcontextid', 'blog');
                    }
                }
            }

            if ($errors) {
                return $errors;

            }
            return true;
        }
    } 

    function display() {
        $existing = $this->_customdata['existing'];


        parent::display();

?>
<script type="text/javascript">
<?php
        //add function to clear the list of context associations
?>
function emptyAssocList() {
  var modassoc = document.getElementById('id_modassoc');
  while(modassoc.length > 0) {
    modassoc.remove(0);
  }
}
<?php
        //add function for adding an element to the list of context associations

?>
function addModAssoc(name, id) {
  var modassoc = document.getElementById('id_modassoc');
  newoption = document.createElement('option');
  newoption.text = name;
  newoption.value = id;
  try {
    modassoc.add(newoption, null);  //standard, broken in IE
  } catch(ex) {
  modassoc.add(newoption);
  }
}
<?php
        //add function to add associations for a particular course
?>
function addCourseAssociations() {
  var courses = document.getElementById('id_courseassoc');
  var course = courses.options[courses.selectedIndex].value;
  var modassoc = document.getElementById('id_modassoc');
  var newoption = null;
  emptyAssocList();
  switch(course) {
<?php
        foreach($this->modnames as $course => $coursemods) {
?>
    case '<?php echo addslashes($course)?>':
<?php
            foreach($coursemods as $modid => $modname) {
?>
      addModAssoc('<?php echo addslashes($modname)?>', '<?php echo $modid?>');
<?php
            }
?>
      break;
<?php
      }
?>
  }
}

function select_initial_course() {
  var course = document.getElementById('id_courseassoc');
  var mods = document.getElementById('id_modassoc');
  var i = 0;
  var j = 0;
  emptyAssocList();
<?php if(!empty($existing->courseassoc)) { ?>
  for(i=0; i < course.length; i= i+1) {
    if(course.options[i].value == '<?php echo $existing->courseassoc; ?>') {
      course.selectedIndex = i;
      addCourseAssociations();
      for(j=0; j < mods.length; j=j+1) {
<?php  if(!empty($existing->modassoc)) foreach($existing->modassoc as $modvalue) { ?>
        if(mods.options[j].value == '<?php echo $modvalue; ?>') {
          mods.options[j].selected = true;
        }
<?php  } ?>
      }
    }
  }
<?php } ?>
}

select_initial_course();
</script>
<?php
    }




    /**
     * This function sets up options of otag select element. This is called from definition and also
     * after adding new official tags with the add tag button.
     *
     */
    function otags_select_setup(){
        global $DB;

        $mform =& $this->_form;
        if ($otagsselect =& $mform->getElement('otags')) {
            $otagsselect->removeOptions();
        }
        $namefield = empty($CFG->keeptagnamecase) ? 'name' : 'rawname';
        if ($otags = $DB->get_records_sql_menu("SELECT id, $namefield FROM {tag} WHERE tagtype='official' ORDER by $namefield ASC")) {
            $otagsselect->loadArray($otags);
        }

    }

}
?>
