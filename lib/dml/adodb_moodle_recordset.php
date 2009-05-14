<?php  //$Id: adodb_moodle_recordset.php,v 1.6 2008/10/28 12:14:10 skodak Exp $

require_once($CFG->libdir.'/dml/moodle_recordset.php');

/**
 * Adodb basic moodle recordset class
 * @package dml
 */
class adodb_moodle_recordset extends moodle_recordset {

    protected $rs; ///ADOdb recordset

    public function __construct($rs) {
        $this->rs = $rs;
    }

    public function current() {
        return (object)$this->rs->fields;
    }

    public function key() {
    /// return first column value as key
        return reset($this->rs->fields);
    }

    public function next() {
        $this->rs->MoveNext();
    }

    public function valid() {
        return !$this->rs->EOF;
    }

    public function close() {
        $this->rs->Close();
        $this->rs = null;
    }
}
