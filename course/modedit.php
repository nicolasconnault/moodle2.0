<?php // $Id: modedit.php,v 1.67 2009/05/06 08:43:52 tjhunt Exp $

//  adds or updates modules in a course using new formslib

    require_once("../config.php");
    require_once("lib.php");
    require_once($CFG->libdir.'/filelib.php');
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->libdir.'/completionlib.php');
    require_once($CFG->libdir.'/conditionlib.php');

    $add    = optional_param('add', 0, PARAM_ALPHA);
    $update = optional_param('update', 0, PARAM_INT);
    $return = optional_param('return', 0, PARAM_BOOL); //return to course/view.php if false or mod/modname/view.php if true
    $type   = optional_param('type', '', PARAM_ALPHANUM);

    require_login();

    if (!empty($add)) {
        $section = required_param('section', PARAM_INT);
        $course  = required_param('course', PARAM_INT);

        if (!$course = $DB->get_record('course', array('id'=>$course))) {
            print_error('invalidcourseid');
        }

        require_login($course);
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        require_capability('moodle/course:manageactivities', $context);

        if (!$module = $DB->get_record('modules', array('name'=>$add))) {
            print_error('moduledoesnotexist');
        }

        $cw = get_course_section($section, $course->id);

        if (!course_allowed_module($course, $module->id)) {
            print_error('moduledisable');
        }

        $cm = null;

        $form = new object();
        $form->section          = $section;  // The section number itself - relative!!! (section column in course_sections)
        $form->visible          = $cw->visible;
        $form->course           = $course->id;
        $form->module           = $module->id;
        $form->modulename       = $module->name;
        $form->groupmode        = $course->groupmode;
        $form->groupingid       = $course->defaultgroupingid;
        $form->groupmembersonly = 0;
        $form->instance         = '';
        $form->coursemodule     = '';
        $form->add              = $add;
        $form->return           = 0; //must be false if this is an add, go back to course view on cancel

        if (plugin_supports('mod', $form->modulename, FEATURE_MOD_INTRO, true)) {
            $draftid_editor = file_get_submitted_draft_itemid('introeditor');
            file_prepare_draft_area($draftid_editor, null, null, null);
            $form->introeditor = array('text'=>'', 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default
        }

        // Turn off default grouping for modules that don't provide group mode
        if ($add=='resource' || $add=='glossary' || $add=='label') {
            $form->groupingid = 0;
        }
        
        if (!empty($type)) {
            $form->type = $type;
        }

        $sectionname = get_section_name($course->format);
        $fullmodulename = get_string('modulename', $module->name);

        if ($form->section && $course->format != 'site') {
            $heading->what = $fullmodulename;
            $heading->to   = "$sectionname $form->section";
            $pageheading = get_string('addinganewto', 'moodle', $heading);
        } else {
            $pageheading = get_string('addinganew', 'moodle', $fullmodulename);
        }

        $pagepath = 'mod-' . $module->name . '-';
        if (!empty($type)) {
            $pagepath .= $type;
        } else {
            $pagepath .= 'mod';
        }
        $PAGE->set_pagetype($pagepath);

        $navlinksinstancename = '';

    } else if (!empty($update)) {
        if (!$cm = get_coursemodule_from_id('', $update, 0)) {
            print_error('invalidcoursemodule');
        }

        if (!$course = $DB->get_record('course', array('id'=>$cm->course))) {
            print_error('invalidcourseid');
        }

        require_login($course); // needed to setup proper $COURSE
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        require_capability('moodle/course:manageactivities', $context);

        if (!$module = $DB->get_record('modules', array('id'=>$cm->module))) {
            print_error('moduledoesnotexist');
        }

        if (!$form = $DB->get_record($module->name, array('id'=>$cm->instance))) {
            print_error('moduleinstancedoesnotexist');
        }

        if (!$cw = $DB->get_record('course_sections', array('id'=>$cm->section))) {
            print_error('sectionnotexist');
        }

        $form->coursemodule       = $cm->id;
        $form->section            = $cw->section;  // The section number itself - relative!!! (section column in course_sections)
        $form->visible            = $cm->visible; //??  $cw->visible ? $cm->visible : 0; // section hiding overrides
        $form->cmidnumber         = $cm->idnumber;          // The cm IDnumber
        $form->groupmode          = groups_get_activity_groupmode($cm); // locked later if forced
        $form->groupingid         = $cm->groupingid;
        $form->groupmembersonly   = $cm->groupmembersonly;
        $form->course             = $course->id;
        $form->module             = $module->id;
        $form->modulename         = $module->name;
        $form->instance           = $cm->instance;
        $form->return             = $return;
        $form->update             = $update;
        $form->completion         = $cm->completion;
        $form->completionview     = $cm->completionview;
        $form->completionexpected = $cm->completionexpected;
        $form->completionusegrade = is_null($cm->completiongradeitemnumber) ? 0 : 1;
        if(!empty($CFG->enableavailability)) {
            $form->availablefrom      = $cm->availablefrom;
            $form->availableuntil     = $cm->availableuntil;
            $form->showavailability   = $cm->showavailability;
        }

        if (plugin_supports('mod', $form->modulename, FEATURE_MOD_INTRO, true)) {
            $draftid_editor = file_get_submitted_draft_itemid('introeditor');
            $currentintro = file_prepare_draft_area($draftid_editor, $context->id, $form->modulename.'_intro', 0, true, $form->intro);
            $form->introeditor = array('text'=>$currentintro, 'format'=>$form->introformat, 'itemid'=>$draftid_editor);
        }

        if ($items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>$form->modulename,
                                                 'iteminstance'=>$form->instance, 'courseid'=>$course->id))) {
            // add existing outcomes
            foreach ($items as $item) {
                if (!empty($item->outcomeid)) {
                    $form->{'outcome_'.$item->outcomeid} = 1;
                }
            }

            // set category if present
            $gradecat = false;
            foreach ($items as $item) {
                if ($gradecat === false) {
                    $gradecat = $item->categoryid;
                    continue;
                }
                if ($gradecat != $item->categoryid) {
                    //mixed categories
                    $gradecat = false;
                    break;
                }
            }
            if ($gradecat !== false) {
                // do not set if mixed categories present
                $form->gradecat = $gradecat;
            }
        }

        $sectionname = get_section_name($course->format);
        $fullmodulename = get_string('modulename', $module->name);

        if ($form->section && $course->format != 'site') {
            $heading->what = $fullmodulename;
            $heading->in   = "$sectionname $cw->section";
            $pageheading = get_string('updatingain', 'moodle', $heading);
        } else {
            $pageheading = get_string('updatinga', 'moodle', $fullmodulename);
        }

        $navlinksinstancename = array('name' => format_string($form->name, true), 'link' => "$CFG->wwwroot/mod/$module->name/view.php?id=$cm->id", 'type' => 'activityinstance');

        $pagetype = 'mod-' . $module->name . '-';
        if (!empty($type)) {
            $pagetype .= $type;
        } else {
            $pagetype .= 'mod';
        }
        $PAGE->set_pagetype($pagetype);
    } else {
        print_error('invalidaction');
    }

    $modmoodleform = "$CFG->dirroot/mod/$module->name/mod_form.php";
    if (file_exists($modmoodleform)) {
        require_once($modmoodleform);

    } else {
        print_error('noformdesc');
    }

    $modlib = "$CFG->dirroot/mod/$module->name/lib.php";
    if (file_exists($modlib)) {
        include_once($modlib);
    } else {
        print_error('modulemissingcode', '', '', $modlib);
    }

    $mformclassname = 'mod_'.$module->name.'_mod_form';
    $mform = new $mformclassname($form->instance, $cw->section, $cm);
    $mform->set_data($form);

    if ($mform->is_cancelled()) {
        if ($return && !empty($cm->id)) {
            redirect("$CFG->wwwroot/mod/$module->name/view.php?id=$cm->id");
        } else {
            redirect("$CFG->wwwroot/course/view.php?id=$course->id#section-".$cw->section);
        }

    } else if ($fromform = $mform->get_data()) {
        if (empty($fromform->coursemodule)) { //add
            $cm = null;
            if (!$course = $DB->get_record('course', array('id'=>$fromform->course))) {
                print_error('invalidcourseid');
            }
            $fromform->instance     = '';
            $fromform->coursemodule = '';
        } else { //update
            if (!$cm = get_coursemodule_from_id('', $fromform->coursemodule, 0)) {
                print_error('invalidcoursemodule');
            }

            if (!$course = $DB->get_record('course', array('id'=>$cm->course))) {
                print_error('invalidcourseid');
            }
            $fromform->instance     = $cm->instance;
            $fromform->coursemodule = $cm->id;
        }

        require_login($course); // needed to setup proper $COURSE

        if (!empty($fromform->coursemodule)) {
            $context = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
        }
        require_capability('moodle/course:manageactivities', $context);

        $fromform->course = $course->id;
        $fromform->modulename = clean_param($fromform->modulename, PARAM_SAFEDIR);  // For safety

        $addinstancefunction    = $fromform->modulename."_add_instance";
        $updateinstancefunction = $fromform->modulename."_update_instance";

        if (!isset($fromform->groupingid)) {
            $fromform->groupingid = 0;
        }

        if (!isset($fromform->groupmembersonly)) {
            $fromform->groupmembersonly = 0;
        }

        if (!isset($fromform->name)) { //label
            $fromform->name = $fromform->modulename;
        }
        
        if (!isset($fromform->completion)) {
            $fromform->completion = COMPLETION_DISABLED;
        }
        if (!isset($fromform->completionview)) {
            $fromform->completionview = COMPLETION_VIEW_NOT_REQUIRED;
        }

        // Convert the 'use grade' checkbox into a grade-item number: 0 if
        // checked, null if not
        $fromform->completiongradeitemnumber =
            isset($fromform->completionusegrade) && $fromform->completionusegrade
            ? 0 : null;

        if (!empty($fromform->update)) {

            if (!empty($course->groupmodeforce) or !isset($fromform->groupmode)) {
                $fromform->groupmode = $cm->groupmode; // keep original
            }

            // update course module first
            $cm->groupmode        = $fromform->groupmode;
            $cm->groupingid       = $fromform->groupingid;
            $cm->groupmembersonly = $fromform->groupmembersonly;
            $completion = new completion_info($course);
            if ($completion->is_enabled()) {
                // Handle completion settings. If necessary, wipe existing completion
                // data first.
                if (!empty($fromform->completionunlocked)) {
                    $completion = new completion_info($course);
                    $completion->reset_all_state($cm);
                }

                $cm->completion                = $fromform->completion;
                $cm->completiongradeitemnumber = $fromform->completiongradeitemnumber;
                $cm->completionview            = $fromform->completionview;
                $cm->completionexpected        = $fromform->completionexpected;
            }
            if(!empty($CFG->enableavailability)) {
                $cm->availablefrom             = $fromform->availablefrom;
                $cm->availableuntil            = $fromform->availableuntil;
                // The form time is midnight, but because we want it to be 
                // inclusive, set it to 23:59:59 on that day.
                if ($cm->availableuntil) {
                    $cm->availableuntil = strtotime('23:59:59', 
                        $cm->availableuntil);
                }
                $cm->showavailability          = $fromform->showavailability;
                condition_info::update_cm_from_form($cm,$fromform,true);
            }

            $DB->update_record('course_modules', $cm);

            $modcontext = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
    
            // update embedded links and save files
            if (plugin_supports('mod', $fromform->modulename, FEATURE_MOD_INTRO, true)) {
                $fromform->intro = file_save_draft_area_files($fromform->introeditor['itemid'], $modcontext->id,
                                                              $fromform->modulename.'_intro', 0,
                                                              array('subdirs'=>true), $fromform->introeditor['text']);
                $fromform->introformat = $fromform->introeditor['format'];
                unset($fromform->introeditor);
            }
            
            if (!$updateinstancefunction($fromform, $mform)) {
                print_error('cannotupdatemod', '', 'view.php?id=$course->id', $fromform->modulename);
            }

            // make sure visibility is set correctly (in particular in calendar)
            set_coursemodule_visible($fromform->coursemodule, $fromform->visible);

            if (isset($fromform->cmidnumber)) { //label
                // set cm idnumber - uniqueness is already verified by form validation
                set_coursemodule_idnumber($fromform->coursemodule, $fromform->cmidnumber);
            }

            add_to_log($course->id, "course", "update mod",
                       "../mod/$fromform->modulename/view.php?id=$fromform->coursemodule",
                       "$fromform->modulename $fromform->instance");
            add_to_log($course->id, $fromform->modulename, "update",
                       "view.php?id=$fromform->coursemodule",
                       "$fromform->instance", $fromform->coursemodule);

        } else if (!empty($fromform->add)) {

            if (!empty($course->groupmodeforce) or !isset($fromform->groupmode)) {
                $fromform->groupmode = 0; // do not set groupmode
            }

            if (!course_allowed_module($course, $fromform->modulename)) {
                print_error('moduledisable', '', '', $fromform->modulename);
            }

            // first add course_module record because we need the context
            $newcm = new object();
            $newcm->course           = $course->id;
            $newcm->module           = $fromform->module;
            $newcm->instance         = 0; // not known yet, will be updated later (this is similar to restore code)
            $newcm->visible          = $fromform->visible;
            $newcm->groupmode        = $fromform->groupmode;
            $newcm->groupingid       = $fromform->groupingid;
            $newcm->groupmembersonly = $fromform->groupmembersonly;
            $completion = new completion_info($course);
            if ($completion->is_enabled()) {
                $newcm->completion                = $fromform->completion;
                $newcm->completiongradeitemnumber = $fromform->completiongradeitemnumber;
                $newcm->completionview            = $fromform->completionview;
                $newcm->completionexpected        = $fromform->completionexpected;
            }
            if(!empty($CFG->enableavailability)) {
                $newcm->availablefrom             = $fromform->availablefrom;
                $newcm->availableuntil            = $fromform->availableuntil;
                // The form time is midnight, but because we want it to be 
                // inclusive, set it to 23:59:59 on that day.
                if ($newcm->availableuntil) {
                    $newcm->availableuntil = strtotime('23:59:59', 
                        $newcm->availableuntil);
                }
                $newcm->showavailability          = $fromform->showavailability;
            }

            if (!$fromform->coursemodule = add_course_module($newcm)) {
                print_error('cannotaddcoursemodule');
            }

            $modcontext = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
    
            // update embedded links and save files
            if (plugin_supports('mod', $fromform->modulename, FEATURE_MOD_INTRO, true)) {
                $fromform->intro = file_save_draft_area_files($fromform->introeditor['itemid'], $modcontext->id,
                                                              $fromform->modulename.'_intro', 0,
                                                              array('subdirs'=>true), $fromform->introeditor['text']);
                $fromform->introformat = $fromform->introeditor['format'];
                unset($fromform->introeditor);
            }

            $returnfromfunc = $addinstancefunction($fromform, $mform);

            if (!$returnfromfunc or !is_number($returnfromfunc)) {
                // undo everything we can
                $modcontext = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
                $fs = get_file_storage();
                $fs->delete_area_files($modcontext->id);
                delete_context(CONTEXT_MODULE, $fromform->coursemodule);
                $DB->delete_records('course_modules', array('id'=>$fromform->coursemodule));

                if (!is_number($returnfromfunc)) {
                    print_error('invalidfunction', '', 'view.php?id=$course->id');
                } else {
                    print_error('cannotaddnewmodule', '', "view.php?id=$course->id", $fromform->modulename);
                }
            }

            $fromform->instance = $returnfromfunc;

            if (!$DB->set_field('course_modules', 'instance', $returnfromfunc, array('id'=>$fromform->coursemodule))) {
                print_error('cannotaddcoursemodule');
            }

            // course_modules and course_sections each contain a reference
            // to each other, so we have to update one of them twice.
            if (!$sectionid = add_mod_to_section($fromform)) {
                print_error('cannotaddcmtosection');
            }

            if (!$DB->set_field('course_modules', 'section', $sectionid, array('id'=>$fromform->coursemodule))) {
                print_error('cannotupdatecm');
            }

            // make sure visibility is set correctly (in particular in calendar)
            set_coursemodule_visible($fromform->coursemodule, $fromform->visible);

            if (isset($fromform->cmidnumber)) { //label
                // set cm idnumber - uniqueness is already verified by form validation
                set_coursemodule_idnumber($fromform->coursemodule, $fromform->cmidnumber);
            }

            // Set up conditions
            if ($CFG->enableavailability) {
                condition_info::update_cm_from_form((object)array('id'=>$fromform->coursemodule), $fromform, false);
            }

            add_to_log($course->id, "course", "add mod",
                       "../mod/$fromform->modulename/view.php?id=$fromform->coursemodule",
                       "$fromform->modulename $fromform->instance");
            add_to_log($course->id, $fromform->modulename, "add",
                       "view.php?id=$fromform->coursemodule",
                       "$fromform->instance", $fromform->coursemodule);
        } else {
            print_error('invaliddata');
        }

        // sync idnumber with grade_item
        if ($grade_item = grade_item::fetch(array('itemtype'=>'mod', 'itemmodule'=>$fromform->modulename,
                     'iteminstance'=>$fromform->instance, 'itemnumber'=>0, 'courseid'=>$course->id))) {
            if ($grade_item->idnumber != $fromform->cmidnumber) {
                $grade_item->idnumber = $fromform->cmidnumber;
                $grade_item->update();
            }
        }

        $items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>$fromform->modulename,
                                             'iteminstance'=>$fromform->instance, 'courseid'=>$course->id));

        // create parent category if requested and move to correct parent category
        if ($items and isset($fromform->gradecat)) {
            if ($fromform->gradecat == -1) {
                $grade_category = new grade_category();
                $grade_category->courseid = $course->id;
                $grade_category->fullname = $fromform->name;
                $grade_category->insert();
                if ($grade_item) {
                    $parent = $grade_item->get_parent_category();
                    $grade_category->set_parent($parent->id);
                }
                $fromform->gradecat = $grade_category->id;
            }
            foreach ($items as $itemid=>$unused) {
                $items[$itemid]->set_parent($fromform->gradecat);
                if ($itemid == $grade_item->id) {
                    // use updated grade_item
                    $grade_item = $items[$itemid];
                }
            }
        }

        // add outcomes if requested
        if ($outcomes = grade_outcome::fetch_all_available($course->id)) {
            $grade_items = array();

            // Outcome grade_item.itemnumber start at 1000, there is nothing above outcomes
            $max_itemnumber = 999;
            if ($items) {
                foreach($items as $item) {
                    if ($item->itemnumber > $max_itemnumber) {
                        $max_itemnumber = $item->itemnumber;
                    }
                }
            }

            foreach($outcomes as $outcome) {
                $elname = 'outcome_'.$outcome->id;

                if (array_key_exists($elname, $fromform) and $fromform->$elname) {
                    // so we have a request for new outcome grade item?
                    if ($items) {
                        foreach($items as $item) {
                            if ($item->outcomeid == $outcome->id) {
                                //outcome aready exists
                                continue 2;
                            }
                        }
                    }

                    $max_itemnumber++;

                    $outcome_item = new grade_item();
                    $outcome_item->courseid     = $course->id;
                    $outcome_item->itemtype     = 'mod';
                    $outcome_item->itemmodule   = $fromform->modulename;
                    $outcome_item->iteminstance = $fromform->instance;
                    $outcome_item->itemnumber   = $max_itemnumber;
                    $outcome_item->itemname     = $outcome->fullname;
                    $outcome_item->outcomeid    = $outcome->id;
                    $outcome_item->gradetype    = GRADE_TYPE_SCALE;
                    $outcome_item->scaleid      = $outcome->scaleid;
                    $outcome_item->insert();

                    // move the new outcome into correct category and fix sortorder if needed
                    if ($grade_item) {
                        $outcome_item->set_parent($grade_item->categoryid);
                        $outcome_item->move_after_sortorder($grade_item->sortorder);

                    } else if (isset($fromform->gradecat)) {
                        $outcome_item->set_parent($fromform->gradecat);
                    }
                }
            }
        }

        rebuild_course_cache($course->id);
        grade_regrade_final_grades($course->id);

        if (isset($fromform->submitbutton)) { 
            redirect("$CFG->wwwroot/mod/$module->name/view.php?id=$fromform->coursemodule");
        } else {
            redirect("$CFG->wwwroot/course/view.php?id=$course->id");
        }
        exit;

    } else {
        if (!empty($cm->id)) {
            $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
        }
        require_capability('moodle/course:manageactivities', $context);

        $streditinga = get_string('editinga', 'moodle', $fullmodulename);
        $strmodulenameplural = get_string('modulenameplural', $module->name);

        $navlinks = array();
        $navlinks[] = array('name' => $strmodulenameplural, 'link' => "$CFG->wwwroot/mod/$module->name/index.php?id=$course->id", 'type' => 'activity');
        if ($navlinksinstancename) {
            $navlinks[] = $navlinksinstancename;
        }
        $navlinks[] = array('name' => $streditinga, 'link' => '', 'type' => 'title');

        $navigation = build_navigation($navlinks);

        print_header_simple($streditinga, '', $navigation, $mform->focus(), "", false);

        if (!empty($cm->id)) {
            $context = get_context_instance(CONTEXT_MODULE, $cm->id);
            $overridableroles = get_overridable_roles($context);
            $assignableroles  = get_assignable_roles($context);
            $currenttab = 'update';
            require($CFG->dirroot.'/'.$CFG->admin.'/roles/tabs.php');
        }
        $icon = '<img src="'.$CFG->modpixpath.'/'.$module->name.'/icon.gif" alt=""/>';

        print_heading_with_help($pageheading, 'mods', $module->name, $icon);
        $mform->display();
        print_footer($course);
    }
?>
