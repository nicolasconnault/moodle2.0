<?PHP // $Id: version.php,v 1.32 2009/04/27 20:29:01 stronk7 Exp $

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of Wiki
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2009042700;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2009041700;  // The current module version (Date: YYYYMMDDXX)
$module->cron     = 3600;        // Period for cron to check this module (secs)

?>
