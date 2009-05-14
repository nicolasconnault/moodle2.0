<?php  //$Id: install.php,v 1.1 2009/01/15 21:36:48 skodak Exp $

// This file is executed right after the install.xml
//

function xmldb_quizreport_statistics_install() {
    global $DB;

    $record = new object();
    $record->name         = 'statistics';
    $record->displayorder = 8000;
    $record->cron         = 18000;
    $record->capability   = 'quizreport/statistics:view';
    $DB->insert_record('quiz_report', $record);

}