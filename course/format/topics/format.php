<?php // $Id: format.php,v 1.97 2009/05/06 08:59:31 tjhunt Exp $
      // Display the whole course as "topics" made of of modules
      // Included from "view.php"
/**
 * Evaluation topics format for course display - NO layout tables, for accessibility, etc.
 * 
 * A duplicate course format to enable the Moodle development team to evaluate 
 * CSS for the multi-column layout in place of layout tables. 
 * Less risk for the Moodle 1.6 beta release.
 *   1. Straight copy of topics/format.php
 *   2. Replace <table> and <td> with DIVs; inline styles.
 *   3. Reorder columns so that in linear view content is first then blocks;
 * styles to maintain original graphical (side by side) view.
 *
 * Target: 3-column graphical view using relative widths for pixel screen sizes 
 * 800x600, 1024x768... on IE6, Firefox. Below 800 columns will shift downwards.
 * 
 * http://www.maxdesign.com.au/presentation/em/ Ideal length for content.
 * http://www.svendtofte.com/code/max_width_in_ie/ Max width in IE.
 *
 * @copyright &copy; 2006 The Open University
 * @author N.D.Freear@open.ac.uk, and others.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package  
 */
//TODO (nfreear): Accessibility: evaluation, lang/en_utf8/moodle.php: $string['formattopicscss']

    require_once($CFG->libdir.'/ajax/ajaxlib.php');
    require_once($CFG->libdir.'/filelib.php');

    $topic = optional_param('topic', -1, PARAM_INT);

    if ($topic != -1) {
        $displaysection = course_set_display($course->id, $topic);
    } else {
        if (isset($USER->display[$course->id])) {
            $displaysection = $USER->display[$course->id];
        } else {
            $displaysection = course_set_display($course->id, 0);
        }
    }

    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    if (($marker >=0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
	$course->marker = $marker;
	if (! $DB->set_field("course", "marker", $marker, array("id"=>$course->id))) {
	    print_error("cannotmarktopic");
	}
    }

    $streditsummary  = get_string('editsummary');
    $stradd          = get_string('add');
    $stractivities   = get_string('activities');
    $strshowalltopics = get_string('showalltopics');
    $strtopic         = get_string('topic');
    $strgroups       = get_string('groups');
    $strgroupmy      = get_string('groupmy');
    $editing         = $PAGE->user_is_editing();

    if ($editing) {
        $strtopichide = get_string('hidetopicfromothers');
        $strtopicshow = get_string('showtopicfromothers');
        $strmarkthistopic = get_string('markthistopic');
        $strmarkedthistopic = get_string('markedthistopic');
        $strmoveup   = get_string('moveup');
        $strmovedown = get_string('movedown');
    }

/* Internet Explorer min-width fix. (See theme/standard/styles_layout.css: min-width for Firefox.)
   Window width: 800px, Firefox 763px, IE 752px. (Window width: 640px, Firefox 602px, IE 588px.)    
*/
?>

<!--[if IE]>
  <style type="text/css">
  .topics-format { width: expression(document.body.clientWidth < 800 ? "752px" : "auto"); }
  </style>
<![endif]-->
<?php
/// Layout the whole page as three big columns (was, id="layout-table")
    echo '<div class="topics-format">';

/// The left column ...

    if (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing) {
        echo '<div id="left-column">';
        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
        print_container_end();
        echo '</div>';
    }
    
/// The right column, BEFORE the middle-column.
    if (blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $editing) {
        echo '<div id="right-column">';
        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
        print_container_end();
        echo '</div>';
    }

/// Start main column
    echo '<div id="middle-column">';
    print_container_start();

    echo skip_main_destination();

    print_heading_block(get_string('topicoutline'), 'outline');

    // Note, an ordered list would confuse - "1" could be the clipboard or summary.
    echo "<ul class='topics'>\n";

/// If currently moving a file then show the current clipboard
    if (ismoving($course->id)) {
        $stractivityclipboard = strip_tags(get_string('activityclipboard', '', $USER->activitycopyname));
        $strcancel= get_string('cancel');
        echo '<li class="clipboard">';
        echo $stractivityclipboard.'&nbsp;&nbsp;(<a href="mod.php?cancelcopy=true&amp;sesskey='.sesskey().'">'.$strcancel.'</a>)';
        echo "</li>\n";
    }

/// Print Section 0 with general activities

    $section = 0;
    $thissection = $sections[$section];

    if ($thissection->summary or $thissection->sequence or $PAGE->user_is_editing()) {

        // Note, no need for a 'left side' cell or DIV.
        // Note, 'right side' is BEFORE content.
        echo '<li id="section-0" class="section main" >';
	echo '<div class="left side">&nbsp;</div>';
        echo '<div class="right side" >&nbsp;</div>';        
        echo '<div class="content">';
        echo '<div class="summary">';

        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course_section', $thissection->id);
        $summaryformatoptions = new object();
        $summaryformatoptions->noclean = true;
        echo format_text($summarytext, FORMAT_HTML, $summaryformatoptions);

        if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $coursecontext)) {
            echo '<a title="'.$streditsummary.'" '.
                 ' href="editsection.php?id='.$thissection->id.'"><img src="'.$CFG->pixpath.'/t/edit.gif" '.
                 ' class="icon edit" alt="'.$streditsummary.'" /></a>';
        }
        echo '</div>';
        
        print_section($course, $thissection, $mods, $modnamesused);

        if ($PAGE->user_is_editing()) {
            print_section_add_menus($course, $section, $modnames);
        }

        echo '</div>';
        echo "</li>\n";
    }


