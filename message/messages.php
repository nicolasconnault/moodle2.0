<?php // $Id: messages.php,v 1.8 2006/12/18 17:10:35 skodak Exp $

    require('../config.php');

/// Select direction
    if ( get_string('thisdirection') == 'rtl' ) {
        $direction = ' dir="rtl"';
    } else {
        $direction = ' dir="ltr"';
    }
/// Output the header
    @header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php echo $direction ?>>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  </head>
  <body class="message course-1" id="message-messages">
