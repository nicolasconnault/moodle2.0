<?php  //$Id: postgres7_adodb_moodle_database.php,v 1.23 2009/05/03 23:46:41 stronk7 Exp $

require_once($CFG->libdir.'/dml/moodle_database.php');
require_once($CFG->libdir.'/dml/adodb_moodle_database.php');

/**
 * Postgresql database class using adodb backend
 * @package dml
 */
class postgres7_adodb_moodle_database extends adodb_moodle_database {

    public function connect($dbhost, $dbuser, $dbpass, $dbname, $prefix, array $dboptions=null) {
        if ($prefix == '' and !$this->external) {
            //Enforce prefixes for everybody but mysql
            throw new dml_exception('prefixcannotbeempty', $this->get_dbfamily());
        }
        return parent::connect($dbhost, $dbuser, $dbpass, $dbname, $prefix, $dboptions);
    }

    /**
     * Detects if all needed PHP stuff installed.
     * Do not connect to connect to db if this test fails.
     * @return mixed true if ok, string if something
     */
    public function driver_installed() {
        if (!extension_loaded('pgsql')) {
            return get_string('pgsqlextensionisnotpresentinphp', 'install');
        }
        return true;
    }

    protected function preconfigure_dbconnection() {
        if (!defined('ADODB_ASSOC_CASE')) {
            define ('ADODB_ASSOC_CASE', 2);
        }
    }

    protected function configure_dbconnection() {
        $this->adodb->SetFetchMode(ADODB_FETCH_ASSOC);

        $sql = "SET NAMES 'utf8'";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->adodb->Execute($sql);
        $this->query_end($result);

        return true;
    }

    /**
     * Returns database family type
     * @return string db family name (mysql, postgres, mssql, oracle, etc.)
     */
    public function get_dbfamily() {
        return 'postgres';
    }

    /**
     * Returns database type
     * @return string db type mysql, mysqli, postgres7
     */
    protected function get_dbtype() {
        return 'postgres7';
    }

    /**
     * Returns localised database description
     * Note: can be used before connect()
     * @return string
     */
    public function get_configuration_hints() {
        return get_string('databasesettingssub_postgres7', 'install');
    }

    /**
     * Returns db related part of config.php
     * @return object
     */
    public function export_dbconfig() {
        $cfg = new stdClass();
        $cfg->dbtype     = $this->get_dbtype();
        $cfg->dblibrary  = $this->get_dblibrary();
        if ($this->dbhost == 'localhost' or $this->dbhost == '127.0.0.1') {
            $cfg->dbhost = "user='{$this->dbuser}' password='{$this->dbpass}' dbname='{$this->dbname}'";
            $cfg->dbname = '';
            $cfg->dbuser = '';
            $cfg->dbpass = '';
        } else {
            $cfg->dbhost = $this->dbhost;
            $cfg->dbname = $this->dbname;
            $cfg->dbuser = $this->dbuser;
            $cfg->dbpass = $this->dbpass;
        }
        $cfg->prefix     = $this->prefix;
        if ($this->dboptions) {
            $cfg->dboptions = $this->dboptions;
        }

        return $cfg;
    }

    /**
     * Returns supported query parameter types
     * @return bitmask
     */
    protected function allowed_param_types() {
        return SQL_PARAMS_QM;
    }

    public function get_columns($table, $usecache=true) {
        if ($usecache and isset($this->columns[$table])) {
            return $this->columns[$table];
        }

        $this->query_start("--adodb-MetaColumns", null, SQL_QUERY_AUX);
        $columns = $this->adodb->MetaColumns($this->prefix.$table);
        $this->query_end(true);

        if (!$columns) {
            return array();
        }

        $this->columns[$table] = array();

        foreach ($columns as $column) {
            // colum names must be lowercase
            $column->meta_type = substr($this->adodb->MetaType($column), 0 ,1); // only 1 character
            // Let's fix the wrong meta type retrieved because of default blobSize=100 in AdoDB
            if ($column->type == 'varchar' && $column->meta_type == 'X') {
                $column->meta_type = 'C';
            }
            if ($column->has_default) {
                if ($pos = strpos($column->default_value, '::')) {
                    if (strpos($column->default_value, "'") === 0) {
                        $column->default_value = substr($column->default_value, 1, $pos-2);
                    } else {
                        $column->default_value = substr($column->default_value, 0, $pos);
                    }
                }
            } else {
                $column->default_value = null;
            }
            $this->columns[$table][$column->name] = new database_column_info($column);
        }

        return $this->columns[$table];
    }

