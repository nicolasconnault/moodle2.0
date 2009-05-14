<?php  // $Id: settings.php,v 1.1 2008/09/08 11:32:32 tjhunt Exp $
$ADMIN->add('reports', new admin_externalpage('reportbackups', get_string('backups', 'admin'), "$CFG->wwwroot/$CFG->admin/report/backups/index.php",'moodle/site:backup'));
?>