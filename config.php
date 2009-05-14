<?php  /// Moodle Configuration File

unset($CFG);
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodle_head';
$CFG->dbuser    = 'nicolas';
$CFG->dbpass    = '1axi586';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersit' => 0,
);

$CFG->wwwroot   = 'http://enterprise/moodle_head';
$CFG->dirroot   = '/web/htdocs/moodle_head';
$CFG->dataroot  = '/web/moodledata/moodle_head';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 00777;  // try 02777 on a server in Safe Mode

require_once("$CFG->dirroot/lib/setup.php");

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
