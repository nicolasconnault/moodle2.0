<?php // $Id: draftfiles.php,v 1.5 2008/09/21 21:30:26 skodak Exp $

    require('../config.php');
    require_once($CFG->libdir.'/filelib.php');

    $itemid     = required_param('itemid', PARAM_INT);
    $filepath   = optional_param('filepath', '/', PARAM_PATH);
    $newdirname = optional_param('newdirname', '', PARAM_FILE);
    $delete     = optional_param('delete', '', PARAM_PATH);
    $subdirs    = optional_param('subdirs', 0, PARAM_BOOL);
    $maxbytes   = optional_param('maxbytes', 0, PARAM_INT);

    require_login();
    if (isguestuser()) {
        print_error('noguest');
    }

    if (!$context = get_context_instance(CONTEXT_USER, $USER->id)) {
        print_error('invalidcontext');
    }

    $notice = '';

    $contextid = $context->id;
    $filearea  = 'user_draft';

    $browser = get_file_browser();
    $fs      = get_file_storage();

    if (!$subdirs) {
        $filepath = '/';
    }

    if (!$directory = $fs->get_file($context->id, 'user_draft', $itemid, $filepath, '.')) {
        $directory = new virtual_root_file($context->id, 'user_draft', $itemid);
        $filepath = $directory->get_filepath();
    }
    $files = $fs->get_directory_files($context->id, 'user_draft', $itemid, $directory->get_filepath());
    $parent = $directory->get_parent_directory();

    $totalbytes = 0;
    foreach ($files as $hash=>$file) {
        if (!$subdirs and $file->get_filepath() !== '/') {
            unset($files[$hash]);
            continue;
        }
        $totalbytes += $file->get_filesize();
    }

