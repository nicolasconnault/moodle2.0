<?php // $Id: maintenance.php,v 1.18 2009/01/20 06:01:06 moodler Exp $
      // Enables/disables maintenance mode

    require('../config.php');
    require_once($CFG->libdir.'/adminlib.php');

    $action = optional_param('action', '', PARAM_ALPHA);

    admin_externalpage_setup('maintenancemode');

    //Check folder exists
    if (! make_upload_directory(SITEID)) {   // Site folder
            print_error('cannotcreatesitedir', 'error');
        }

    $filename = $CFG->dataroot.'/'.SITEID.'/maintenance.html';

    if ($form = data_submitted()) {
        if (confirm_sesskey()) {
            if ($form->action == "disable") {
                unlink($filename);
                redirect('maintenance.php', get_string('sitemaintenanceoff','admin'));
            } else {
                $file = fopen($filename, 'w');
                fwrite($file, $form->text);
                fclose($file);
                redirect('maintenance.php', get_string('sitemaintenanceon', 'admin'));
            }
        }
    }

/// Print the header stuff

    admin_externalpage_print_header();

    print_heading(get_string('sitemaintenancemode', 'admin'));

    print_box_start();

/// Print the appropriate form

    if (file_exists($filename)) {   // We are in maintenance mode
        echo '<div class="buttons">';
        echo '<p>'.get_string('sitemaintenanceon', 'admin').'</p>';
        echo '<form action="maintenance.php" method="post">';
        echo '<div>';
        echo '<input type="hidden" name="action" value="disable" />';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo '<p><input type="submit" value="'.get_string('disable').'" /></p>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
    } else {                        // We are not in maintenance mode
        $usehtmleditor = can_use_html_editor();

        echo '<div class="buttons">';
        echo '<form action="maintenance.php" method="post">';
        echo '<div>';
        echo '<input type="hidden" name="action" value="enable" />';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo '<p><input type="submit" value="'.get_string('enable').'" /></p>';
        echo '<p>'.get_string('optionalmaintenancemessage', 'admin').':</p>';
        echo '<div class="editor" style="width:600px;">';  // contains the editor
        print_textarea($usehtmleditor, 20, 50, 600, 400, "text");
        echo '</div>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
    }

    print_box_end();

    admin_externalpage_print_footer();
?>
