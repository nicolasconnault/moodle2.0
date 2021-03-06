<?php // $Id: move.php,v 1.8 2009/01/02 10:36:27 skodak Exp $
/**
 * Action that displays an interface for moving a page
 *
 * @version $Id: move.php,v 1.8 2009/01/02 10:36:27 skodak Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/
   
    $pageid = required_param('pageid', PARAM_INT);
    $title = $DB->get_field("lesson_pages", "title", array("id" => $pageid));
    print_heading(get_string("moving", "lesson", format_string($title)));
   
    $params = array ("lessonid" => $lesson->id, "prevpageid" => 0);
    if (!$page = $DB->get_record_select("lesson_pages", "lessonid = :lessonid AND prevpageid = :prevpageid", $params)) {
        print_error('cannotfindfirstpage', 'lesson');
    }

    echo "<center><table cellpadding=\"5\" border=\"1\">\n";
    echo "<tr><td><a href=\"lesson.php?id=$cm->id&amp;sesskey=".sesskey()."&amp;action=moveit&amp;pageid=$pageid&amp;after=0\"><small>".
        get_string("movepagehere", "lesson")."</small></a></td></tr>\n";
    while (true) {
        if ($page->id != $pageid) {
            if (!$title = trim(format_string($page->title))) {
                $title = "<< ".get_string("notitle", "lesson")."  >>";
            }
            echo "<tr><td><b>$title</b></td></tr>\n";
            echo "<tr><td><a href=\"lesson.php?id=$cm->id&amp;sesskey=".sesskey()."&amp;action=moveit&amp;pageid=$pageid&amp;after={$page->id}\"><small>".
                get_string("movepagehere", "lesson")."</small></a></td></tr>\n";
        }
        if ($page->nextpageid) {
            if (!$page = $DB->get_record("lesson_pages", array("id" => $page->nextpageid))) {
                print_error('cannotfindnextpage', 'lesson');
            }
        } else {
            // last page reached
            break;
        }
    }
    echo "</table>\n";
?>
