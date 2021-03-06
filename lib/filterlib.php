<?php // $Id: filterlib.php,v 1.49 2009/05/09 14:21:11 tjhunt Exp $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
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

/**
 * Library functions for managing text filter plugins.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodlecore
 */

/**
 * The states a filter can be in, stored in the filter_active table.
 */
define('TEXTFILTER_ON', 1);
define('TEXTFILTER_INHERIT', 0);
define('TEXTFILTER_OFF', -1);
define('TEXTFILTER_DISABLED', -9999);

/**
 * Class to manage the filtering of strings. It is intended that this class is
 * only used by weblib.php. Client code should probably be using the
 * format_text and format_string functions.
 *
 * This class is a singleton.
 */
class filter_manager {
    /** This list of active filters, by context, for filtering content.
     * An array contextid => array of filter objects. */
    protected $textfilters = array();

    /** This list of active filters, by context, for filtering strings.
     * An array contextid => array of filter objects. */
    protected $stringfilters = array();

    /** Exploded version of $CFG->stringfilters. */
    protected $stringfilternames = array();

    /** Holds the singleton instance. */
    protected static $singletoninstance;

    protected function __construct() {
        $this->stringfilternames = filter_get_string_filters();
    }

    /**
     * @return the singleton instance.
     */
    public static function instance() {
        if (is_null(self::$singletoninstance)) {
            global $CFG;
            if (!empty($CFG->perfdebug)) {
                self::$singletoninstance = new performance_measuring_filter_manager();
            } else {
                self::$singletoninstance = new self();
            }
        }
        return self::$singletoninstance;
    }

    /** Load all the filters required by this context. */
    protected function load_filters($context, $courseid) {
        $filters = filter_get_active_in_context($context);
        $this->textfilters[$context->id] = array();
        $this->stringfilters[$context->id] = array();
        foreach ($filters as $filtername => $localconfig) {
            $filter = $this->make_filter_object($filtername, $context, $courseid, $localconfig);
            if (is_null($filter)) {
                continue;
            }
            $this->textfilters[$context->id][] = $filter;
            if (in_array($filtername, $this->stringfilternames)) {
                $this->stringfilters[$context->id][] = $filter;
            }
        }
    }

    /**
     * Factory method for creating a filter.
     * @param string $filter The filter name, for example 'filter/tex' or 'mod/glossary'.
     * @param $context context object.
     * @param $courseid course if.
     * @param $localconfig array of local configuration variables for this filter.
     * @return moodle_text_filter The filter, or null, if this type of filter is
     *      not recognised or could not be created.
     */
    protected function make_filter_object($filtername, $context, $courseid, $localconfig) {
        global $CFG;
        $path = $CFG->dirroot .'/'. $filtername .'/filter.php';
        if (!is_readable($path)) {
            return null;
        }
        include_once($path);

        $filterclassname = basename($filtername) . '_filter';
        if (class_exists($filterclassname)) {
            return new $filterclassname($courseid, $context, $localconfig);
        }

        $legacyfunctionname = basename($filtername) . '_filter';
        if (function_exists($legacyfunctionname)) {
            return new legacy_filter($legacyfunctionname, $courseid, $context, $localconfig);
        }

        return null;
    }

    protected function apply_filter_chain($text, $filterchain) {
        foreach ($filterchain as $filter) {
            $text = $filter->filter($text);
        }
        return $text;
    }

    protected function get_text_filters($context, $courseid) {
        if (!isset($this->textfilters[$context->id])) {
            $this->load_filters($context, $courseid);
        }
        return $this->textfilters[$context->id];
    }

    protected function get_string_filters($context, $courseid) {
        if (!isset($this->stringfilters[$context->id])) {
            $this->load_filters($context, $courseid);
        }
        return $this->stringfilters[$context->id];
    }

    public function filter_text($text, $context, $courseid) {
        $text = $this->apply_filter_chain($text, $this->get_text_filters($context, $courseid));
        /// <nolink> tags removed for XHTML compatibility
        $text = str_replace(array('<nolink>', '</nolink>'), '', $text);
        return $text;
    }

    public function filter_string($string, $context, $courseid) {
        return $this->apply_filter_chain($string, $this->get_string_filters($context, $courseid));
    }

    public function text_filtering_hash($context, $courseid) {
        $filters = $this->get_text_filters($context, $courseid);
        $hashes = array();
        foreach ($filters as $filter) {
            $hashes[] = $filter->hash();
        }
        return implode('-', $hashes);
    }
}

/**
 * Filter manager subclass that does nothing. Having this simplifies the logic
 * of format_text, etc.
 */
class null_filter_manager {
    public function filter_text($text, $context, $courseid) {
        return $text;
    }

    public function filter_string($string, $context, $courseid) {
        return $string;
    }

    public function text_filtering_hash() {
        return '';
    }
}

/**
 * Filter manager subclass that tacks how much work it does.
 */
class performance_measuring_filter_manager extends filter_manager {
    protected $filterscreated = 0;
    protected $textsfiltered = 0;
    protected $stringsfiltered = 0;

