<?php  //$Id: settings.php,v 1.1 2008/12/29 19:13:56 skodak Exp $

$ADMIN->add('reports', new admin_externalpage('reportsecurity', get_string('reportsecurity', 'report_security'), "$CFG->wwwroot/$CFG->admin/report/security/index.php",'report/security:view'));
