<?php  //$Id: upgrade.php,v 1.26 2009/05/01 01:19:19 stronk7 Exp $

// This file keeps track of upgrades to 
// the feedback module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

function xmldb_feedback_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    $result = true;

    if ($result && $oldversion < 2007012310) {

        //create a new table feedback_completedtmp and the field-definition
        $table = new xmldb_table('feedback_completedtmp');

        $field = new xmldb_field('id');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, true, null, null);
        $table->addField($field);
        
        $field = new xmldb_field('feedback');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', null);
        $table->addField($field);
        
        $field = new xmldb_field('userid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', null);
        $table->addField($field);

        $field = new xmldb_field('guestid');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, false, '', null);
        $table->addField($field);

        $field = new xmldb_field('timemodified');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', null);
        $table->addField($field);
        
        $key = new xmldb_key('PRIMARY');
        $key->set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($key);
        
        $key = new xmldb_key('feedback');
        $key->set_attributes(XMLDB_KEY_FOREIGN, array('feedback'), 'feedback', 'id');
        $table->addKey($key);

        $dbman->create_table($table);
        ////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////
        //create a new table feedback_valuetmp and the field-definition
        $table = new xmldb_table('feedback_valuetmp');

        $field = new xmldb_field('id');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, true, null, null);
        $table->addField($field);
        
        $field = new xmldb_field('course_id');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', null);
        $table->addField($field);
        
        $field = new xmldb_field('item');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', null);
        $table->addField($field);
        
        $field = new xmldb_field('completed');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', null);
        $table->addField($field);
        
        $field = new xmldb_field('tmp_completed');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', null);
        $table->addField($field);

        $field = new xmldb_field('value');
        $field->set_attributes(XMLDB_TYPE_TEXT, null, null, null, false, '', null);
        $table->addField($field);
        
        $key = new xmldb_key('PRIMARY');
        $key->set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($key);
        
        $key = new xmldb_key('feedback');
        $key->set_attributes(XMLDB_KEY_FOREIGN, array('item'), 'feedback_item', 'id');
        $table->addKey($key);

        $dbman->create_table($table);
        ////////////////////////////////////////////////////////////
        upgrade_mod_savepoint($result, 2007012310, 'feedback');
    }

    if ($result && $oldversion < 2007050504) {

        /// Define field random_response to be added to feedback_completed
        $table = new xmldb_table('feedback_completed');
        $field = new xmldb_field('random_response', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', null);
        /// Launch add field1
        $dbman->add_field($table, $field);

        /// Define field anonymous_response to be added to feedback_completed
        $table = new xmldb_table('feedback_completed');
        $field = new xmldb_field('anonymous_response', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '1', null);
        /// Launch add field2
        $dbman->add_field($table, $field);

        /// Define field random_response to be added to feedback_completed
        $table = new xmldb_table('feedback_completedtmp');
        $field = new xmldb_field('random_response', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', null);
        /// Launch add field1
        $dbman->add_field($table, $field);

        /// Define field anonymous_response to be added to feedback_completed
        $table = new xmldb_table('feedback_completedtmp');
        $field = new xmldb_field('anonymous_response', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '1', null);
        /// Launch add field2
        $dbman->add_field($table, $field);

        ////////////////////////////////////////////////////////////
        upgrade_mod_savepoint($result, 2007050504, 'feedback');
    }

    if ($result && $oldversion < 2007102600) {
        // public is a reserved word on Oracle

        $table = new xmldb_table('feedback_template');
        $field = new xmldb_field('ispublic', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '1', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint($result, 2007102600, 'feedback');
    }

    if ($result && $oldversion < 2008042400) { //New version in version.php
        if ($all_nonanonymous_feedbacks = $DB->get_records('feedback', 'anonymous', 2)) {
            $update_sql = 'UPDATE {feedback_completed} SET anonymous_response = 2 WHERE feedback = ';
            foreach ($all_nonanonymous_feedbacks as $fb) {
                $result = $result && $DB->execute($update_sql.$fb->id);
            }
        }
        upgrade_mod_savepoint($result, 2008042400, 'feedback');
    }

    if ($result && $oldversion < 2008042401) { //New version in version.php
        if ($result) {
            $concat_radio    = $DB->sql_concat("'r>>>>>'",'presentation');
            $concat_check    = $DB->sql_concat("'c>>>>>'",'presentation');
            $concat_dropdown = $DB->sql_concat("'d>>>>>'",'presentation');
            
            $update_sql1 = "UPDATE {feedback_item} SET presentation = ".$concat_radio." WHERE typ IN('radio','radiorated')";
            $update_sql2 = "UPDATE {feedback_item} SET presentation = ".$concat_dropdown." WHERE typ IN('dropdown','dropdownrated')";
            $update_sql3 = "UPDATE {feedback_item} SET presentation = ".$concat_check." WHERE typ = 'check'";
            
            $result = $result && $DB->execute($update_sql1);
            $result = $result && $DB->execute($update_sql2);
            $result = $result && $DB->execute($update_sql3);
        }
        if ($result) {
            $update_sql1 = "UPDATE {feedback_item} SET typ = 'multichoice' WHERE typ IN('radio','check','dropdown')";
            $update_sql2 = "UPDATE {feedback_item} SET typ = 'multichoicerated' WHERE typ IN('radiorated','dropdownrated')";
            $result = $result && $DB->execute($update_sql1);            
            $result = $result && $DB->execute($update_sql2);            
        }
        upgrade_mod_savepoint($result, 2008042401, 'feedback');
    }

    if ($result && $oldversion < 2008042801) {
        $new_log_display = new object();
        $new_log_display->module = 'feedback';
        $new_log_display->action = 'startcomplete';
        $new_log_display->mtable = 'feedback';
        $new_log_display->field = 'name';
        $result = $result && $DB->insert_record('log_display', $new_log_display);
        
        $new_log_display = clone($new_log_display);
        $new_log_display->action = 'submit';
        $result = $result && $DB->insert_record('log_display', $new_log_display);
        
        $new_log_display = clone($new_log_display);
        $new_log_display->action = 'delete';
        $result = $result && $DB->insert_record('log_display', $new_log_display);
        
        $new_log_display = clone($new_log_display);
        $new_log_display->action = 'view';
        $result = $result && $DB->insert_record('log_display', $new_log_display);
        
        $new_log_display = clone($new_log_display);
        $new_log_display->action = 'view all';
        $new_log_display->mtable = 'course';
        $new_log_display->field = 'shortname';
        $result = $result && $DB->insert_record('log_display', $new_log_display);

        upgrade_mod_savepoint($result, 2008042801, 'feedback');
    }

    if ($result && $oldversion < 2008042900) {
        /// Define field autonumbering to be added to feedback
        $table = new xmldb_table('feedback');
        $field = new xmldb_field('autonumbering', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'multiple_submit');
        /// Launch add field2
        $dbman->add_field($table, $field);

        upgrade_mod_savepoint($result, 2008042900, 'feedback');
    }

    if ($result && $oldversion < 2008050104) {
        /// Define field site_after_submit to be added to feedback
        $table = new xmldb_table('feedback');
        $field = new xmldb_field('site_after_submit', XMLDB_TYPE_CHAR, '255', null, null, false, '', 'autonumbering');
        /// Launch add field2
        $dbman->add_field($table, $field);

        upgrade_mod_savepoint($result, 2008050104, 'feedback');
    }
    
    if ($result && $oldversion < 2008050105) {
        //field count is not more needed
        $table = new xmldb_table('feedback_tracking');
        $field = new xmldb_field('count');
        $dbman->drop_field($table, $field);

        upgrade_mod_savepoint($result, 2008050105, 'feedback');
    }
    
    if ($result && $oldversion < 2008073002) {
        $update_sql = "UPDATE {feedback_item} SET presentation = '-|-' WHERE presentation = '0|0' AND typ = 'numeric'";
        $result = $result && $DB->execute($update_sql);
        
        upgrade_mod_savepoint($result, 2008073002, 'feedback');
    }
    
    if ($result && $oldversion < 2009031301) {
        /// Define field label to be added to feedback_item
        $table = new xmldb_table('feedback_item');
        $field = new xmldb_field('label', XMLDB_TYPE_CHAR, '255', null, null, false, '', 'name');
        /// Launch add field2
        $dbman->add_field($table, $field);

        upgrade_mod_savepoint($result, 2009031301, 'feedback');
    }

    if ($result && $oldversion < 2009042000) {

    /// Rename field summary on table feedback to intro
        $table = new xmldb_table('feedback');
        $field = new xmldb_field('summary', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, 'name');

    /// Launch rename field summary
        $dbman->rename_field($table, $field, 'intro');

    /// feedback savepoint reached
        upgrade_mod_savepoint($result, 2009042000, 'feedback');
    }

    if ($result && $oldversion < 2009042001) {

    /// Define field introformat to be added to feedback
        $table = new xmldb_table('feedback');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

    /// Launch add field introformat
        $dbman->add_field($table, $field);

    /// feedback savepoint reached
        upgrade_mod_savepoint($result, 2009042001, 'feedback');
    }

    return $result;
}

?>
