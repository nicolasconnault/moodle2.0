<?php
/**
 * Created on 01/12/2008
 *
 * user core functions
 *
 * @author Jerome Mouneyrac
 */

/**
 * DO NOT USE ANYTHING FROM THIS FILE - WORK IN PROGRESS
 */

    /**
     * Returns a subset of users (DO NOT COUNT)
     * @global object $DB
     * @param string $sort A SQL snippet for the sorting criteria to use
     * @param string $recordsperpage how many records do pages have
     * @param string $page which page to return (starts from 0)
     * @param string $fields A comma separated list of fields to be returned from the chosen table.
     * @param object $selectioncriteria:
     *      ->search         string     A simple string to search for
     *      ->confirmed      bool       A switch to allow/disallow unconfirmed users
     *      ->exceptions     array(int) A list of IDs to ignore, eg 2,4,5,8,9,10
     *      ->firstinitial   string     ?
     *      ->lastinitial    string     ?
     * @return array|false Array of {@link $USER} objects. False is returned if an error is encountered.
     */
    function tmp_get_users($sort='firstname ASC', $recordsperpage=999999, $page=0, $fields='*', $selectioncriteria=NULL) {
        global $DB;

         ///WS: convert array into an object
        if (!empty($selectioncriteria) && is_array($selectioncriteria))  {
            $selectioncriteria = (object) $selectioncriteria;
        }

        $LIKE      = $DB->sql_ilike();
        $fullname  = $DB->sql_fullname();

        $select = " username <> :guest AND deleted = 0";
        $params = array('guest'=>'guest');

        if (!empty($selectioncriteria->search)){
            $selectioncriteria->search = trim($selectioncriteria->search);
            $select .= " AND ($fullname $LIKE :search1 OR email $LIKE :search2 OR username = :search3)";
            $params['search1'] = "%".$selectioncriteria->search."%";
            $params['search2'] = "%".$selectioncriteria->search."%";
            $params['search3'] = $selectioncriteria->search;
        }

        if (!empty($selectioncriteria->confirmed)) {
            $select .= " AND confirmed = 1";
        }

        if (!empty($selectioncriteria->exceptions)) {
            list($selectioncriteria->exceptions, $eparams) = $DB->get_in_or_equal($selectioncriteria->exceptions, SQL_PARAMS_NAMED, 'ex0000', false);
            $params = $params + $eparams;
            $except = " AND id ".$selectioncriteria->exceptions;
        }

        if (!empty($selectioncriteria->firstinitial)) {
            $select .= " AND firstname $LIKE :fni";
            $params['fni'] = $selectioncriteria->firstinitial."%";
        }
        if (!empty($selectioncriteria->lastinitial)) {
            $select .= " AND lastname $LIKE :lni";
            $params['lni'] = $selectioncriteria->lastinitial."%";
        }

        if (!empty($selectioncriteria->extraselect)) {
            $select .= " AND ".$selectioncriteria->extraselect;
            if (empty($selectioncriteria->extraparams)){
                $params = $params + (array)$selectioncriteria->extraparams;
            }
        }

        return $DB->get_records_select('user', $select, $params, $sort, $fields, $page, $recordsperpage);
    }

   
    /**
     * Creates an User with given information. Required fields are:
     * -username
     * -idnumber
     * -firstname
     * -lastname
     * -email
     *
     * And there's some interesting fields:
     * -password
     * -auth
     * -confirmed
     * -timezone
     * -country
     * -emailstop
     * -theme
     * -lang
     * -mailformat
     *
     * @param assoc array or object $user
     *
     * @return userid or thrown exceptions
     */
    function tmp_create_user($user) {
        global $CFG, $DB;
     ///WS: convert user array into an user object
        if (is_array($user))  {
            $user = (object) $user;
        }

     ///check password and auth fields
        if (!isset($user->password)) {
            $user->password = '';
        }
        if (!isset($user->auth)) {
            $user->auth = 'manual';
        }

        $required = array('username','firstname','lastname','email');
        foreach ($required as $req) {
            if (!isset($user->{$req})) {
                throw new moodle_exception('missingerequiredfield');
            }
        }

        $record = create_user_record($user->username, $user->password, $user->auth);
        if ($record) {
            $user->id = $record->id;
            if ($DB->update_record('user',$user)) {
                return $record->id;
            } else {
                $DB->delete_record('user',array('id' => $record->id));
            }
        }
        throw new moodle_exception('couldnotcreateuser');
    }

    

    /**
     * Update a user record from its id
     * Warning: no checks are done on the data!!!
     * @param object $user 
     */
    function tmp_update_user($user) {
        global $DB;
        if ($DB->update_record('user', $user)) {
            $DB->commit_sql();
            events_trigger('user_updated', $user);
            return true;
        } else {
            $DB->rollback_sql();
            return false;
        }
    }



?>