<?php  //$Id: database_column_info.php,v 1.4 2008/07/13 20:27:51 skodak Exp $

/**
 * Detail database field information.
 * Based on ADOFieldObject.
 * @package dml
 */
class database_column_info {
    /**
     * Name of column - lowercase
     */
    public $name;

    /**
     * Driver dependent native data type
     * Not standardised - used to find meta_type
     */
    public $type;

    /**
     * Max length:
     *  character type - number of characters
     *  blob - number of bytes
     *  integer - number of digits
     *  float - digits left from floating point
     *  boolean - 1
     *  enums - null
     */
    public $max_length;

    /**
     * Scale
     * float - decimal points
     * other - null
     */
    public $scale;

    /**
     * Enumerated field options,
     * null if not enum type
     *
     * For performance reasons this field is optional!
     * You can use DDL sql_generator::getCheckConstraintsFromDB() if needed.
     */
    public $enums;

    /**
     * True if not null, false otherwise
     */
    public $not_null;

    /**
     * True if column is primary key.
     * (usually 'id').
     */
    public $primary_key;

    /**
     * True if filed autoincrementing
     * (usually 'id' only)
     */
    public $auto_increment;

    /**
     * True if binary
     */
    public $binary;

    /**
     * True if integer unsigned, false if signed.
     * Null for other types
     */
    public $unsigned;

    /**
     * True if default value defined
     */
    public $has_default;

    /**
     * Default value if defined
     */
    public $default_value;

    /**
     * True if field values unique, false if not
     */
    public $unique;

    /**
     * Standardised one cahracter column type, uppercase
     * R - counter (integer primary key)
     * I - integers
     * N - numbers (floats)
     * C - characters and strings
     * X - texts
     * B - binary blobs
     * L - boolean (1 bit)
     * T - timestamp - unsupported
     * D - date - unsupported
     */
    public $meta_type;

    /**
     * Contructor
     * @param $data mixed object or array with properties
     */
    public function __construct($data) {
        foreach ($data as $key=>$value) {
            if (array_key_exists($key, $this)) {
                $this->$key = $value;
            }
        }

        switch ($this->meta_type) {
            case 'R': // normalise counters (usually 'id')
                $this->auto_increment = true;
                $this->binary         = false;
                $this->has_default    = false;
                $this->default_value  = null;
                $this->unique         = true;
                break;
            case 'C':
                $this->auto_increment = false;
                $this->binary         = false;
                break;
        }
    }
}