    protected function make_filter_object($filtername, $context, $courseid, $localconfig) {
        $this->filterscreated++;
        return parent::make_filter_object($filtername, $context, $courseid, $localconfig);
    }

    public function filter_text($text, $context, $courseid) {
        $this->textsfiltered++;
        return parent::filter_text($text, $context, $courseid);
    }

    public function filter_string($string, $context, $courseid) {
        $this->stringsfiltered++;
        return parent::filter_string($string, $context, $courseid);
    }

    public function get_performance_summary() {
        return array(array(
            'contextswithfilters' => count($this->textfilters),
            'filterscreated' => $this->filterscreated,
            'textsfiltered' => $this->textsfiltered,
            'stringsfiltered' => $this->stringsfiltered,
        ), array(
            'contextswithfilters' => 'Contexts for which filters were loaded',
            'filterscreated' => 'Filters created',
            'textsfiltered' => 'Pieces of content filtered',
            'stringsfiltered' => 'Strings filtered',
        ));
    }
}

/**
 * Base class for text filters. You just need to override this class and
 * implement the filter method.
 */
abstract class moodle_text_filter {
    /** The course we are in. */
    protected $courseid;
    /** The context we are in. */
    protected $context;
    /** Any local configuration for this filter in this context. */
    protected $localconfig;

    /**
     * Set any context-specific configuration for this filter.
     * @param object $context The current course id.
     * @param object $context The current context.
     * @param array $config Any context-specific configuration for this filter.
     */
    public function __construct($courseid, $context, array $localconfig) {
        $this->courseid = $courseid;
        $this->context = $context;
        $this->localconfig = $localconfig;
    }

    public function hash() {
        return __CLASS__;
    }

    /**
     * Override this funciton to actually implement the filtering.
     * @param $text some HTML content.
     * @return the HTML content after the filtering has been applied.
     */
    public abstract function filter($text);
}

/**
 * moodle_text_filter implementation that encapsulates an old-style filter that
 * only defines a function, not a class.
 */
class legacy_filter extends moodle_text_filter {
    protected $filterfunction;

    /**
     * Set any context-specific configuration for this filter.
     * @param string $filterfunction
     * @param object $context The current course id.
     * @param object $context The current context.
     * @param array $config Any context-specific configuration for this filter.
     */
    public function __construct($filterfunction, $courseid, $context, array $localconfig) {
        parent::__construct($courseid, $context, $localconfig);
        $this->filterfunction = $filterfunction;
    }

    public function filter($text) {
        return call_user_func($this->filterfunction, $this->courseid, $text);
    }
}

/// Define one exclusive separator that we'll use in the temp saved tags
/// keys. It must be something rare enough to avoid having matches with
/// filterobjects. MDL-18165
define('EXCL_SEPARATOR', '-%-');

/**
 * This is just a little object to define a phrase and some instructions 
 * for how to process it.  Filters can create an array of these to pass 
 * to the filter_phrases function below.
 **/
class filterobject {
    var $phrase;
    var $hreftagbegin;
    var $hreftagend;
    var $casesensitive;
    var $fullmatch;
    var $replacementphrase;
    var $work_phrase;
    var $work_hreftagbegin;
    var $work_hreftagend;
    var $work_casesensitive;
    var $work_fullmatch;
    var $work_replacementphrase;
    var $work_calculated;

    /// a constructor just because I like constructing
    function filterobject($phrase, $hreftagbegin='<span class="highlight">', 
                                   $hreftagend='</span>', 
                                   $casesensitive=false, 
                                   $fullmatch=false,
                                   $replacementphrase=NULL) {

        $this->phrase           = $phrase;
        $this->hreftagbegin     = $hreftagbegin;
        $this->hreftagend       = $hreftagend;
        $this->casesensitive    = $casesensitive;
        $this->fullmatch        = $fullmatch;
        $this->replacementphrase= $replacementphrase;
        $this->work_calculated  = false;

    }
}

/**
 * Look up the name of this filter in the most appropriate location.
 * If $filterlocation = 'mod' then does get_string('filtername', $filter);
 * else if $filterlocation = 'filter' then does get_string('filtername', 'filter_' . $filter);
 * with a fallback to get_string('filtername', $filter) for backwards compatibility.
 * These are the only two options supported at the moment.
 * @param string $filterlocation 'filter' or 'mod'.
 * @param string $filter the folder name where the filter lives.
 * @return string the human-readable name for this filter.
 */
function filter_get_name($filter) {
    list($type, $filter) = explode('/', $filter);
    switch ($type) {
        case 'filter':
            $strfiltername = get_string('filtername', 'filter_' . $filter);
            if (substr($strfiltername, 0, 2) != '[[') {
                // found a valid string.
                return $strfiltername;
            }
            // Fall through to try the legacy location.

        case 'mod':
            $strfiltername = get_string('filtername', $filter);
            if (substr($strfiltername, 0, 2) == '[[') {
                $strfiltername .= ' (' . $type . '/' . $filter . ')';
            }
            return $strfiltername;

        default:
            throw new coding_exception('Unknown filter type ' . $type);
    }
}

