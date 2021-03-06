<?php // $Id: grouppix.php,v 1.1 2009/01/05 21:37:21 skodak Exp $
      // This function fetches group pictures from the data directory
      // Syntax:   pix.php/groupid/f1.jpg or pix.php/groupid/f2.jpg
      //     OR:   ?file=groupid/f1.jpg or ?file=groupid/f2.jpg

    define('NO_MOODLE_COOKIES', true);                  // session not used here

    require_once('../config.php');
    require_once($CFG->libdir.'/filelib.php');

    // disable moodle specific debug messages
    disable_debugging();

    $relativepath = get_file_argument();

    $args = explode('/', trim($relativepath, '/'));

    if (count($args) == 2) {
        $groupid  = (integer)$args[0];
        $image    = $args[1];
        $pathname = $CFG->dataroot.'/groups/'.$groupid.'/'.$image;
    } else {
        $image    = 'f1.png';
        $pathname = $CFG->dirroot.'/pix/g/f1.png';
    }

    if (file_exists($pathname) and !is_dir($pathname)) {
        send_file($pathname, $image);
    } else {
        header('HTTP/1.0 404 not found');
        print_error('filenotfound', 'error'); //this is not displayed on IIS??
    }
?>