    /**
     * This method will introspect inside DB to detect it it's a UTF-8 DB or no
     * Used from setup.php to set correctly "set names" when the installation
     * process is performed without the initial and beautiful installer
     * @return bool true if db in unicode mode
     */
    function setup_is_unicodedb() {
    /// Get PostgreSQL server_encoding value
        $sql = "SHOW server_encoding";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $rs = $this->adodb->Execute($sql);
        $this->query_end($rs);

        if ($rs && !$rs->EOF) {
            $encoding = $rs->fields['server_encoding'];
            if (strtoupper($encoding) == 'UNICODE' || strtoupper($encoding) == 'UTF8') {
                return true;
            }
        }
        return false;
    }

    /**
     * Insert new record into database, as fast as possible, no safety checks, lobs not supported.
     * (overloaded from adodb_moodle_database because of sequence numbers
     * @param string $table name
     * @param mixed $params data record as object or array
     * @param bool $returnit return it of inserted record
     * @param bool $bulk true means repeated inserts expected
     * @param bool $customsequence true if 'id' included in $params, disables $returnid
     * @return true or new id
     * @throws dml_exception if error
     */
    public function insert_record_raw($table, $params, $returnid=true, $bulk=false, $customsequence=false) {
        if (!is_array($params)) {
            $params = (array)$params;
        }

        if ($customsequence) {
            if (!isset($params['id'])) {
                throw new coding_exception('moodle_database::insert_record_raw() id field must be specified if custom sequences used.');
            }
            $returnid = false;

        } else {
            unset($params['id']);
            /// Postgres doesn't have the concept of primary key built in
            /// and will return the OID which isn't what we want.
            /// The efficient and transaction-safe strategy is to
            /// move the sequence forward first, and make the insert
            /// with an explicit id.
            if ($returnid) {
                $seqname = "{$this->prefix}{$table}_id_seq";
                $this->query_start('--adodb-GenID', null, SQL_QUERY_AUX);
                $nextval = $this->adodb->GenID($seqname);
                $this->query_end(true);
                if ($nextval) {
                    $params['id'] = (int)$nextval;
                }
            }
        }

        if (empty($params)) {
            throw new coding_exception('moodle_database::insert_record_raw() no fields found.');
        }

        $fields = implode(',', array_keys($params));
        $qms    = array_fill(0, count($params), '?');
        $qms    = implode(',', $qms);

        $sql = "INSERT INTO {$this->prefix}$table ($fields) VALUES($qms)";
        $this->query_start($sql, $params, SQL_QUERY_INSERT);
        $rs = $this->adodb->Execute($sql, $params);
        $this->query_end($rs);

        if (!$returnid) {
            return true;
        }
        if (!empty($params['id'])) {
            return (int)$params['id'];
        }

        $oid = $this->adodb->Insert_ID();

        // try to get the primary key based on id
        $sql = "SELECT id FROM {$this->prefix}$table WHERE oid = $oid";
        $this->query_start($sql, $params, SQL_QUERY_AUX);
        $rs = $this->adodb->Execute($sql, $params);
        $this->query_end($rs);

        if ( $rs && ($rs->RecordCount() == 1) ) {
            trigger_error("Retrieved id using oid on table $table because we could not find the sequence.");
            return (integer)reset($rs->fields);
        }
        throw new dml_write_exception('unknown error fetching inserted id');
    }

