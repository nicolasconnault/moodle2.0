<?php // $Id: version.php,v 1.20 2009/04/22 07:14:19 skodak Exp $

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of label
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2009042201;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2009041700;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)

?>