/**
 * Get the names of all the filters installed in this Moodle.
 * @return array path => filter name from the appropriate lang file. e.g.
 * array('mod/glossary' => 'Glossary Auto-linking', 'filter/tex' => 'TeX Notation');
 * sorted in alphabetical order of name.
 */
function filter_get_all_installed() {
    global $CFG;
    $filternames = array();
    $filterlocations = array('mod', 'filter');
    foreach ($filterlocations as $filterlocation) {
        $filters = get_list_of_plugins($filterlocation);
        foreach ($filters as $filter) {
            $path = $filterlocation . '/' . $filter;
            if (is_readable($CFG->dirroot . '/' . $path . '/filter.php')) {
                $strfiltername = filter_get_name($path);
                $filternames[$path] = $strfiltername;
            }
        }
    }
    asort($filternames, SORT_LOCALE_STRING);
    return $filternames;
}

/**
 * Set the global activated state for a text filter.
 * @param string $filter The filter name, for example 'filter/tex' or 'mod/glossary'.
 * @param integer $state One of the values TEXTFILTER_ON, TEXTFILTER_OFF or TEXTFILTER_DISABLED.
 * @param integer $sortorder (optional) a position in the sortorder to place this filter.
 *      If not given defaults to:
 *      No change in order if we are updating an exsiting record, and not changing to or from TEXTFILTER_DISABLED.
 *      Just after the last currently active filter when adding an unknown filter
 *          in state TEXTFILTER_ON or TEXTFILTER_OFF, or enabling/diabling an exsisting filter.
 *      Just after the very last filter when adding an unknown filter in state TEXTFILTER_DISABLED
 */
function filter_set_global_state($filter, $state, $sortorder = false) {
    global $DB;

    // Check requested state is valid.
    if (!in_array($state, array(TEXTFILTER_ON, TEXTFILTER_OFF, TEXTFILTER_DISABLED))) {
        throw new coding_exception("Illegal option '$state' passed to filter_set_global_state. " .
                "Must be one of TEXTFILTER_ON, TEXTFILTER_OFF or TEXTFILTER_DISABLED.");
    }

    // Check sortorder is valid.
    if ($sortorder !== false) {
        if ($sortorder < 1 || $sortorder > $DB->get_field('filter_active', 'MAX(sortorder)', array()) + 1) {
            throw new coding_exception("Invalid sort order passed to filter_set_global_state.");
        }
    }

    // See if there is an existing record.
    $syscontext = get_context_instance(CONTEXT_SYSTEM);
    $rec = $DB->get_record('filter_active', array('filter' => $filter, 'contextid' => $syscontext->id));
    if (empty($rec)) {
        $insert = true;
        $rec = new stdClass;
        $rec->filter = $filter;
        $rec->contextid = $syscontext->id;
    } else {
        $insert = false;
        if ($sortorder === false && !($rec->active == TEXTFILTER_DISABLED xor $state == TEXTFILTER_DISABLED)) {
            $sortorder = $rec->sortorder;
        }
    }

    // Automatic sort order.
    if ($sortorder === false) {
        if ($state == TEXTFILTER_DISABLED && $insert) {
            $prevmaxsortorder = $DB->get_field('filter_active', 'MAX(sortorder)', array());
        } else {
            $prevmaxsortorder = $DB->get_field_select('filter_active', 'MAX(sortorder)', 'active <> ?', array(TEXTFILTER_DISABLED));
        }
        if (empty($prevmaxsortorder)) {
            $sortorder = 1;
        } else {
            $sortorder = $prevmaxsortorder + 1;
            if (!$insert && $state == TEXTFILTER_DISABLED) {
                $sortorder = $prevmaxsortorder;
            }
        }
    }

    // Move any existing records out of the way of the sortorder.
    if ($insert) {
        $DB->execute('UPDATE {filter_active} SET sortorder = sortorder + 1 WHERE sortorder >= ?', array($sortorder));
    } else if ($sortorder != $rec->sortorder) {
        $sparesortorder = $DB->get_field('filter_active', 'MIN(sortorder)', array()) - 1;
        $DB->set_field('filter_active', 'sortorder', $sparesortorder, array('filter' => $filter, 'contextid' => $syscontext->id));
        if ($sortorder < $rec->sortorder) {
            $DB->execute('UPDATE {filter_active} SET sortorder = sortorder + 1 WHERE sortorder >= ? AND sortorder < ?',
                    array($sortorder, $rec->sortorder));
        } else if ($sortorder > $rec->sortorder) {
            $DB->execute('UPDATE {filter_active} SET sortorder = sortorder - 1 WHERE sortorder <= ? AND sortorder > ?',
                    array($sortorder, $rec->sortorder));
        }
    }

    // Insert/update the new record.
    $rec->active = $state;
    $rec->sortorder = $sortorder;
    if ($insert) {
        $DB->insert_record('filter_active', $rec);
    } else {
        $DB->update_record('filter_active', $rec);
    }
}

