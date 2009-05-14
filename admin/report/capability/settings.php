<?php  // $Id: settings.php,v 1.3 2008/11/29 14:22:16 skodak Exp $
$ADMIN->add('roles', new admin_externalpage('reportcapability', get_string('capability', 'report_capability'), "$CFG->wwwroot/$CFG->admin/report/capability/index.php",'moodle/role:manage'));
?>