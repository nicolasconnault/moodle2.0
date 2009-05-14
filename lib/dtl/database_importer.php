<?php  //$Id: database_importer.php,v 1.4 2009/02/13 02:49:25 tjhunt Exp $

/**
 * General database import classes
 * @author Andrei Bautu
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package dbtransfer
 */

/**
 * Base class for database import operations. This class implements
 * basic callbacks for import operations and defines the @see import_database
 * method as a common method for all importers. In general, subclasses will
 * override import_database and call other methods in appropriate moments.
 * Between a single pair of calls to @see begin_database_import and
 * @see finish_database_import, multiple non-overlapping pairs of calls may
 * be made to @see begin_table_import and @see finish_database_import for
 * different tables.
 * Between one pair of calls to @see begin_table_import and
 * @see finish_database_import multiple calls may be made to
 * @see import_table_data for the same table.
 * This class can be used directly, if the standard control flow (defined above)
 * is respected.
 */
class database_importer {
    /** Connection to the target database (a @see moodle_database object). */
    protected $mdb;
    /** Database manager of the target database (a @see database_manager object). */
    protected $manager;
    /** Target database schema in XMLDB format (a @see xmldb_structure object). */
    protected $schema;
    /**
     * Boolean flag - whether or not to check that XML database schema matches
     * the RDBMS database schema before importing (used by
     * @see begin_database_import).
     */
    protected $check_schema;
    /**
     * How to use transactions.
     */
    protected $transactionmode = 'allinone';

    /**
     * Object constructor.
     *
     * @param moodle_database $mdb Connection to the target database (a
     * @see moodle_database object). Use null to use the curent $DB connection.
     * @param boolean $check_schema - whether or not to check that XML database
     * schema matches the RDBMS database schema before importing (inside
     * @see begin_database_import).
     */
    public function __construct(moodle_database $mdb, $check_schema=true) {
        $this->mdb          = $mdb;
        $this->manager      = $mdb->get_manager();
        $this->schema       = $this->manager->get_install_xml_schema();
        $this->check_schema = $check_schema;
    }

    /**
     * How to use transactions during the import.
     * @param string $mode 'pertable', 'allinone' or 'none'.
     */
    public function set_transaction_mode($mode) {
        if (!in_array($mode, array('pertable', 'allinone', 'none'))) {
            throw new coding_exception('Unknown transaction mode', $mode);
        }
        $this->transactionmode = $mode;
    }

    /**
     * Callback function. Should be called only once database per import
     * operation, before any database changes are made. It will check the database
     * schema if @see check_schema is true
     *
     * @exception dbtransfer_exception if any checking (e.g. database schema, Moodle
     * version) fails
     *
     * @param float $version the version of the system which generated the data
     * @param string $timestamp the timestamp of the data (in ISO 8601) format.
     * @return void
     */
    public function begin_database_import($version, $timestamp) {
        global $CFG;

        if (!$this->mdb->get_tables()) {
            // not tables yet, time to create all tables
            $this->manager->install_from_xmldb_structure($this->schema);
        }

        if (round($version, 2) !== round($CFG->version, 2)) { // version might be in decimal format too
            $a = (object)array('schemaver'=>$version, 'currentver'=>$CFG->version);
            throw new dbtransfer_exception('importversionmismatchexception', $a);
        }

        if ($this->check_schema and $errors = $this->manager->check_database_schema($this->schema)) {
            $details = '';
            foreach ($errors as $table=>$items) {
                $details .= '<div>'.get_string('table').' '.$table.':';
                $details .= '<ul>';
                foreach ($items as $item) {
                    $details .= '<li>'.$item.'</li>';
                }
                $details .= '</ul></div>';
            }
            throw new dbtransfer_exception('importschemaexception', $details);
        }
        if ($this->transactionmode == 'allinone') {
            $this->mdb->begin_sql();
        }
    }

    /**
     * Callback function. Should be called only once per table import operation,
     * before any table changes are made. It will delete all table data.
     *
     * @exception dbtransfer_exception an unknown table import is attempted
     * @exception ddl_table_missing_exception if the table is missing
     *
     * @param string $tablename - the name of the table that will be imported
     * @param string $schemaHash - the hash of the xmldb_table schema of the table
     * @return void
     */
    public function begin_table_import($tablename, $schemaHash) {
        if ($this->transactionmode == 'pertable') {
            $this->mdb->begin_sql();
        }
        if (!$table = $this->schema->getTable($tablename)) {
            throw new dbtransfer_exception('unknowntableexception', $tablename);
        }
        if ($schemaHash != $table->getHash()) {
            throw new dbtransfer_exception('differenttableexception', $tablename);
        }
        // this should not happen, unless someone drops tables after import started
        if (!$this->manager->table_exists($table)) {
            throw new ddl_table_missing_exception($tablename);
        }
        $this->mdb->delete_records($tablename);
    }

    /**
     * Callback function. Should be called only once per table import operation,
     * after all table changes are made. It will reset table sequences if any.
     * @param string $tablename
     * @return void
     */
    public function finish_table_import($tablename) {
        $table  = $this->schema->getTable($tablename);
        $fields = $table->getFields();
        foreach ($fields as $field) {
            if ($field->getSequence()) {
                $this->manager->reset_sequence($tablename);
                return;
            }
        }
        if ($this->transactionmode == 'pertable') {
            $this->mdb->commit_sql();
        }
    }

    /**
     * Callback function. Should be called only once database per import
     * operation, after all database changes are made. It will commit changes.
     * @return void
     */
    public function finish_database_import() {
        if ($this->transactionmode == 'allinone') {
            $this->mdb->commit_sql();
        }
    }

    /**
     * Callback function. Should be called only once per record import operation, only
     * between @see begin_table_import and @see finish_table_import calls.
     * It will insert table data.
     *
     * @exception dml_exception if data insert operation failed
     *
     * @param string $tablename - the name of the table in which data will be
     * imported
     * @param object $data - data object (fields and values will be inserted
     * into table)
     * @return void
     */
    public function import_table_data($tablename, $data) {
        $this->mdb->import_record($tablename, $data);
    }

    /**
     * Common import method
     * @return void
     */
    public function import_database() {
        // implement in subclasses
    }
}
