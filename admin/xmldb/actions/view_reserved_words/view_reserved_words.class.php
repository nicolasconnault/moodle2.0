<?php // $Id: view_reserved_words.class.php,v 1.7 2008/09/03 07:48:19 skodak Exp $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas        http://dougiamas.com  //
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

/// This class will show all the reserved words in a format suitable to
/// be pasted to: http://docs.moodle.org/en/XMLDB_reserved_words and
/// http://docs.moodle.org/en/Database_reserved_words
/// Also, it introspects te DB looking for such words and informing about

class view_reserved_words extends XMLDBAction {

    /**
     * Init method, every subclass will have its own
     */
    function init() {
        parent::init();

    /// Set own custom attributes

    /// Get needed strings
        $this->loadStrings(array(
            'listreservedwords' => 'xmldb',
            'wrongreservedwords' => 'xmldb',
            'table' => 'xmldb',
            'field' => 'xmldb',
            'back' => 'xmldb'
        ));
    }

    /**
     * Invoke method, every class will have its own
     * returns true/false on completion, setting both
     * errormsg and output as necessary
     */
    function invoke() {
        parent::invoke();

        $result = true;

    /// Set own core attributes
        $this->does_generate = ACTION_GENERATE_HTML;

    /// These are always here
        global $CFG, $XMLDB, $DB;

    /// Calculate list of available SQL generators
        require_once("$CFG->libdir/ddl/sql_generator.php");
        $reserved_words = sql_generator::getAllReservedWords();

    /// Now, calculate, looking into current DB (with AdoDB Metadata), which fields are
    /// in the list of reserved words
        $wronguses = array();
        $dbtables = $DB->get_tables();
        if ($dbtables) {
            foreach ($dbtables as $table) {
                if (array_key_exists($table, $reserved_words)) {
                    $wronguses[] = $this->str['table'] . ' - ' . $table . ' (' . implode(', ',$reserved_words[$table]) . ')';

                }
                $dbfields = $DB->get_columns($table);
                if ($dbfields) {
                    foreach ($dbfields as $dbfield) {
                        if (array_key_exists($dbfield->name, $reserved_words)) {
                            $wronguses[] = $this->str['field'] . ' - ' . $table . '->' . $dbfield->name . ' (' . implode(', ',$reserved_words[$dbfield->name]) . ')';
                        }
                    }
                }
            }
        }

    /// Sort the wrong uses
        sort($wronguses);

    /// The back to edit table button
        $b = ' <p class="centerpara buttons">';
        $b .= '<a href="index.php">[' . $this->str['back'] . ']</a>';
        $b .= '</p>';
        $o = $b;

    /// The list of currently wrong field names
        if ($wronguses) {
            $o.= '    <table id="formelements" class="boxaligncenter" cellpadding="5">';
            $o.= '      <tr><td align="center"><font color="red">' . $this->str['wrongreservedwords'] . '</font></td></tr>';
            $o.= '      <tr><td>';
            $o.= '        <ul><li>' . implode('</li><li>', $wronguses) . '</li></ul>';
            $o.= '      </td></tr>';
            $o.= '    </table>';
        }

    /// The textarea showing all the reserved words
        $o.= '    <table id="formelements" class="boxaligncenter" cellpadding="5">';
        $o.= '      <tr><td align="center">' . $this->str['listreservedwords'].'</td></tr>';
        $o.= '      <tr><td><textarea cols="80" rows="32">';
        $o.= s(implode(', ', array_keys($reserved_words)));
        $o.= '</textarea></td></tr>';
        $o.= '    </table>';

        $this->output = $o;

    /// Launch postaction if exists (leave this here!)
        if ($this->getPostAction() && $result) {
            return $this->launch($this->getPostAction());
        }

    /// Return ok if arrived here
        return $result;
    }
}
?>
