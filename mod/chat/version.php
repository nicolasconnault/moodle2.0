<?php // $Id: version.php,v 1.34 2009/04/20 18:39:24 skodak Exp $

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of chat
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2009042000;   // The (date) version of this module
$module->requires = 2009041700;  // Requires this Moodle version
$module->cron     = 300;          // How often should cron check this module (seconds)?

?>