    /**
     * Insert a record into a table and return the "id" field if required,
     * Some conversions and safety checks are carried out. Lobs are supported.
     * If the return ID isn't required, then this just reports success as true/false.
     * $data is an object containing needed data
     * @param string $table The database table to be inserted into
     * @param object $data A data object with values for one or more fields in the record
     * @param bool $returnid Should the id of the newly created record entry be returned? If this option is not requested then true/false is returned.
     * @param bool $bulk true means repeated inserts expected
     * @return true or new id
     * @throws dml_exception if error
     */
    public function insert_record($table, $dataobject, $returnid=true, $bulk=false) {
        //TODO: add support for blobs BYTEA
        if (!is_object($dataobject)) {
            $dataobject = (object)$dataobject;
        }

        $columns = $this->get_columns($table);
        unset($dataobject->id);
        $cleaned = array();
        $blobs   = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            $column = $columns[$field];
            if ($column->meta_type == 'B') {
                if (is_null($value)) {
                    $cleaned[$field] = null;
                } else {
                    $blobs[$field] = $value;
                    $cleaned[$field] = '@#BLOB#@';
                }
                continue;

            } else if (is_bool($value)) {
                $value = (int)$value; // prevent false '' problems

            } else if ($value === '') {
                if ($column->meta_type == 'I' or $column->meta_type == 'F' or $column->meta_type == 'N') {
                    $value = 0; // prevent '' problems in numeric fields
                }
            }

            $cleaned[$field] = $value;
        }

        if (empty($blobs)) {
            return $this->insert_record_raw($table, $cleaned, $returnid, $bulk);
        }

        $id = $this->insert_record_raw($table, $cleaned, true, $bulk);

        foreach ($blobs as $key=>$value) {
            $this->query_start('--adodb-UpdateBlob', null, SQL_QUERY_UPDATE);
            $result = $this->adodb->UpdateBlob($this->prefix.$table, $key, $value, "id = $id", 'BLOB');// adodb does not use bound parameters for blob updates :-(
            $this->query_end($result);
        }