/// process actions
    if ($newdirname !== '' and data_submitted() and confirm_sesskey()) {
        $newdirname = $directory->get_filepath().$newdirname.'/';
        $fs->create_directory($contextid, $filearea, $itemid, $newdirname, $USER->id);
        redirect('draftfiles.php?itemid='.$itemid.'&amp;filepath='.rawurlencode($newdirname).'&amp;subdirs='.$subdirs.'&amp;maxbytes='.$maxbytes);
    }

    if (isset($_FILES['newfile']) and data_submitted() and confirm_sesskey()) {
        if (!empty($_FILES['newfile']['error'])) {
            $notice = file_get_upload_error($_FILES['newfile']['error']);
        } else {
            $file = $_FILES['newfile'];
            $newfilename = clean_param($file['name'], PARAM_FILE);
            if (is_uploaded_file($_FILES['newfile']['tmp_name'])) {
                if ($existingfile = $fs->get_file($contextid, $filearea, $itemid, $filepath, $newfilename)) {
                    $existingfile->delete();
                }
                $filerecord = array('contextid'=>$contextid, 'filearea'=>$filearea, 'itemid'=>$itemid, 'filepath'=>$filepath,
                                    'filename'=>$newfilename, 'userid'=>$USER->id);
                $newfile = $fs->create_file_from_pathname($filerecord, $_FILES['newfile']['tmp_name']);
                redirect('draftfiles.php?itemid='.$itemid.'&amp;filepath='.rawurlencode($filepath).'&amp;subdirs='.$subdirs.'&amp;maxbytes='.$maxbytes);
            }
        }
    }

    if ($delete !== '' and $file = $fs->get_file($contextid, $filearea, $itemid, $filepath, $delete)) {
        if (!data_submitted() or !confirm_sesskey()) {
            print_header();
            notify(get_string('deletecheckwarning').': '.s($file->get_filepath().$file->get_filename()));
            $optionsno  = array('itemid'=>$itemid, 'filepath'=>$filepath, 'subdirs'=>$subdirs);
            $optionsyes = array('itemid'=>$itemid, 'filepath'=>$filepath, 'delete'=>$delete, 'sesskey'=>sesskey(), 'subdirs'=>$subdirs);
            notice_yesno (get_string('deletecheckfiles'), 'draftfiles.php', 'draftfiles.php', $optionsyes, $optionsno, 'post', 'get');
            print_footer('empty');
            die;

        } else {
            $isdir = $file->is_directory();
            $file->delete();
            if ($isdir) {
                redirect('draftfiles.php?itemid='.$itemid.'&amp;filepath='.rawurlencode($parent->get_filepath()).'&amp;subdirs='.$subdirs.'&amp;maxbytes='.$maxbytes);
            } else {
                redirect('draftfiles.php?itemid='.$itemid.'&amp;filepath='.rawurlencode($filepath).'&amp;subdirs='.$subdirs.'&amp;maxbytes='.$maxbytes);
            }
        }
    }

    print_header();

    if ($notice !== '') {
        notify($notice);
    }

    echo '<div class="areafiles">';

    $strfolder   = get_string('folder');
    $strfile     = get_string('file');
    $strdownload = get_string('download');
    $strdelete   = get_string('delete');

    if ($parent) {
        echo '<div class="folder">';
        echo '<a href="draftfiles.php?itemid='.$itemid.'&amp;filepath='.$parent->get_filepath().'&amp;subdirs='.$subdirs.'&amp;maxbytes='.$maxbytes.'"><img src="'.$CFG->pixpath.'/f/parent.gif" class="icon" alt="" />&nbsp;'.get_string('parentfolder').'</a>';
        echo '</div>';
    }

    foreach ($files as $file) {
        $filename    = $file->get_filename();
        $filenameurl = rawurlencode($filename);
        $filepath    = $file->get_filepath();
        $filesize    = $file->get_filesize();
        $filesize    = $filesize ? display_size($filesize) : '';

        $mimetype = $file->get_mimetype();

        if ($file->is_directory()) {
            if ($subdirs) {
                $dirname = explode('/', trim($filepath, '/'));
                $dirname = array_pop($dirname);
                echo '<div class="folder">';
                echo "<a href=\"draftfiles.php?itemid=$itemid&amp;filepath=$filepath&amp;subdirs=$subdirs&amp;maxbytes=$maxbytes\"><img src=\"$CFG->pixpath/f/folder.gif\" class=\"icon\" alt=\"$strfolder\" />&nbsp;".s($dirname)."</a> ";
                echo "<a href=\"draftfiles.php?itemid=$itemid&amp;filepath=$filepath&amp;delete=$filenameurl&amp;subdirs=$subdirs&amp;maxbytes=$maxbytes\"><img src=\"$CFG->pixpath/t/delete.gif\" class=\"iconsmall\" alt=\"$strdelete\" /></a>";
                echo '</div>';
            }

        } else {
            $icon    = mimeinfo_from_type('icon', $mimetype);
            $viewurl = $browser->encodepath("$CFG->wwwroot/draftfile.php", "/$contextid/user_draft/$itemid".$filepath.$filename, false, false);
            echo '<div class="file">';
            echo "<a href=\"$viewurl\"><img src=\"$CFG->pixpath/f/$icon\" class=\"icon\" alt=\"$strfile\" />&nbsp;".s($filename)." ($filesize)</a> ";
            echo "<a href=\"draftfiles.php?itemid=$itemid&amp;filepath=$filepath&amp;delete=$filenameurl&amp;subdirs=$subdirs&amp;maxbytes=$maxbytes\"><img src=\"$CFG->pixpath/t/delete.gif\" class=\"iconsmall\" alt=\"$strdelete\" /></a>";;
            echo '</div>';
        }
    }

    echo '</div>';

    if ($maxbytes == 0 or $maxbytes > $totalbytes) {
        echo '<form enctype="multipart/form-data" method="post" action="draftfiles.php"><div>';
        if ($maxbytes) {
            echo '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxbytes-$totalbytes).'" />';
        }
        echo '<input type="hidden" name="itemid" value="'.$itemid.'" />';
        echo '<input type="hidden" name="filepath" value="'.s($filepath).'" />';
        echo '<input type="hidden" name="subdirs" value="'.$subdirs.'" />';
        echo '<input type="hidden" name="maxbytes" value="'.$maxbytes.'" />';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo '<input name="newfile" type="file" />';
        echo '<input type="submit" value="'.get_string('uploadafile').'" />';
        if ($maxbytes) {
            echo ' ('.get_string('maxsize', '', display_size(get_max_upload_file_size($CFG->maxbytes, $maxbytes-$totalbytes))).')';
        } else {
            echo ' ('.get_string('maxsize', '', display_size(get_max_upload_file_size($CFG->maxbytes))).')';
        }
        echo '</div></form>';
    } else {
        //TODO: notify upload limit reached here
        echo get_string('maxsize', '', display_size(get_max_upload_file_size($CFG->maxbytes, $maxbytes)));
    }

    if ($subdirs) {
        echo '<form action="draftfiles.php" method="post"><div>';
        echo '<input type="hidden" name="itemid" value="'.$itemid.'" />';
        echo '<input type="hidden" name="filepath" value="'.s($filepath).'" />';
        echo '<input type="hidden" name="subdirs" value="'.$subdirs.'" />';
        echo '<input type="hidden" name="maxbytes" value="'.$maxbytes.'" />';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo '<input type="text" name="newdirname" value="" />';
        echo '<input type="submit" value="'.get_string('makeafolder').'" />';
        echo '</div></form>';
    }

    print_footer('empty');

?>
