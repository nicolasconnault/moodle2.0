<?php // $Id: check_defaults.class.php,v 1.7 2008/10/23 08:30:43 tjhunt Exp $

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

/// This class will check all the default values existing in the DB
/// match those specified in the xml specs
/// and providing one SQL script to fix all them.

class check_defaults extends XMLDBCheckAction {

    /**
     * Init method, every subclass will have its own
     */
    function init() {
        $this->introstr = 'confirmcheckdefaults';
        parent::init();

    /// Set own core attributes

    /// Set own custom attributes

    /// Get needed strings
        $this->loadStrings(array(
            'wrongdefaults' => 'xmldb',
            'nowrongdefaultsfound' => 'xmldb',
            'yeswrongdefaultsfound' => 'xmldb',
            'expected' => 'xmldb',
            'actual' => 'xmldb',
        ));
    }

    protected function check_table(xmldb_table $xmldb_table, array $metacolumns) {
        $o = '';
        $wrong_fields = array();

        /// Get and process XMLDB fields
        if ($xmldb_fields = $xmldb_table->getFields()) {
            $o.='        <ul>';
            foreach ($xmldb_fields as $xmldb_field) {

                // Get the default value for the field
                $xmldbdefault = $xmldb_field->getDefault();

                /// If the metadata for that column doesn't exist or 'id' field found, skip
                if (!isset($metacolumns[$xmldb_field->getName()]) or $xmldb_field->getName() == 'id') {
                    continue;
                }

                /// To variable for better handling
                $metacolumn = $metacolumns[$xmldb_field->getName()];

                /// Going to check this field in DB
                $o.='            <li>' . $this->str['field'] . ': ' . $xmldb_field->getName() . ' ';

                // get the value of the physical default (or blank if there isn't one)
                if ($metacolumn->has_default==1) {
                    $physicaldefault = $metacolumn->default_value;
                }
                else {
                    $physicaldefault = '';
                }

                // there *is* a default and it's wrong
                if ($physicaldefault != $xmldbdefault) {
                    $info = '('.$this->str['expected']." '$xmldbdefault', ".$this->str['actual'].
                    " '$physicaldefault')";
                    $o.='<font color="red">' . $this->str['wrong'] . " $info</font>";
                /// Add the wrong field to the list
                    $obj = new object;
                    $obj->table = $xmldb_table;
                    $obj->field = $xmldb_field;
                    $obj->physicaldefault = $physicaldefault;
                    $obj->xmldbdefault = $xmldbdefault;
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
        $r.= '    <p class="centerpara">' . $this->str['wrongdefaults'] . ': ' . count($wrong_fields) . '</p>';
        $r.= '  </td></tr>';
        $r.= '  <tr><td class="generalboxcontent">';

    /// If we have found wrong defaults inform about them
        if (count($wrong_fields)) {
            $r.= '    <p class="centerpara">' . $this->str['yeswrongdefaultsfound'] . '</p>';
            $r.= '        <ul>';
            foreach ($wrong_fields as $obj) {
                $xmldb_table = $obj->table;
                $xmldb_field = $obj->field;
                $physicaldefault = $obj->physicaldefault;
                $xmldbdefault = $obj->xmldbdefault;

                // get the alter table command
                $sqlarr = $dbman->generator->getAlterFieldSQL($xmldb_table, $xmldb_field);

                $r.= '            <li>' . $this->str['table'] . ': ' . $xmldb_table->getName() . '. ' .
                                          $this->str['field'] . ': ' . $xmldb_field->getName() . ', ' .
                                          $this->str['expected'] . ' ' . "'$xmldbdefault'" . ' ' .
                                          $this->str['actual'] . ' ' . "'$physicaldefault'" . '</li>';
                /// Add to output if we have sentences
                if ($sqlarr) {
                    $sqlarr = $dbman->generator->getEndedStatements($sqlarr);
                    $s.= '<code>' . str_replace("\n", '<br />', implode('<br />', $sqlarr)) . '</code><br />';
                }
            }
            $r.= '        </ul>';
        /// Add the SQL statements (all together)
            $r.= '<hr />' . $s;
        } else {
            $r.= '    <p class="centerpara">' . $this->str['nowrongdefaultsfound'] . '</p>';
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