/**
 * @param string $filter The filter name, for example 'filter/tex' or 'mod/glossary'.
 * @return boolean is this filter allowed to be used on this site. That is, the
 *      admin has set the global 'active' setting to On, or Off, but available.
 */
function filter_is_enabled($filter) {
    return array_key_exists($filter, filter_get_globally_enabled());
}

/**
 * Return a list of all the filters that may be in use somewhere.
 * @return array where the keys and values are both the filter name, like 'filter/tex'.
 */
function filter_get_globally_enabled() {
    static $enabledfilters = null;
    if (is_null($enabledfilters)) {
        $filters = filter_get_global_states();
        $enabledfilters = array();
        foreach ($filters as $filter => $filerinfo) {
            if ($filerinfo->active != TEXTFILTER_DISABLED) {
                $enabledfilters[$filter] = $filter;
            }
        }
    }
    return $enabledfilters;
}

/**
 * Return the names of the filters that should also be applied to strings
 * (when they are enabled).
 * @return array where the keys and values are both the filter name, like 'filter/tex'.
 */
function filter_get_string_filters() {
    global $CFG;
    $stringfilters = array();
    if (!empty($CFG->filterall) && !empty($CFG->stringfilters)) {
        $stringfilters = explode(',', $CFG->stringfilters);
        $stringfilters = array_combine($stringfilters, $stringfilters);
    }
    return $stringfilters;
}

/**
 * Sets whether a particular active filter should be applied to all strings by
 * format_string, or just used by format_text.
 * @param string $filter The filter name, for example 'filter/tex' or 'mod/glossary'.
 * @param boolean $applytostrings if true, this filter will apply to format_string
 *      and format_text, when it is enabled.
 */
function filter_set_applies_to_strings($filter, $applytostrings) {
    $stringfilters = filter_get_string_filters();
    $numstringfilters = count($stringfilters);
    if ($applytostrings) {
        $stringfilters[$filter] = $filter;
    } else {
        unset($stringfilters[$filter]);
    }
    if (count($stringfilters) != $numstringfilters) {
        set_config('stringfilters', implode(',', $stringfilters));
        set_config('filterall', !empty($stringfilters));
    }
}

/**
 * Set the local activated state for a text filter.
 * @param string $filter The filter name, for example 'filter/tex' or 'mod/glossary'.
 * @param integer $contextid The id of the context to get the local config for.
 * @param integer $state One of the values TEXTFILTER_ON, TEXTFILTER_OFF or TEXTFILTER_INHERIT.
 */
function filter_set_local_state($filter, $contextid, $state) {
    global $DB;

    // Check requested state is valid.
    if (!in_array($state, array(TEXTFILTER_ON, TEXTFILTER_OFF, TEXTFILTER_INHERIT))) {
        throw new coding_exception("Illegal option '$state' passed to filter_set_local_state. " .
                "Must be one of TEXTFILTER_ON, TEXTFILTER_OFF or TEXTFILTER_INHERIT.");
    }

    if ($contextid == get_context_instance(CONTEXT_SYSTEM)->id) {
        throw new coding_exception('You cannot use filter_set_local_state ' .
                'with $contextid equal to the system context id.');
    }

    if ($state == TEXTFILTER_INHERIT) {
        $DB->delete_records('filter_active', array('filter' => $filter, 'contextid' => $contextid));
        return;
    }

    $rec = $DB->get_record('filter_active', array('filter' => $filter, 'contextid' => $contextid));
    $insert = false;
    if (empty($rec)) {
        $insert = true;
        $rec = new stdClass;
        $rec->filter = $filter;
        $rec->contextid = $contextid;
    }

    $rec->active = $state;

    if ($insert) {
        $DB->insert_record('filter_active', $rec);
    } else {
        $DB->update_record('filter_active', $rec);
    }
}

/**
 * Set a particular local config variable for a filter in a context.
 * @param string $filter The filter name, for example 'filter/tex' or 'mod/glossary'.
 * @param integer $contextid The id of the context to get the local config for.
 * @param string $name the setting name.
 * @param string $value the corresponding value.
 */
function filter_set_local_config($filter, $contextid, $name, $value) {
    global $DB;
    $rec = $DB->get_record('filter_config', array('filter' => $filter, 'contextid' => $contextid, 'name' => $name));
    $insert = false;
    if (empty($rec)) {
        $insert = true;
        $rec = new stdClass;
        $rec->filter = $filter;
        $rec->contextid = $contextid;
        $rec->name = $name;
    }

    $rec->value = $value;

    if ($insert) {
        $DB->insert_record('filter_config', $rec);
    } else {
        $DB->update_record('filter_config', $rec);
    }
}

/**
 * Remove a particular local config variable for a filter in a context.
 * @param string $filter The filter name, for example 'filter/tex' or 'mod/glossary'.
 * @param integer $contextid The id of the context to get the local config for.
 * @param string $name the setting name.
 */
function filter_unset_local_config($filter, $contextid, $name) {
    global $DB;
    $DB->delete_records('filter_config', array('filter' => $filter, 'contextid' => $contextid, 'name' => $name));
}

