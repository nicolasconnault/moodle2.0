<?php  // $Id: settings.php,v 1.2 2008/11/29 14:22:15 skodak Exp $
$ADMIN->add('reports', new admin_externalpage('reportcourseoverview', get_string('courseoverview', 'admin'), "$CFG->wwwroot/$CFG->admin/report/courseoverview/index.php",'report/courseoverview:view'));
?>