<?php // $Id: export.php,v 1.4 2008/09/25 08:47:55 dongsheng Exp $
/**
* prints the form to export the items as xml-file
*
* @version $Id: export.php,v 1.4 2008/09/25 08:47:55 dongsheng Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");

    // get parameters
    $id = required_param('id', PARAM_INT); 
    $action = optional_param('action', false, PARAM_ALPHA);

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

    require_login($course->id, true, $cm);
    
    if(!$capabilities->edititems){
        print_error('invalidaction');
    }
    
    if ($action == 'exportfile') {
        if(!$exportdata = feedback_get_xml_data($feedback->id)) {
            print_error('nodata');
        }
        @feedback_send_xml_data($exportdata, 'feedback_'.$feedback->id.'.xml');
        exit;
    }

    redirect('view.php?id='.$id);
    exit;
  
    function feedback_get_xml_data($feedbackid) {
        global $DB;

        $space = '     ';
        //get all items of the feedback
        if(!$items = $DB->get_records('feedback_item', array('feedback'=>$feedbackid), 'position')) {
            return false;
        }
        
        //writing the header of the xml file including the charset of the currrent used language
        $data = '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
        $data .= '<FEEDBACK VERSION="200701" COMMENT="XML-Importfile for mod/feedback">'."\n";
        $data .= $space.'<ITEMS>'."\n";
        
        //writing all the items
        foreach($items as $item) {
            //start of item
            $data .= $space.$space.'<ITEM TYPE="'.$item->typ.'" REQUIRED="'.$item->required.'">'."\n";
            
            //start of itemtext
            $data .= $space.$space.$space.'<ITEMTEXT>'."\n";
            //start of CDATA
            $data .= $space.$space.$space.$space.'<![CDATA[';
            $data .= $item->name;
            //end of CDATA
            $data .= ']]>'."\n";
            //end of itemtext
            $data .= $space.$space.$space.'</ITEMTEXT>'."\n";
            
            //start of presentation
            $data .= $space.$space.$space.'<PRESENTATION>'."\n";
            //start of CDATA
            $data .= $space.$space.$space.$space.'<![CDATA[';
            $data .= $item->presentation;
            //end of CDATA
            $data .= ']]>'."\n";
            //end of presentation
            $data .= $space.$space.$space.'</PRESENTATION>'."\n";
            
            //end of item
            $data .= $space.$space.'</ITEM>'."\n";
        }
        
        //writing the footer of the xml file
        $data .= $space.'</ITEMS>'."\n";
        $data .= '</FEEDBACK>'."\n";
        
        return $data;
    }
    
    function feedback_send_xml_data($data, $filename) {
        $charset = get_string('thischarset');
        @header('Content-Type: application/xml; charset=UTF-8');
        @header('Content-Disposition: attachment; filename='.$filename);
        print($data);
    }
?>
