<?php // $Id: version.php,v 1.66 2009/04/20 19:14:57 skodak Exp $

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of glossary
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2009042006;
$module->requires = 2009041700;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)

?>
