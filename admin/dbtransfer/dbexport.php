<?php  //$Id: dbexport.php,v 1.1 2008/09/02 21:20:46 skodak Exp $

require('../../config.php');
require_once('lib.php');
require_once('database_export_form.php');

require_login();
admin_externalpage_setup('dbexport');

//create form
$form = new database_export_form();

if ($data = $form->get_data()) {
    dbtransfer_export_xml_database($data->description, $DB);
    die;
}

admin_externalpage_print_header();
// TODO: add some more info here
$form->display();
admin_externalpage_print_footer();