/**
 * Get local config variables for a filter in a context. Normally (when your
 * filter is running) you don't need to call this, becuase the config is fetched
 * for you automatically. You only need this, for example, when you are getting
 * the config so you can show the user an editing from.
 * @param string $filter The filter name, for example 'filter/tex' or 'mod/glossary'.
 * @param integer $contextid The ID of the context to get the local config for.
 * @return array of name => value pairs.
 */
function filter_get_local_config($filter, $contextid) {
    global $DB;
    return $DB->get_records_menu('filter_config', array('filter' => $filter, 'contextid' => $contextid), '', 'name,value');
}

/**
 * This function is for use by backup. Gets all the filter information specific
 * to one context.
 * @return array with two elements. The first element is an array of objects with
 *      fields filter and active. These come from the filter_active table. The
 *      second element is an array of objects with fields filter, name and value
 *      from the filter_config table.
 */
function filter_get_all_local_settings($contextid) {
    global $DB;
    $context = get_context_instance(CONTEXT_SYSTEM);
    return array(
        $DB->get_records('filter_active', array('contextid' => $contextid), 'filter', 'filter,active'),
        $DB->get_records('filter_config', array('contextid' => $contextid), 'filter,name', 'filter,name,value'),
    );
}

/**
 * Get the list of active filters, in the order that they should be used
 * for a particular context, along with any local configuration variables.
 *
 * @param object $context a context
 *
 * @return array an array where the keys are the filter names, for example
 *      'filter/tex' or 'mod/glossary' and the values are any local
 *      configuration for that filter, as an array of name => value pairs
 *      from the filter_config table. In a lot of cases, this will be an
 *      empty array. So, an example return value for this function might be
 *      array('filter/tex' => array(), 'mod/glossary' => array('glossaryid', 123))
 */
