<?php // $Id: version.php,v 1.142 2009/04/20 19:29:15 skodak Exp $

////////////////////////////////////////////////////////////////////////////////
//  Code fragment to define the version of quiz
//  This fragment is called by moodle_needs_upgrading() and /admin/index.php
////////////////////////////////////////////////////////////////////////////////

$module->version  = 2009042000;   // The (date) version of this module
$module->requires = 2009041700;   // Requires this Moodle version
$module->cron     = 0;            // How often should cron check this module (seconds)?

?>
