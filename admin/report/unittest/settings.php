<?php  //$Id: settings.php,v 1.3 2008/11/26 19:27:41 skodak Exp $
if ($hassiteconfig) {
    $ADMIN->add('development', new admin_externalpage('reportsimpletest', get_string('simpletest', 'admin'), "$CFG->wwwroot/$CFG->admin/report/unittest/index.php",'report/unittest:view'));
    $ADMIN->add('development', new admin_externalpage('reportdbtest', get_string('dbtest', 'admin'), "$CFG->wwwroot/$CFG->admin/report/unittest/dbtest.php",'report/unittest:view'));
}
?>