        return ($returnid ? $id : true);
    }

    /**
     * Update a record in a table
     *
     * $dataobject is an object containing needed data
     * Relies on $dataobject having a variable "id" to
     * specify the record to update
     *
     * @param string $table The database table to be checked against.
     * @param object $dataobject An object with contents equal to fieldname=>fieldvalue. Must have an entry for 'id' to map to the table specified.
     * @param bool true means repeated updates expected
     * @return bool true
     * @throws dml_exception if error
     */
    public function update_record($table, $dataobject, $bulk=false) {
        //TODO: add support for blobs BYTEA
        if (!is_object($dataobject)) {
            $dataobject = (object)$dataobject;
        }

        $columns = $this->get_columns($table);
        $cleaned = array();
        $blobs   = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            $column = $columns[$field];
            if ($column->meta_type == 'B') {
                if (is_null($value)) {
                    $cleaned[$field] = null;
                } else {
                    $blobs[$field] = $value;
                    $cleaned[$field] = '@#BLOB#@';
                }
                continue;

            } else if (is_bool($value)) {
                $value = (int)$value; // prevent "false" problems

            } else if ($value === '') {
                if ($column->meta_type == 'I' or $column->meta_type == 'F' or $column->meta_type == 'N') {
                    $value = 0; // prevent '' problems in numeric fields
                }
            }
            $cleaned[$field] = $value;
        }

        $this->update_record_raw($table, $cleaned, $bulk);

        if (empty($blobs)) {
            return true;
        }

        $id = $dataobject->id;

        foreach ($blobs as $key=>$value) {
            $this->query_start('--adodb-UpdateBlob', null, SQL_QUERY_UPDATE);
            $result = $this->adodb->UpdateBlob($this->prefix.$table, $key, $value, "id = $id", 'BLOB');// adodb does not use bound parameters for blob updates :-(
            $this->query_end($result);
        }

        return true;
    }

    /**
     * Set a single field in every table row where the select statement evaluates to true.
     *
     * @param string $table The database table to be checked against.
     * @param string $newfield the field to set.
     * @param string $newvalue the value to set the field to.
     * @param string $select A fragment of SQL to be used in a where clause in the SQL call.
     * @param array $params array of sql parameters
     * @return bool true
     * @throws dml_exception if error
     */
    public function set_field_select($table, $newfield, $newvalue, $select, array $params=null) {
        $params = (array)$params;
        list($select, $params, $type) = $this->fix_sql_params($select, $params);

        $columns = $this->get_columns($table);
        $column = $columns[$newfield];

        if ($column->meta_type == 'B') {
            /// update blobs and return
            $select = $this->emulate_bound_params($select, $params); // adodb does not use bound parameters for blob updates :-(
            $this->query_start('--adodb-UpdateBlob', null, SQL_QUERY_UPDATE);
            $result = $this->adodb->UpdateBlob($this->prefix.$table, $newfield, $newvalue, $select, 'BLOB');
            $this->query_end($result);
            return true;
        }

        if ($select) {
            $select = "WHERE $select";
        }

        /// normal field update
        if (is_null($newvalue)) {
            $newfield = "$newfield = NULL";
        } else {
            if (is_bool($newvalue)) {
                $newvalue = (int)$newvalue; // prevent "false" problems
            } else if ($newvalue === '') {
                if ($column->meta_type == 'I' or $column->meta_type == 'F' or $column->meta_type == 'N') {
                    $newvalue = 0; // prevent '' problems in numeric fields
                }
            }

            $newfield = "$newfield = ?";
            array_unshift($params, $newvalue); // add as first param
        }
        $sql = "UPDATE {$this->prefix}$table SET $newfield $select";
        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $rs = $this->adodb->Execute($sql, $params);
        $this->query_end($rs);

        return true;
    }

    public function sql_ilike() {
        return 'ILIKE';
    }

    public function sql_concat() {
        $args = func_get_args();
    /// PostgreSQL requires at least one char element in the concat, let's add it
    /// here (at the beginning of the array) until ADOdb fixes it
        if (is_array($args)) {
            array_unshift($args , "''");
        }
        return call_user_func_array(array($this->adodb, 'Concat'), $args);
    }

    public function sql_bitxor($int1, $int2) {
        return '(' . $this->sql_bitor($int1, $int2) . ' - ' . $this->sql_bitand($int1, $int2) . ')';
    }

    public function sql_cast_char2int($fieldname, $text=false) {
        return ' CAST(' . $fieldname . ' AS INT) ';
    }

    public function sql_cast_char2real($fieldname, $text=false) {
        return " $fieldname::real ";
    }

    /**
     * Does this driver suppoer regex syntax when searching
     */
    public function sql_regex_supported() {
        return true;
    }

    /**
     * Return regex positive or negative match sql
     * @param bool $positivematch
     * @return string or empty if not supported
     */
    public function sql_regex($positivematch=true) {
        return $positivematch ? '~*' : '!~*';
    }

    /**
     * Import a record into a table, id field is required.
     * Basic safety checks only. Lobs are supported.
     * @param string $table name of database table to be inserted into
     * @param mixed $dataobject object or array with fields in the record
     * @return bool true
     * @throws dml_exception if error
     */
    public function import_record($table, $dataobject) {
        $dataobject = (object)$dataobject;

        $columns = $this->get_columns($table);
        $cleaned = array();
        $blobs   = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            $column = $columns[$field];
            if ($column->meta_type == 'B') {
                if (!is_null($value)) {
                    $blobs[$field] = $value;
                    $cleaned[$field] = '@#BLOB#@';
                    continue;
                }
            }
            $cleaned[$field] = $value;
        }

        $this->insert_record_raw($table, $cleaned, false, true, true);

        if (empty($blobs)) {
            return true;
        }

    /// We have BLOBs to postprocess

        foreach ($blobs as $key=>$value) {
            $this->query_start('--adodb-UpdateBlob', null, SQL_QUERY_UPDATE);
            $result = $this->adodb->UpdateBlob($this->prefix.$table, $key, $value, "id = $id", 'BLOB');
            $this->query_end($result);
        }

        return true;
    }
}
