<?php // $Id: unmapcourse.php,v 1.3 2008/09/26 06:01:22 dongsheng Exp $
/**
* drops records from feedback_sitecourse_map
*
* @version $Id: unmapcourse.php,v 1.3 2008/09/26 06:01:22 dongsheng Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once($CFG->dirroot.'/mod/feedback/lib.php');

    $id = required_param('id', PARAM_INT);
    $cmapid = required_param('cmapid', PARAM_INT);
    
    if ($id) {
        if (! $cm = get_coursemodule_from_id('feedback', $id)) {
            print_error('invalidcoursemodule');
        }

        if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
            print_error('coursemisconf');
        }
        
        if (! $feedback = $DB->get_record("feedback", array("id"=>$cm->instance))) {
            print_error('invalidcoursemodule');
        }
    }
    $capabilities = feedback_load_capabilities($cm->id);
    
    if (!$capabilities->mapcourse) {
        print_error('invalidaccess');
    }


    // cleanup all lost entries after deleting courses or feedbacks
    feedback_clean_up_sitecourse_map();

    if ($DB->delete_records('feedback_sitecourse_map', array('id'=>$cmapid))) {
        redirect (htmlspecialchars('mapcourse.php?id='.$id));
    } else {
        print_error('cannotunmap', 'feedback');
    }

?>
