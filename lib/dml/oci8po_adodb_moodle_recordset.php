<?php  //$Id: oci8po_adodb_moodle_recordset.php,v 1.7 2008/06/15 11:35:26 skodak Exp $

require_once($CFG->libdir.'/dml/adodb_moodle_recordset.php');

/**
 * Oracle moodle recordest with special hacks
 * @package dml
 */
class oci8po_adodb_moodle_recordset extends adodb_moodle_recordset {

    public function current() {
        /// Really DIRTY HACK for Oracle - needed because it can not see difference from NULL and ''
        /// this can not be removed even if we change db defaults :-(
        $fields = $this->rs->fields;
        array_walk($fields, array('oci8po_adodb_moodle_database', 'onespace2empty'));
        return (object)$fields;
    }
}
