<?php  // $Id: settings.php,v 1.2 2008/11/29 14:22:13 skodak Exp $
$ADMIN->add('reports', new admin_externalpage('reportquestioninstances', get_string('questioninstances', 'report_questioninstances'), "$CFG->wwwroot/$CFG->admin/report/questioninstances/index.php", 'report/questioninstances:view'));
?>