function filter_get_active_in_context($context) {
    global $DB;
    $contextids = str_replace('/', ',', trim($context->path, '/'));

    // The following SQL is tricky. It is explained on
    // http://docs.moodle.org/en/Development:Filter_enable/disable_by_context
    $sql = "SELECT active.filter, fc.name, fc.value
         FROM (SELECT f.filter, MAX(f.sortorder) AS sortorder
             FROM {filter_active} f
             JOIN {context} ctx ON f.contextid = ctx.id
             WHERE ctx.id IN ($contextids)
             GROUP BY filter
             HAVING MAX(f.active * " . $DB->sql_cast_2signed('ctx.depth') .
                    ") > -MIN(f.active * " . $DB->sql_cast_2signed('ctx.depth') . ")
         ) active
         LEFT JOIN {filter_config} fc ON fc.filter = active.filter AND fc.contextid = $context->id
         ORDER BY active.sortorder";
    $rs = $DB->get_recordset_sql($sql);

    // Masssage the data into the specified format to return.
    $filters = array();
    foreach ($rs as $row) {
        if (!isset($filters[$row->filter])) {
            $filters[$row->filter] = array();
        }
        if (!is_null($row->name)) {
            $filters[$row->filter][$row->name] = $row->value;
        }
    }

    $rs->close();

    return $filters;
}

/**
 * List all of the filters that are available in this context, and what the
 * local and interited states of that filter are.
 * @param object $context a context that is not the system context.
 * @return array an array with filter names, for example 'filter/tex' or
 *      'mod/glossary' as keys. and and the values are objects with fields:
 *      ->filter filter name, same as the key.
 *      ->localstate TEXTFILTER_ON/OFF/INHERIT
 *      ->inheritedstate TEXTFILTER_ON/OFF - the state that will be used if localstate is set to TEXTFILTER_INHERIT.
 */
function filter_get_available_in_context($context) {
    global $DB;

    // The complex logic is working out the active state in the parent context,
    // so strip the current context from the list.
    $contextids = explode('/', trim($context->path, '/'));
    array_pop($contextids);
    $contextids = implode(',', $contextids);
    if (empty($contextids)) {
        throw new coding_exception('filter_get_available_in_context cannot be called with the system context.');
    }

    // The following SQL is tricky, in the same way at the SQL in filter_get_active_in_context.
    $sql = "SELECT parent_states.filter,
                CASE WHEN fa.active IS NULL THEN " . TEXTFILTER_INHERIT . "
                ELSE fa.active END AS localstate,
             parent_states.inheritedstate
         FROM (SELECT f.filter, MAX(f.sortorder) AS sortorder,
                    CASE WHEN MAX(f.active * " . $DB->sql_cast_2signed('ctx.depth') .
                            ") > -MIN(f.active * " . $DB->sql_cast_2signed('ctx.depth') . ") THEN " . TEXTFILTER_ON . "
                    ELSE " . TEXTFILTER_OFF . " END AS inheritedstate
             FROM {filter_active} f
             JOIN {context} ctx ON f.contextid = ctx.id
             WHERE ctx.id IN ($contextids)
             GROUP BY f.filter
             HAVING MIN(f.active) > " . TEXTFILTER_DISABLED . "
         ) parent_states
         LEFT JOIN {filter_active} fa ON fa.filter = parent_states.filter AND fa.contextid = $context->id
         ORDER BY parent_states.sortorder";
    return $DB->get_records_sql($sql);
}

/**
 * This function is for use by the filter administration page.
 * @return array 'filtername' => object with fields 'filter' (=filtername), 'active' and 'sortorder'
 */
function filter_get_global_states() {
    global $DB;
    $context = get_context_instance(CONTEXT_SYSTEM);
    return $DB->get_records('filter_active', array('contextid' => $context->id), 'sortorder', 'filter,active,sortorder');
}

/**
 * Delete all the data in the database relating to a filter, prior to deleting it.
 * @param string $filter The filter name, for example 'filter/tex' or 'mod/glossary'.
 */
function filter_delete_all_for_filter($filter) {
    global $DB;
    if (substr($filter, 0, 7) == 'filter/') {
        unset_all_config_for_plugin('filter_' . basename($filter));
    }
    $DB->delete_records('filter_active', array('filter' => $filter));
    $DB->delete_records('filter_config', array('filter' => $filter));
}

/**
 * Delete all the data in the database relating to a context, used when contexts are deleted.
 * @param integer $contextid The id of the context being deleted.
 */
function filter_delete_all_for_context($contextid) {
    global $DB;
    $DB->delete_records('filter_active', array('contextid' => $contextid));
    $DB->delete_records('filter_config', array('contextid' => $contextid));
}

/**
 * Does this filter have a global settings page in the admin tree?
 * (The settings page for a filter must be called, for example,
 * filtersettingfiltertex or filtersettingmodglossay.)
 *
 * @param string $filter The filter name, for example 'filter/tex' or 'mod/glossary'.
 * @return boolean Whether there should be a 'Settings' link on the config page.
 */
function filter_has_global_settings($filter) {
    global $CFG;
    $settingspath = $CFG->dirroot . '/' . $filter . '/filtersettings.php';
    return is_readable($settingspath);
}

/**
 * Does this filter have local (per-context) settings?
 *
 * @param string $filter The filter name, for example 'filter/tex' or 'mod/glossary'.
 * @return boolean Whether there should be a 'Settings' link on the manage filters in context page.
 */
function filter_has_local_settings($filter) {
    global $CFG;
    $settingspath = $CFG->dirroot . '/' . $filter . '/filterlocalsettings.php';
    return is_readable($settingspath);
}

/**
 * Certain types of context (block and user) may not have local filter settings.
 * the function checks a context to see whether it may have local config.
 * @param object $context a context.
 * @return boolean whether this context may have local filter settings.
 */
function filter_context_may_have_filter_settings($context) {
    return $context->contextlevel != CONTEXT_BLOCK && $context->contextlevel != CONTEXT_USER;
}

/**
 * Process phrases intelligently found within a HTML text (such as adding links)
 *
 * param  text             the text that we are filtering
 * param  link_array       an array of filterobjects
 * param  ignoretagsopen   an array of opening tags that we should ignore while filtering
 * param  ignoretagsclose  an array of corresponding closing tags
 **/
function filter_phrases($text, &$link_array, $ignoretagsopen=NULL, $ignoretagsclose=NULL) {

    global $CFG;

    static $usedphrases;

    $ignoretags = array();  //To store all the enclosig tags to be completely ignored
    $tags = array();        //To store all the simple tags to be ignored

/// A list of open/close tags that we should not replace within
/// No reason why you can't put full preg expressions in here too
/// eg '<script(.+?)>' to match any type of script tag
    $filterignoretagsopen  = array('<head>' , '<nolink>' , '<span class="nolink">');
    $filterignoretagsclose = array('</head>', '</nolink>', '</span>');

/// Invalid prefixes and suffixes for the fullmatch searches
/// Every "word" character, but the underscore, is a invalid suffix or prefix.
/// (nice to use this because it includes national characters (accents...) as word characters.
    $filterinvalidprefixes = '([^\W_])';
    $filterinvalidsuffixes = '([^\W_])';

/// Add the user defined ignore tags to the default list
/// Unless specified otherwise, we will not replace within <a></a> tags
    if ( $ignoretagsopen === NULL ) {
        //$ignoretagsopen  = array('<a(.+?)>');
        $ignoretagsopen  = array('<a\s[^>]+?>');
        $ignoretagsclose = array('</a>');
    }
    
    if ( is_array($ignoretagsopen) ) {
        foreach ($ignoretagsopen as $open) $filterignoretagsopen[] = $open;
        foreach ($ignoretagsclose as $close) $filterignoretagsclose[] = $close;
    }

    //// Double up some magic chars to avoid "accidental matches"
    $text = preg_replace('/([#*%])/','\1\1',$text);


////Remove everything enclosed by the ignore tags from $text    
    filter_save_ignore_tags($text,$filterignoretagsopen,$filterignoretagsclose,$ignoretags);

/// Remove tags from $text
    filter_save_tags($text,$tags);

/// Time to cycle through each phrase to be linked
    $size = sizeof($link_array);
    for ($n=0; $n < $size; $n++) {
        $linkobject =& $link_array[$n];

    /// Set some defaults if certain properties are missing
    /// Properties may be missing if the filterobject class has not been used to construct the object
        if (empty($linkobject->phrase)) {
            continue;
        }

    /// Avoid integers < 1000 to be linked. See bug 1446.
        $intcurrent = intval($linkobject->phrase);
        if (!empty($intcurrent) && strval($intcurrent) == $linkobject->phrase && $intcurrent < 1000) {
            continue;
        }

    /// All this work has to be done ONLY it it hasn't been done before
    if (!$linkobject->work_calculated) {
            if (!isset($linkobject->hreftagbegin) or !isset($linkobject->hreftagend)) {
                $linkobject->work_hreftagbegin = '<span class="highlight"';
                $linkobject->work_hreftagend   = '</span>';
            } else {
                $linkobject->work_hreftagbegin = $linkobject->hreftagbegin;
                $linkobject->work_hreftagend   = $linkobject->hreftagend;
            }

        /// Double up chars to protect true duplicates
        /// be cleared up before returning to the user.
            $linkobject->work_hreftagbegin = preg_replace('/([#*%])/','\1\1',$linkobject->work_hreftagbegin);

            if (empty($linkobject->casesensitive)) {
                $linkobject->work_casesensitive = false;
            } else {
                $linkobject->work_casesensitive = true;
            }
            if (empty($linkobject->fullmatch)) {
                $linkobject->work_fullmatch = false;
            } else {
                $linkobject->work_fullmatch = true;
            }

        /// Strip tags out of the phrase
            $linkobject->work_phrase = strip_tags($linkobject->phrase);

        /// Double up chars that might cause a false match -- the duplicates will
        /// be cleared up before returning to the user.
            $linkobject->work_phrase = preg_replace('/([#*%])/','\1\1',$linkobject->work_phrase);

        /// Set the replacement phrase properly
            if ($linkobject->replacementphrase) {    //We have specified a replacement phrase
            /// Strip tags
                $linkobject->work_replacementphrase = strip_tags($linkobject->replacementphrase);
            } else {                                 //The replacement is the original phrase as matched below
                $linkobject->work_replacementphrase = '$1';
            }

        /// Quote any regular expression characters and the delimiter in the work phrase to be searched
            $linkobject->work_phrase = preg_quote($linkobject->work_phrase, '/');

        /// Work calculated
            $linkobject->work_calculated = true;
    
        }

    /// If $CFG->filtermatchoneperpage, avoid previously (request) linked phrases
        if (!empty($CFG->filtermatchoneperpage)) {
            if (!empty($usedphrases) && in_array($linkobject->work_phrase,$usedphrases)) {
                continue;
            }
        }

    /// Regular expression modifiers
        $modifiers = ($linkobject->work_casesensitive) ? 's' : 'isu'; // works in unicode mode!

    /// Do we need to do a fullmatch?
    /// If yes then go through and remove any non full matching entries
        if ($linkobject->work_fullmatch) {
            $notfullmatches = array();
            $regexp = '/'.$filterinvalidprefixes.'('.$linkobject->work_phrase.')|('.$linkobject->work_phrase.')'.$filterinvalidsuffixes.'/'.$modifiers;

            preg_match_all($regexp,$text,$list_of_notfullmatches);

            if ($list_of_notfullmatches) {
                foreach (array_unique($list_of_notfullmatches[0]) as $key=>$value) {
                    $notfullmatches['<*'.$key.'*>'] = $value;
                }
                if (!empty($notfullmatches)) {
                    $text = str_replace($notfullmatches,array_keys($notfullmatches),$text);
                }
            }
        }

    /// Finally we do our highlighting
        if (!empty($CFG->filtermatchonepertext) || !empty($CFG->filtermatchoneperpage)) {
            $resulttext = preg_replace('/('.$linkobject->work_phrase.')/'.$modifiers, 
                                      $linkobject->work_hreftagbegin.
                                      $linkobject->work_replacementphrase.
                                      $linkobject->work_hreftagend, $text, 1);
        } else {
            $resulttext = preg_replace('/('.$linkobject->work_phrase.')/'.$modifiers, 
                                      $linkobject->work_hreftagbegin.
                                      $linkobject->work_replacementphrase.
                                      $linkobject->work_hreftagend, $text);
        }


    /// If the text has changed we have to look for links again
        if ($resulttext != $text) {
        /// Set $text to $resulttext
            $text = $resulttext;
        /// Remove everything enclosed by the ignore tags from $text    
            filter_save_ignore_tags($text,$filterignoretagsopen,$filterignoretagsclose,$ignoretags);
        /// Remove tags from $text
            filter_save_tags($text,$tags);
        /// If $CFG->filtermatchoneperpage, save linked phrases to request
            if (!empty($CFG->filtermatchoneperpage)) {
                $usedphrases[] = $linkobject->work_phrase;
            }
        }


    /// Replace the not full matches before cycling to next link object
        if (!empty($notfullmatches)) {
            $text = str_replace(array_keys($notfullmatches),$notfullmatches,$text);
            unset($notfullmatches);
        }
    }

/// Rebuild the text with all the excluded areas

    if (!empty($tags)) {
        $text = str_replace(array_keys($tags), $tags, $text);
    }

    if (!empty($ignoretags)) {
        $ignoretags = array_reverse($ignoretags); /// Reversed so "progressive" str_replace() will solve some nesting problems.
        $text = str_replace(array_keys($ignoretags),$ignoretags,$text);
    }

    //// Remove the protective doubleups 
    $text =  preg_replace('/([#*%])(\1)/','\1',$text);

/// Add missing javascript for popus
    $text = filter_add_javascript($text);


    return $text;
}

function filter_remove_duplicates($linkarray) {

    $concepts  = array(); // keep a record of concepts as we cycle through
    $lconcepts = array(); // a lower case version for case insensitive

    $cleanlinks = array();
    
    foreach ($linkarray as $key=>$filterobject) {
        if ($filterobject->casesensitive) {
            $exists = in_array($filterobject->phrase, $concepts);
        } else {
            $exists = in_array(moodle_strtolower($filterobject->phrase), $lconcepts);
        }
        
        if (!$exists) {
            $cleanlinks[] = $filterobject;
            $concepts[] = $filterobject->phrase;
            $lconcepts[] = moodle_strtolower($filterobject->phrase);
        }
    }

    return $cleanlinks;
}

/**
 * Extract open/lose tags and their contents to avoid being processed by filters.
 * Useful to extract pieces of code like <a>...</a> tags. It returns the text
 * converted with some <#xEXCL_SEPARATORx#> codes replacing the extracted text. Such extracted
 * texts are returned in the ignoretags array (as values), with codes as keys.
 *
 * param  text                  the text that we are filtering (in/out)
 * param  filterignoretagsopen  an array of open tags to start searching
 * param  filterignoretagsclose an array of close tags to end searching 
 * param  ignoretags            an array of saved strings useful to rebuild the original text (in/out)
 **/
function filter_save_ignore_tags(&$text,$filterignoretagsopen,$filterignoretagsclose,&$ignoretags) {

/// Remove everything enclosed by the ignore tags from $text
    foreach ($filterignoretagsopen as $ikey=>$opentag) {
        $closetag = $filterignoretagsclose[$ikey];
    /// form regular expression
        $opentag  = str_replace('/','\/',$opentag); // delimit forward slashes
        $closetag = str_replace('/','\/',$closetag); // delimit forward slashes
        $pregexp = '/'.$opentag.'(.*?)'.$closetag.'/is';
        
        preg_match_all($pregexp, $text, $list_of_ignores);
        foreach (array_unique($list_of_ignores[0]) as $key=>$value) {
            $prefix = (string)(count($ignoretags) + 1);
            $ignoretags['<#'.$prefix.EXCL_SEPARATOR.$key.'#>'] = $value;
        }
        if (!empty($ignoretags)) {
            $text = str_replace($ignoretags,array_keys($ignoretags),$text);
        }
    }
}

/**
 * Extract tags (any text enclosed by < and > to avoid being processed by filters.
 * It returns the text converted with some <%xEXCL_SEPARATORx%> codes replacing the extracted text. Such extracted
 * texts are returned in the tags array (as values), with codes as keys.
 *      
 * param  text   the text that we are filtering (in/out)
 * param  tags   an array of saved strings useful to rebuild the original text (in/out)
 **/
function filter_save_tags(&$text,&$tags) {

    preg_match_all('/<([^#%*].*?)>/is',$text,$list_of_newtags);
    foreach (array_unique($list_of_newtags[0]) as $ntkey=>$value) {
        $prefix = (string)(count($tags) + 1);
        $tags['<%'.$prefix.EXCL_SEPARATOR.$ntkey.'%>'] = $value;
    }
    if (!empty($tags)) {
        $text = str_replace($tags,array_keys($tags),$text);
    }
}

/**
 * Add missing openpopup javascript to HTML files.
 */
function filter_add_javascript($text) {
    global $CFG;

    if (stripos($text, '</html>') === FALSE) {
        return $text; // this is not a html file
    }
    if (strpos($text, 'onclick="return openpopup') === FALSE) {
        return $text; // no popup - no need to add javascript
    }
    $js =" 
    <script type=\"text/javascript\">
    <!--
        function openpopup(url,name,options,fullscreen) {
          fullurl = \"".$CFG->httpswwwroot."\" + url;
          windowobj = window.open(fullurl,name,options);
          if (fullscreen) {
            windowobj.moveTo(0,0);
            windowobj.resizeTo(screen.availWidth,screen.availHeight);
          }
          windowobj.focus();
          return false;
        }
    // -->
    </script>";
    if (stripos($text, '</head>') !== FALSE) {
        //try to add it into the head element
        $text = str_ireplace('</head>', $js.'</head>', $text);
        return $text;
    }

    //last chance - try adding head element
    return preg_replace("/<html.*?>/is", "\\0<head>".$js.'</head>', $text);
}
?>
