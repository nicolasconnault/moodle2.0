<?php  // $Id: editcategories.php,v 1.48 2009/05/05 09:00:24 skodak Exp $

/// This page allows to edit entries categories for a particular instance of glossary

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);                       // Course Module ID, or
    $usedynalink = optional_param('usedynalink', 0, PARAM_INT);  // category ID
    $confirm     = optional_param('confirm', 0, PARAM_INT);      // confirm the action
    $name        = optional_param('name', '', PARAM_CLEAN);  // confirm the name

    $action = optional_param('action', '', PARAM_ALPHA ); // what to do
    $hook   = optional_param('hook', '', PARAM_ALPHANUM); // category ID
    $mode   = optional_param('mode', '', PARAM_ALPHA);   // cat

    $action = strtolower($action);

    if (! $cm = get_coursemodule_from_id('glossary', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
        print_error('coursemisconf');
    }

    if (! $glossary = $DB->get_record("glossary", array("id"=>$cm->instance))) {
        print_error('invalidcoursemodule');
    }

    if ($hook > 0) {
        if ($category = $DB->get_record("glossary_categories", array("id"=>$hook))) {
            //Check it belongs to the same glossary
            if ($category->glossaryid != $glossary->id) {
                print_error('invalidid', 'glossary');
            }
        } else {
            print_error('invalidcategoryid');
        }
    }

    require_login($course->id, false, $cm);

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/glossary:managecategories', $context);

    $strglossaries   = get_string("modulenameplural", "glossary");
    $strglossary     = get_string("modulename", "glossary");

    $navlinks = array();
    $navlinks[] = array('name' => $strglossaries, 'link' => "index.php?id=$course->id", 'type' => 'activity');
    $navlinks[] = array('name' => format_string($glossary->name), 'link' => "view.php?id=$cm->id&amp;tab=GLOSSARY_CATEGORY_VIEW", 'type' => 'activityinstance');
    $navlinks[] = array('name' => get_string("categories","glossary"), 'link' => '', 'type' => 'title');
    
    $navigation = build_navigation($navlinks);

    print_header_simple(format_string($glossary->name), "", $navigation,
                        "", "", true, update_module_button($cm->id, $course->id, $strglossary),
                        navmenu($course, $cm));

    if ( $hook >0 ) {

        if ( $action == "edit" ) {
            if ( $confirm ) {
                $action = "";
                $cat = new object();
                $cat->id = $hook;
                $cat->name = $name;
                $cat->usedynalink = $usedynalink;

                $DB->update_record("glossary_categories", $cat);
                add_to_log($course->id, "glossary", "edit category", "editcategories.php?id=$cm->id", $hook,$cm->id);

            } else {
                echo "<p style=\"text-align:center\">" . get_string("edit"). " " . get_string("category","glossary") . "<span style=\"font-size:1.5em\">";

                $name = $category->name;
                $usedynalink = $category->usedynalink;
                require "editcategories.html";
                print_footer();
                die;
            }

        } elseif ( $action == "delete" ) {
            if ( $confirm ) {
                $DB->delete_records("glossary_entries_categories", array("categoryid"=>$hook));
                $DB->delete_records("glossary_categories", array("id"=>$hook));

                print_simple_box_start("center","40%", "#FFBBBB");
                echo "<div style=\"text-align:center\">" . get_string("categorydeleted","glossary") ."</div>";
                echo "</center>";
                print_simple_box_end();
                print_footer($course);

                add_to_log($course->id, "glossary", "delete category", "editcategories.php?id=$cm->id", $hook,$cm->id);

                redirect("editcategories.php?id=$cm->id");
            } else {
                echo "<p style=\"text-align:center\">" . get_string("delete"). " " . get_string("category","glossary"). "</p>";

                print_simple_box_start("center","40%", "#FFBBBB");
                echo "<div class=\"boxaligncenter\"><b>".format_text($category->name, FORMAT_PLAIN)."</b><br/>";

                $num_entries = $DB->count_records("glossary_entries_categories", array("categoryid"=>$category->id));
                if ( $num_entries ) {
                    print_string("deletingnoneemptycategory","glossary");
                }
                echo "<p>";
                print_string("areyousuredelete","glossary");
                echo "</p>";
?>

                <table border="0" width="100">
                    <tr>
                        <td align="right" style="width:50%">                
                        <form id="form" method="post" action="editcategories.php">
                        <div>
                        <input type="hidden" name="id"          value="<?php p($cm->id) ?>" />
                        <input type="hidden" name="action"      value="delete" />
                        <input type="hidden" name="confirm"     value="1" />
                        <input type="hidden" name="mode"         value="<?php echo $mode ?>" />
                        <input type="hidden" name="hook"         value="<?php echo $hook ?>" />
                        <input type="submit" value=" <?php print_string("yes")?> " />
                        </div>
                        </form>
                        </td>
                        <td align="left" style="width:50%">

<?php
                unset($options);
                $options = array ("id" => $id);
                print_single_button("editcategories.php", $options, get_string("no") );
                echo "</td></tr></table>";
                echo "</div>";
                print_simple_box_end();
            }
        }

    } elseif ( $action == "add" ) {
        if ( $confirm ) {
            $ILIKE = $DB->sql_ilike();
            $dupcategory = $DB->get_records_sql("SELECT * FROM {glossary_categories} WHERE name $ILIKE ? AND glossaryid=?", array($name, $glossary->id));
            if ( $dupcategory ) {
                echo "<p style=\"text-align:center\">" . get_string("add"). " " . get_string("category","glossary");

                print_simple_box_start("center","40%", "#FFBBBB");
                echo "<div style=\"text-align:center\">" . get_string("duplicatedcategory","glossary") ."</div>";
                print_simple_box_end();

                redirect("editcategories.php?id=$cm->id&amp;action=add&&amp;name=$name");

            } else {
                $action = "";
                $cat = new object();
                $cat->name = $name;
                $cat->usedynalink = $usedynalink;
                $cat->glossaryid = $glossary->id;

                $cat->id = $DB->insert_record("glossary_categories", $cat);
                add_to_log($course->id, "glossary", "add category", "editcategories.php?id=$cm->id", $cat->id,$cm->id);
            }
        } else {
            echo "<p style=\"text-align:center\">" . get_string("add"). " " . get_string("category","glossary"). "</p>";
            $name="";
            require "editcategories.html";
        }
    }

    if ( $action ) {
        print_footer();
        die;
    }

?>

<form method="post" action="editcategories.php">
<table width="40%" class="boxaligncenter generalbox" cellpadding="5">
        <tr>
          <td style="width:90%" align="center"><b>
          <?php p(get_string("categories","glossary")) ?></b></td>
          <td style="width:10%" align="center"><b>
          <?php p(get_string("action")) ?></b></td>
        </tr>
        <tr><td style="width:100%" colspan="2">

        

<?php
    $categories = $DB->get_records("glossary_categories", array("glossaryid"=>$glossary->id), "name ASC");

    if ( $categories ) {
        echo '<table width="100%">';
        foreach ($categories as $category) {
            $num_entries = $DB->count_records("glossary_entries_categories", array("categoryid"=>$category->id));
?>

             <tr>
               <td style="width:90%" align="left">
               <?php
                    echo "<b>".format_text($category->name, FORMAT_PLAIN)."</b> <span style=\"font-size:0.75em\">($num_entries " . get_string("entries","glossary") . ")</span>";
               ?>
               </td>
               <td style="width:10%" align="center"><b>
               <?php
                echo "<a href=\"editcategories.php?id=$cm->id&amp;action=delete&amp;mode=cat&amp;hook=$category->id\"><img  alt=\"" . get_string("delete") . "\"src=\"{$CFG->pixpath}/t/delete.gif\" class=\"iconsmall\" /></a> ";
                echo "<a href=\"editcategories.php?id=$cm->id&amp;action=edit&amp;mode=cat&amp;hook=$category->id\"><img  alt=\"" . get_string("edit") . "\" src=\"{$CFG->pixpath}/t/edit.gif\" class=\"iconsmall\" /></a>";
               ?>
               </b></td>
             </tr>

             <?php
          
          }
        echo '</table>';
     }
?>

        </td></tr>
        <tr>
        <td style="width:100%" colspan="2"  align="center">
            <?php

             $options['id'] = $cm->id;
             $options['action'] = "add";

             echo "<table border=\"0\"><tr><td align=\"right\">";
             echo print_single_button("editcategories.php", $options, get_string("add") . " " . get_string("category","glossary"), "get");
             echo "</td><td align=\"left\">";
             unset($options['action']);
             $options['mode'] = 'cat';
             $options['hook'] = $hook;
             echo print_single_button("view.php", $options, get_string("back","glossary") );
             echo "</td></tr>";
             echo "</table>";

            ?>
        </td>
        </tr>
        </table>


</form>

<?php print_footer() ?>
