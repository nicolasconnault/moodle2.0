<?php // $Id: delete.php,v 1.10 2008/10/09 11:07:34 mudrd8mz Exp $
/**
 * Action for deleting a page
 *
 * @version $Id: delete.php,v 1.10 2008/10/09 11:07:34 mudrd8mz Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/
    confirm_sesskey();

    $pageid = required_param('pageid', PARAM_INT);
    if (!$thispage = $DB->get_record("lesson_pages", array("id" => $pageid))) {
        print_error("Delete: page record not found");
    }

    // first delete all the associated records...
    $DB->delete_records("lesson_attempts", array("pageid" => $pageid));
    // ...now delete the answers...
    $DB->delete_records("lesson_answers", array("pageid" => $pageid));
    // ..and the page itself
    $DB->delete_records("lesson_pages", array("id" => $pageid));

    // repair the hole in the linkage
    if (!$thispage->prevpageid AND !$thispage->nextpageid) {
        //This is the only page, no repair needed
    } elseif (!$thispage->prevpageid) {
        // this is the first page...
        if (!$page = $DB->get_record("lesson_pages", array("id" => $thispage->nextpageid))) {
            print_error("Delete: next page not found");
        }
        if (!$DB->set_field("lesson_pages", "prevpageid", 0, array("id" => $page->id))) {
            print_error("Delete: unable to set prevpage link");
        }
    } elseif (!$thispage->nextpageid) {
        // this is the last page...
        if (!$page = $DB->get_record("lesson_pages", array("id" => $thispage->prevpageid))) {
            print_error("Delete: prev page not found");
        }
        if (!$DB->set_field("lesson_pages", "nextpageid", 0, array("id" => $page->id))) {
            print_error("Delete: unable to set nextpage link");
        }
    } else {
        // page is in the middle...
        if (!$prevpage = $DB->get_record("lesson_pages", array("id" => $thispage->prevpageid))) {
            print_error("Delete: prev page not found");
        }
        if (!$nextpage = $DB->get_record("lesson_pages", array("id" => $thispage->nextpageid))) {
            print_error("Delete: next page not found");
        }
        if (!$DB->set_field("lesson_pages", "nextpageid", $nextpage->id, array("id" => $prevpage->id))) {
            print_error("Delete: unable to set next link");
        }
        if (!$DB->set_field("lesson_pages", "prevpageid", $prevpage->id, array("id" => $nextpage->id))) {
            print_error("Delete: unable to set prev link");
        }
    }
    lesson_set_message(get_string('deletedpage', 'lesson').': '.format_string($thispage->title, true), 'notifysuccess');
    redirect("$CFG->wwwroot/mod/lesson/edit.php?id=$cm->id");
?>