/// Now all the normal modules by topic
/// Everything below uses "section" terminology - each "section" is a topic.

    $timenow = time();
    $section = 1;
    $sectionmenu = array();

    while ($section <= $course->numsections) {

        if (!empty($sections[$section])) {
            $thissection = $sections[$section];

        } else {
            unset($thissection);
            $thissection->course  = $course->id;   // Create a new section structure
            $thissection->section = $section;
            $thissection->summary  = '';
            $thissection->visible  = 1;
            if (!$thissection->id = $DB->insert_record('course_sections', $thissection)) {
                notify('Error inserting new topic!');
            }
        }

        $showsection = (has_capability('moodle/course:viewhiddensections', $context) or $thissection->visible or !$course->hiddensections);

        if (!empty($displaysection) and $displaysection != $section) {  // Check this topic is visible
            if ($showsection) {
		$strsummary = strip_tags(format_string($thissection->summary,true));
		if (strlen($strsummary) < 57) {
		    $strsummary = ' - '.$strsummary;
		} else {
		    $strsummary = ' - '.substr($strsummary, 0, 60).'...';
		}
                $sectionmenu['topic='.$section] = s($section.$strsummary);
            }
            $section++;
            continue;
        }

        if ($showsection) {

            $currenttopic = ($course->marker == $section);

            $currenttext = '';
            if (!$thissection->visible) {
                $sectionstyle = ' hidden';
            } else if ($currenttopic) {
                $sectionstyle = ' current';
                $currenttext = get_accesshide(get_string('currenttopic','access'));
            } else {
                $sectionstyle = '';
            }

            echo '<li id="section-'.$section.'" class="section main'.$sectionstyle.'" >'; //'<div class="left side">&nbsp;</div>';

	        echo '<div class="left side">'.$currenttext.$section.'</div>';
            // Note, 'right side' is BEFORE content.
            echo '<div class="right side">';
            
            if ($displaysection == $section) {	// Show the zoom boxes
                echo '<a href="view.php?id='.$course->id.'&amp;topic=0#section-'.$section.'" title="'.$strshowalltopics.'">'.
                     '<img src="'.$CFG->pixpath.'/i/all.gif" class="icon" alt="'.$strshowalltopics.'" /></a><br />';
            } else {
                $strshowonlytopic = get_string("showonlytopic", "", $section);
                echo '<a href="view.php?id='.$course->id.'&amp;topic='.$section.'" title="'.$strshowonlytopic.'">'.
                     '<img src="'.$CFG->pixpath.'/i/one.gif" class="icon" alt="'.$strshowonlytopic.'" /></a><br />';
            }

            if ($PAGE->user_is_editing() && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {

		if ($course->marker == $section) {  // Show the "light globe" on/off
               	    echo '<a href="view.php?id='.$course->id.'&amp;marker=0&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strmarkedthistopic.'">'.'<img src="'.$CFG->pixpath.'/i/marked.gif" alt="'.$strmarkedthistopic.'" /></a><br />';
            	} else {
                    echo '<a href="view.php?id='.$course->id.'&amp;marker='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strmarkthistopic.'">'.'<img src="'.$CFG->pixpath.'/i/marker.gif" alt="'.$strmarkthistopic.'" /></a><br />';
            	}

                if ($thissection->visible) {        // Show the hide/show eye
                    echo '<a href="view.php?id='.$course->id.'&amp;hide='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strtopichide.'">'.
                         '<img src="'.$CFG->pixpath.'/i/hide.gif" class="icon hide" alt="'.$strtopichide.'" /></a><br />';
                } else {
                    echo '<a href="view.php?id='.$course->id.'&amp;show='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strtopicshow.'">'.
                         '<img src="'.$CFG->pixpath.'/i/show.gif" class="icon hide" alt="'.$strtopicshow.'" /></a><br />';
                }
                if ($section > 1) {                       // Add a arrow to move section up
                    echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=-1&amp;sesskey='.sesskey().'#section-'.($section-1).'" title="'.$strmoveup.'">'.
                         '<img src="'.$CFG->pixpath.'/t/up.gif" class="icon up" alt="'.$strmoveup.'" /></a><br />';
                }

                if ($section < $course->numsections) {    // Add a arrow to move section down
                    echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=1&amp;sesskey='.sesskey().'#section-'.($section+1).'" title="'.$strmovedown.'">'.
                         '<img src="'.$CFG->pixpath.'/t/down.gif" class="icon down" alt="'.$strmovedown.'" /></a><br />';
                }
            }
            echo '</div>';

            echo '<div class="content">';
            if (!has_capability('moodle/course:viewhiddensections', $context) and !$thissection->visible) {   // Hidden for students
		echo get_string('notavailable').'</div>';
	    } else {
                echo '<div class="summary">';
                $summaryformatoptions->noclean = true;
		if ($thissection->summary) {
                   echo format_text($thissection->summary, FORMAT_HTML, $summaryformatoptions);
		} else {
		   echo '&nbsp;';
 	   	}

                if ($PAGE->user_is_editing() && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
                    echo ' <a title="'.$streditsummary.'" href="editsection.php?id='.$thissection->id.'">'.
                         '<img src="'.$CFG->pixpath.'/t/edit.gif" class="icon edit" alt="'.$streditsummary.'" /></a><br /><br />';
                }
                echo '</div>';

                print_section($course, $thissection, $mods, $modnamesused);

                if ($PAGE->user_is_editing()) {
                    print_section_add_menus($course, $section, $modnames);
                }
            }

            echo '</div>';
            echo "</li>\n";
        }

        $section++;
    }
    echo "</ul>\n";

    if (!empty($sectionmenu)) {
        echo '<div class="jumpmenu">';
        echo popup_form($CFG->wwwroot.'/course/view.php?id='.$course->id.'&amp;', $sectionmenu,
                   'sectionmenu', '', get_string('jumpto'), '', '', true);
        echo '</div>';
    }

    print_container_end();
    echo '</div>';

    echo '</div>';
    echo '<div class="clearer"></div>';

?>
