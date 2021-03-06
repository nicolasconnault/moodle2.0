<?php // $Id: check_bigints.class.php,v 1.9 2008/10/23 08:30:43 tjhunt Exp $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas     http://dougiamas.com  //
//           (C) 2001-3001 Eloy Lafuente (stronk7) http://contiento.com  //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/// This class will check all the int(10) fields existing in the DB
/// reporting about the ones not phisically implemented as BIGINTs
/// and providing one SQL script to fix all them. Also, under MySQL,
/// it performs one check of signed bigints. MDL-11038

class check_bigints extends XMLDBCheckAction {
    private $correct_type;
    private $dbfamily;

    /**
     * Init method, every subclass will have its own
     */
    function init() {
        global $DB;

        $this->introstr = 'confirmcheckbigints';
        parent::init();

    /// Set own core attributes

    /// Set own custom attributes

    /// Get needed strings
        $this->loadStrings(array(
            'wrongints' => 'xmldb',
            'nowrongintsfound' => 'xmldb',
            'yeswrongintsfound' => 'xmldb',
            'mysqlextracheckbigints' => 'xmldb',
        ));

    /// Correct fields must be type bigint for MySQL and int8 for PostgreSQL
        $this->dbfamily = $DB->get_dbfamily();
        switch ($this->dbfamily) {
            case 'mysql':
                $this->correct_type = 'bigint';
                break;
            case 'postgres':
                $this->correct_type = 'int8';
                break;
            default:
                $this->correct_type = NULL;
        }
    }

    protected function check_table(xmldb_table $xmldb_table, array $metacolumns) {
        $o = '';
        $wrong_fields = array();

    /// Get and process XMLDB fields
        if ($xmldb_fields = $xmldb_table->getFields()) {
            $o.='        <ul>';
            foreach ($xmldb_fields as $xmldb_field) {
            /// If the field isn't integer(10), skip
                if ($xmldb_field->getType() != XMLDB_TYPE_INTEGER || $xmldb_field->getLength() != 10) {
                    continue;
                }
            /// If the metadata for that column doesn't exist, skip
                if (!isset($metacolumns[$xmldb_field->getName()])) {
                    continue;
                }
            /// To variable for better handling
                $metacolumn = $metacolumns[$xmldb_field->getName()];
            /// Going to check this field in DB
                $o.='            <li>' . $this->str['field'] . ': ' . $xmldb_field->getName() . ' ';
            /// Detect if the phisical field is wrong and, under mysql, check for incorrect signed fields too
                if ($metacolumn->type != $this->correct_type || ($this->dbfamily == 'mysql' && $xmldb_field->getUnsigned() && !$metacolumn->unsigned)) {
                    $o.='<font color="red">' . $this->str['wrong'] . '</font>';
                /// Add the wrong field to the list
                    $obj = new object;
                    $obj->table = $xmldb_table;
                    $obj->field = $xmldb_field;
                    $wrong_fields[] = $obj;
                } else {
                    $o.='<font color="green">' . $this->str['ok'] . '</font>';
                }
                $o.='</li>';
            }
            $o.='        </ul>';
        }

        return array($o, $wrong_fields);
    }

    protected function display_results(array $wrong_fields) {
        global $DB;
        $dbman = $DB->get_manager();

        $s = '';
        $r = '<table class="generalbox boxaligncenter boxwidthwide" border="0" cellpadding="5" cellspacing="0" id="results">';
        $r.= '  <tr><td class="generalboxcontent">';
        $r.= '    <h2 class="main">' . $this->str['searchresults'] . '</h2>';
        $r.= '    <p class="centerpara">' . $this->str['wrongints'] . ': ' . count($wrong_fields) . '</p>';
        $r.= '  </td></tr>';
        $r.= '  <tr><td class="generalboxcontent">';

    /// If we have found wrong integers inform about them
        if (count($wrong_fields)) {
            $r.= '    <p class="centerpara">' . $this->str['yeswrongintsfound'] . '</p>';
            $r.= '        <ul>';
            foreach ($wrong_fields as $obj) {
                $xmldb_table = $obj->table;
                $xmldb_field = $obj->field;
            /// MySQL directly supports this

// TODO: move this hack to generators!!

                if ($this->dbfamily == 'mysql') {
                    $sqlarr = $dbman->generator->getAlterFieldSQL($xmldb_table, $xmldb_field);
            /// PostgreSQL (XMLDB implementation) is a bit, er... imperfect.
                } else if ($this->dbfamily == 'postgres') {
                    $sqlarr = array('ALTER TABLE ' . $DB->get_prefix() . $xmldb_table->getName() .
                              ' ALTER COLUMN ' . $xmldb_field->getName() . ' TYPE BIGINT;');
                }
                $r.= '            <li>' . $this->str['table'] . ': ' . $xmldb_table->getName() . '. ' .
                                          $this->str['field'] . ': ' . $xmldb_field->getName() . '</li>';
            /// Add to output if we have sentences
                if ($sqlarr) {
                    $sqlarr = $dbman->generator->getEndedStatements($sqlarr);
                    $s.= '<code>' . str_replace("\n", '<br />', implode('<br />', $sqlarr)). '</code><br />';
                }
            }
            $r.= '        </ul>';
        /// Add the SQL statements (all together)
            $r.= '<hr />' . $s;
        } else {
            $r.= '    <p class="centerpara">' . $this->str['nowrongintsfound'] . '</p>';
        }
        $r.= '  </td></tr>';
        $r.= '  <tr><td class="generalboxcontent">';
    /// Add the complete log message
        $r.= '    <p class="centerpara">' . $this->str['completelogbelow'] . '</p>';
        $r.= '  </td></tr>';
        $r.= '</table>';

        return $r;
    }
}
?>
