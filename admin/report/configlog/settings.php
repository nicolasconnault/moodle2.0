<?php  //$Id: settings.php,v 1.1 2009/01/13 21:13:16 skodak Exp $

$ADMIN->add('reports', new admin_externalpage('reportconfiglog', get_string('configlog', 'report_configlog'), "$CFG->wwwroot/$CFG->admin/report/configlog/index.php"));
