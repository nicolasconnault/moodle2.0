<?php
/**
* this file contains:
* {@link portfolio_add_button} -entry point for callers
* {@link class portfolio_plugin_base} - class plugins extend
* {@link class portfolio_caller_base} - class callers extend
* {@link class portfolio_admin_form} - base moodleform class for plugin administration
* {@link class portfolio_user_form} - base moodleform class for plugin instance user config
* {@link class portfolio_export_form} - base moodleform class for during-export configuration (eg metadata)
* {@link class portfolio_exporter} - class used during export process
*
* and some helper functions:
* {@link portfolio_instances - returns an array of all configured instances
* {@link portfolio_instance - returns an instance of the right class given an id
* {@link portfolio_instance_select} - returns a drop menu of available instances
* {@link portfolio_static_function - requires the file, and calls a static function on the given class
" {@link portfolio_plugin_sanity_check - polls given (or all) portfolio_plugins for sanity and disables insane ones
" {@link portfolio_instance_sanity_check - polls given (or all) portfolio instances for sanity and disables insane ones
* {@link portfolio_report_instane} - returns a table of insane plugins and the reasons (used for plugins or instances thereof)
* {@link portfolio_supported_formats - returns array of all available formats for plugins and callers to use
* {@link portfolio_handle_event} - event handler for queued transfers that get triggered on cron
*
*/
require_once ($CFG->libdir.'/formslib.php');

// **** EXPORT STAGE CONSTANTS **** //

/**
* display a form to the user
* this one might not be used if neither
* the plugin, or the caller has any config.
*/
define('PORTFOLIO_STAGE_CONFIG', 1);

/**
* summarise the form and ask for confirmation
* if we skipped PORTFOLIO_STAGE_CONFIG,
* just confirm the send.
*/
define('PORTFOLIO_STAGE_CONFIRM', 2);

/**
* either queue the event and skip to PORTFOLIO_STAGE_FINISHED
* or continue to PORTFOLIO_STAGE_PACKAGE
*/

define('PORTFOLIO_STAGE_QUEUEORWAIT', 3);

/**
* package up the various bits
* during this stage both the caller
* and the plugin get their package methods called
*/
define('PORTFOLIO_STAGE_PACKAGE', 4);

/*
* the portfolio plugin must send the file
*/
define('PORTFOLIO_STAGE_SEND', 5);

/**
* cleanup the temporary area
*/
define('PORTFOLIO_STAGE_CLEANUP', 6);

/**
* display the "finished notification"
*/
define('PORTFOLIO_STAGE_FINISHED', 7);



// **** EXPORT FORMAT CONSTANTS **** //
// these should always correspond to a string
// in the portfolio module, called format_{$value}
// ****                         **** //


/**
* file - the most basic fallback format.
* this should always be supported
* in remote system.s
*/
define('PORTFOLIO_FORMAT_FILE', 'file');

/**
* moodle backup - the plugin needs to be able to write a complete backup
* the caller need to be able to export the particular XML bits to insert
* into moodle.xml (?and the file bits if necessary)
*/
define('PORTFOLIO_FORMAT_MBKP', 'mbkp');


// **** EXPORT TIME LEVELS  **** //
// these should correspond to a string
// in the portfolio module, called time_{$value}

/**
* no delay. don't even offer the user the option
* of not waiting for the transfer
*/
define('PORTFOLIO_TIME_LOW', 'low');

/**
* a small delay. user can still easily opt to
* watch this transfer and wait.
*/
define('PORTFOLIO_TIME_MODERATE', 'moderate');

/**
* slow. the user really should not be given the option
* to choose this.
*/
define('PORTFOLIO_TIME_HIGH', 'high');


/**
* entry point to add an 'add to portfolio' button somewhere in moodle
* this function does not check permissions. the caller must check permissions first.
* later, during the export process, the caller class is instantiated and the check_permissions method is called
* but not in this function.
*
* @param string $callbackclass           name of the class containing the callback functions
*                                        activity modules should ALWAYS use their name_portfolio_caller
*                                        other locations must use something unique
* @param mixed $callbackargs             this can be an array or hash of arguments to pass
*                                        back to the callback functions (passed by reference)
*                                        these MUST be primatives to be added as hidden form fields.
*                                        and the values get cleaned to PARAM_ALPHAEXT or PARAM_NUMBER or PARAM_PATH
* @param string $callbackfile            this can be autodetected if it's in the same file as your caller,
*                                        but more often, the caller is a script.php and the class in a lib.php
*                                        so you can pass it here if necessary.
*                                        this path should be relative (ie, not include) dirroot
* @param boolean $fullform               either display the fullform with the dropmenu of available instances
*                                        or just a small icon (which will trigger instance selection in a new screen)
*                                        optional, defaults to true.
* @param boolean $return                 whether to echo or return content (optional defaults to false (echo)
*/
function portfolio_add_button($callbackclass, $callbackargs, $callbackfile=null, $fullform=true, $return=false) {

    global $SESSION, $CFG, $COURSE, $USER;

    if (empty($CFG->portfolioenabled)) {
        return;
    }

    if (!$instances = portfolio_instances()) {
        return;
    }

    if (!empty($SESSION->portfoliointernal)) {
        // something somewhere has detected a risk of this being called during inside the preparation
        // eg forum_print_attachments
        return;
    }

    if (empty($callbackfile)) {
        $backtrace = debug_backtrace();
        if (!array_key_exists(0, $backtrace) || !array_key_exists('file', $backtrace[0]) || !is_readable($backtrace[0]['file'])) {
            debugging(get_string('nocallbackfile', 'portfolio'));
            return;
        }

        $callbackfile = substr($backtrace[0]['file'], strlen($CFG->dirroot));
    } else {
        if (!is_readable($CFG->dirroot . $callbackfile)) {
            debugging(get_string('nocallbackfile', 'portfolio'));
            return;
        }
    }

    require_once($CFG->dirroot . $callbackfile);

    $callersupports = call_user_func(array($callbackclass, 'supported_formats'));

    if (isset($SESSION->portfolio)) {
        return portfolio_exporter::raise_error('alreadyexporting', 'portfolio');
    }

    $output = '<form method="post" action="' . $CFG->wwwroot . '/portfolio/add.php" id="portfolio-add-button">' . "\n";
    foreach ($callbackargs as $key => $value) {
        if (!empty($value) && !is_string($value) && !is_numeric($value)) {
            $a->key = $key;
            $a->value = print_r($value, true);
            debugging(get_string('nonprimative', 'portfolio', $a));
            return;
        }
        $output .= "\n" . '<input type="hidden" name="ca_' . $key . '" value="' . $value . '" />';
    }
    $output .= "\n" . '<input type="hidden" name="callbackfile" value="' . $callbackfile . '" />';
    $output .= "\n" . '<input type="hidden" name="callbackclass" value="' . $callbackclass . '" />';
    $output .= "\n" . '<input type="hidden" name="course" value="' . (!empty($COURSE) ? $COURSE->id : 0) . '" />';
    $selectoutput = '';
    if (count($instances) == 1) {
        $instance = array_shift($instances);
        if (count(array_intersect($callersupports,  $instance->supported_formats())) == 0) {
            // bail. no common formats.
            debugging(get_string('nocommonformats', 'portfolio', $callbackclass));
            return;
        }
        if ($error = portfolio_instance_sanity_check($instance)) {
            // bail, plugin is misconfigured
            debugging(get_string('instancemisconfigured', 'portfolio', get_string($error[$instance->get('id')], 'portfolio_' . $instance->get('plugin'))));
            return;
        }
        $output .= "\n" . '<input type="hidden" name="instance" value="' . $instance->get('id') . '" />';
    }
    else {
        $selectoutput = portfolio_instance_select($instances, $callersupports, $callbackclass, 'instance', true);
    }

    if ($fullform) {
        $output .= $selectoutput;
        $output .= "\n" . '<input type="submit" value="' . get_string('addtoportfolio', 'portfolio') .'" />';
    } else {
        $output .= "\n" . '<input type="image" src="' . $CFG->pixpath . '/t/portfolio.gif" alt=' . get_string('addtoportfolio', 'portfolio') .'" />';
        //@todo replace this with a little icon
    }

    $output .= "\n" . '</form>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
    return true;
}

/**
* returns a drop menu with a list of available instances.
*
* @param array $instances     the instances to put in the menu
* @param array $callerformats the formats the caller supports
                              (this is used to filter plugins)
* @param array $callbackclass the callback class name
*
* @return string the html, from <select> to </select> inclusive.
*/
function portfolio_instance_select($instances, $callerformats, $callbackclass, $selectname='instance', $return=false, $returnarray=false) {
    global $CFG;

    if (empty($CFG->portfolioenabled)) {
        return;
    }

    $insane = portfolio_instance_sanity_check();
    $count = 0;
    $selectoutput = "\n" . '<select name="' . $selectname . '">' . "\n";
    foreach ($instances as $instance) {
        if (count(array_intersect($callerformats,  $instance->supported_formats())) == 0) {
            // bail. no common formats.
            continue;
        }
        if (array_key_exists($instance->get('id'), $insane)) {
            // bail, plugin is misconfigured
            debugging(get_string('instancemisconfigured', 'portfolio', get_string($insane[$instance->get('id')], 'portfolio_' . $instance->get('plugin'))));
            continue;
        }
        $count++;
        $selectoutput .= "\n" . '<option value="' . $instance->get('id') . '">' . $instance->get('name') . '</option>' . "\n";
        $options[$instance->get('id')] = $instance->get('name');
    }
    if (empty($count)) {
        // bail. no common formats.
        debugging(get_string('nocommonformats', 'portfolio', $callbackclass));
        return;
    }
    $selectoutput .= "\n" . "</select>\n";
    if (!empty($returnarray)) {
        return $options;
    }
    if (!empty($return)) {
        return $selectoutput;
    }
    echo $selectoutput;
}

/**
* return all portfolio instances
*
* @param boolean visibleonly don't include hidden instances (defaults to true and will be overridden to true if the next parameter is true)
* @param boolean useronly check the visibility preferences and permissions of the logged in user
* @return array of portfolio instances (full objects, not just database records)
*/
function portfolio_instances($visibleonly=true, $useronly=true) {

    global $DB, $USER;

    $values = array();
    $sql = 'SELECT * FROM {portfolio_instance}';

    if ($visibleonly || $useronly) {
        $values[] = 1;
        $sql .= ' WHERE visible = ?';
    }
    if ($useronly) {
        $sql .= ' AND id NOT IN (
                SELECT instance FROM {portfolio_instance_user}
                WHERE userid = ? AND name = ? AND value = ?
            )';
        $values = array_merge($values, array($USER->id, 'visible', 0));
    }
    $sql .= ' ORDER BY name';

    $instances = array();
    foreach ($DB->get_records_sql($sql, $values) as $instance) {
        $instances[$instance->id] = portfolio_instance($instance->id, $instance);
    }
    // @todo check capabilities here - see MDL-15768
    return $instances;
}

/**
* supported formats that portfolio plugins and callers
* can use for exporting content
*
* @return array of all the available export formats
*/
function portfolio_supported_formats() {
    return array(
        PORTFOLIO_FORMAT_FILE,
        /*PORTFOLIO_FORMAT_MBKP, */ // later
        /*PORTFOLIO_FORMAT_PIOP, */ // also later
    );
}

/**
* helper function to return an instance of a plugin (with config loaded)
*
* @param int $instance id of instance
* @param array $record database row that corresponds to this instance
*                      this is passed to avoid unnecessary lookups
*
* @return subclass of portfolio_plugin_base
*/
function portfolio_instance($instanceid, $record=null) {
    global $DB, $CFG;

    if ($record) {
        $instance  = $record;
    } else {
        if (!$instance = $DB->get_record('portfolio_instance', array('id' => $instanceid))) {
            return false; // @todo throw exception?
        }
    }
    require_once($CFG->dirroot . '/portfolio/type/'. $instance->plugin . '/lib.php');
    $classname = 'portfolio_plugin_' . $instance->plugin;
    return new $classname($instanceid, $instance);
}

/**
* helper function to call a static function on a portfolio plugin class
* this will figure out the classname and require the right file and call the function.
* you can send a variable number of arguments to this function after the first two
* and they will be passed on to the function you wish to call.
*
* @param string $plugin name of plugin
* @param string $function function to call
*/
function portfolio_static_function($plugin, $function) {
    global $CFG;

    $pname = null;
    if (is_object($plugin) || is_array($plugin)) {
        $plugin = (object)$plugin;
        $pname = $plugin->name;
    } else {
        $pname = $plugin;
    }

    $args = func_get_args();
    if (count($args) <= 2) {
        $args = array();
    }
    else {
        array_shift($args);
        array_shift($args);
    }

    require_once($CFG->dirroot . '/portfolio/type/' . $plugin .  '/lib.php');
    return call_user_func_array(array('portfolio_plugin_' . $plugin, $function), $args);
}

/**
* helper function to check all the plugins for sanity and set any insane ones to invisible.
*
* @param array $plugins to check (if null, defaults to all)
*               one string will work too for a single plugin.
*
* @return array array of insane instances (keys= id, values = reasons (keys for plugin lang)
*/
function portfolio_plugin_sanity_check($plugins=null) {
    global $DB;
    if (is_string($plugins)) {
       $plugins = array($plugins);
    } else if (empty($plugins)) {
        $plugins = get_list_of_plugins('portfolio/type');
    }

    $insane = array();
    foreach ($plugins as $plugin) {
        if ($result = portfolio_static_function($plugin, 'plugin_sanity_check')) {
            $insane[$plugin] = $result;
        }
    }
    if (empty($insane)) {
        return array();
    }
    list($where, $params) = $DB->get_in_or_equal(array_keys($insane));
    $where = ' plugin ' . $where;
    $DB->set_field_select('portfolio_instance', 'visible', 0, $where, $params);
    return $insane;
}

/**
* helper function to check all the instances for sanity and set any insane ones to invisible.
*
* @param array $instances to check (if null, defaults to all)
*              one instance or id will work too
*
* @return array array of insane instances (keys= id, values = reasons (keys for plugin lang)
*/
function portfolio_instance_sanity_check($instances=null) {
    global $DB;
    if (empty($instances)) {
        $instances = portfolio_instances(false);
    } else if (!is_array($instances)) {
        $instances = array($instances);
    }

    $insane = array();
    foreach ($instances as $instance) {
        if (is_object($instance) && !($instance instanceof portfolio_plugin_base)) {
            $instance = portfolio_instance($instance->id, $instance);
        } else if (is_numeric($instance)) {
            $instance = portfolio_instance($instance);
        }
        if (!($instance instanceof portfolio_plugin_base)) {
            debugging('something weird passed to portfolio_instance_sanity_check, not subclass or id');
            continue;
        }
        if ($result = $instance->instance_sanity_check()) {
            $insane[$instance->get('id')] = $result;
        }
    }
    if (empty($insane)) {
        return array();
    }
    list ($where, $params) = $DB->get_in_or_equal(array_keys($insane));
    $where = ' id ' . $where;
    $DB->set_field_select('portfolio_instance', 'visible', 0, $where, $params);
    return $insane;
}

/**
* helper function to display a table of plugins (or instances) and reasons for disabling
*
* @param array $insane array of insane plugins (key = plugin (or instance id), value = reason)
* @param array $instances if reporting instances rather than whole plugins, pass the array (key = id, value = object) here
*
*/
function portfolio_report_insane($insane, $instances=false, $return=false) {
    if (empty($insane)) {
        return;
    }

    static $pluginstr;
    if (empty($pluginstr)) {
        $pluginstr = get_string('plugin', 'portfolio');
    }
    if ($instances) {
        $headerstr = get_string('someinstancesdisabled', 'portfolio');
    } else {
        $headerstr = get_string('somepluginsdisabled', 'portfolio');
    }

    $output = notify($headerstr, 'notifyproblem', 'center', true);
    $table = new StdClass;
    $table->head = array($pluginstr, '');
    $table->data = array();
    foreach ($insane as $plugin => $reason) {
        if ($instances) {
            // @todo this isn't working
            // because it seems the new recordset object
            // doesn't implement the key correctly.
            // see MDL-15798
            $instance = $instances[$plugin];
            $plugin   = $instance->get('plugin');
            $name     = $instance->get('name');
        } else {
            $name = $plugin;
        }
        $table->data[] = array($name, get_string($reason, 'portfolio_' . $plugin));
    }
    $output .= print_table($table, true);
    $output .= '<br /><br /><br />';

    if ($return) {
        return $output;
    }
    echo $output;
}

/**
* temporary functions until the File API settles
* to do  with moving files around
*/
function temp_portfolio_working_directory($unique) {
    return make_upload_directory('temp/portfolio/export/' . $unique);
}

function temp_portfolio_usertemp_directory($userid) {
    return make_upload_directory('userdata/' . $userid . '/temp');
}

/**
*cleans up the working directory
*/
function temp_portfolio_cleanup($unique) {
    $workdir = temp_portfolio_working_directory($unique);
    return remove_dir($workdir);
}

/**
* fake the url to portfolio/add.php from data from somewhere else
* you should use portfolio_add_button instead 99% of the time
*
* @param int $instanceid instanceid (optional, will force a new screen if not specified)
* @param string $classname callback classname
* @param string $classfile file containing the callback class definition
* @param array $callbackargs arguments to pass to the callback class
*/
function portfolio_fake_add_url($instanceid, $classname, $classfile, $callbackargs) {
    global $CFG;
    $url = $CFG->wwwroot . '/portfolio/add.php?instance=' . $instanceid . '&amp;callbackclass=' . $classname . '&amp;callbackfile=' . $classfile;

    if (is_object($callbackargs)) {
        $callbackargs = (array)$callbackargs;
    }
    if (!is_array($callbackargs) || empty($callbackargs)) {
        return $url;
    }
    foreach ($callbackargs as $key => $value) {
        $url .= '&amp;ca_' . $key . '=' . urlencode($value);
    }
    return $url;
}

/**
* base class for the caller
* places in moodle that want to display
* the add form should subclass this for their callback.
*/
abstract class portfolio_caller_base {

    /**
    * stdclass object
    * course that was active during the caller
    */
    protected $course;

    /**
    * named array of export config
    * use{@link  set_export_config} and {@link get_export_config} to access
    */
    protected $exportconfig;

    /**
    * stdclass object
    * user currently exporting content
    */
    protected $user;

    /**
    * id if any of tempdata
    */
    private $tempdataid;

    /**
    * if this caller wants any additional config items
    * they should be defined here.
    *
    * @param array $mform moodleform object (passed by reference) to add elements to
    * @param object $instance subclass of portfolio_plugin_base
    * @param integer $userid id of user exporting content
    */
    public function export_config_form(&$mform, $instance) {}


    /**
    * whether this caller wants any additional
    * config during export (eg options or metadata)
    *
    * @return boolean
    */
    public function has_export_config() {
        return false;
    }

    /**
    * just like the moodle form validation function
    * this is passed in the data array from the form
    * and if a non empty array is returned, form processing will stop.
    *
    * @param array $data data from form.
    * @return array keyvalue pairs - form element => error string
    */
    public function export_config_validation($data) {}

    /**
    * how long does this reasonably expect to take..
    * should we offer the user the option to wait..
    * this is deliberately nonstatic so it can take filesize into account
    * the portfolio plugin can override this.
    * (so for exmaple even if a huge file is being sent,
    * the download portfolio plugin doesn't care )
    *
    * @return string (see PORTFOLIO_TIME_* constants)
    */
    public abstract function expected_time();

    /**
    * used for displaying the navigation during the export screens.
    *
    * this function must be implemented, but can really return anything.
    * an Exporting.. string will be added on the end.
    * @return array of $extranav and $cm
    *
    * to pass to build_navigation
    *
    */
    public abstract function get_navigation();

    /**
    *
    */
    public abstract function get_sha1();

    /*
    * generic getter for properties belonging to this instance
    * <b>outside</b> the subclasses
    * like name, visible etc.
    *
    * @todo  determine what to return in the error case
    */
    public function get($field) {
        if (property_exists($this, $field)) {
            return $this->{$field};
        }
        return false; // @todo throw exception?
    }

    /**
    * generic setter for properties belonging to this instance
    * <b>outside</b> the subclass
    * like name, visible, etc.
    *
    * @todo  determine what to return in the error case
    */
    public final function set($field, $value) {
        if (property_exists($this, $field)) {
            $this->{$field} = $value;
            $this->dirty = true;
            return true;
        }
        return false; // @todo throw exception?

    }

    /**
    * stores the config generated at export time.
    * subclasses can retrieve values using
    * {@link get_export_config}
    *
    * @param array $config formdata
    */
    public final function set_export_config($config) {
        $allowed = array_merge(
            array('wait', 'hidewait', 'format', 'hideformat'),
            $this->get_allowed_export_config()
        );
        foreach ($config as $key => $value) {
            if (!in_array($key, $allowed)) {
                continue; // @ todo throw exception
            }
            $this->exportconfig[$key] = $value;
        }
    }

    /**
    * returns a particular export config value.
    * subclasses shouldn't need to override this
    *
    * @param string key the config item to fetch
    * @todo figure out the error cases (item not found or not allowed)
    */
    public final function get_export_config($key) {
        $allowed = array_merge(
            array('wait', 'hidewait', 'format', 'hideformat'),
            $this->get_allowed_export_config()
        );
        if (!in_array($key, $allowed)) {
            return false; // @todo throw exception?
        }
        if (!array_key_exists($key, $this->exportconfig)) {
            return null; // @todo what to return|
        }
        return $this->exportconfig[$key];
    }


    protected function set_export_data($data) {
        global $DB;
        if ($this->tempdataid) {
            $DB->set_field('portfolio_tempdata', 'data', serialize($data), array('id' => $this->tempdataid));
        }
        $this->tempdataid = $DB->insert_record('portfolio_tempdata', (object)array('data' => serialize($data)));
    }

    protected function get_export_data() {
        global $DB;
        if ($this->tempdataid) {
            if ($data = $DB->get_field('portfolio_tempdata', 'data', array('id' => $this->tempdataid))) {
                return unserialize($data);
            }
        }
        return false;
    }

    /**
    * Similar to the other allowed_config functions
    * if you need export config, you must provide
    * a list of what the fields are.
    *
    * even if you want to store stuff during export
    * without displaying a form to the user,
    * you can use this.
    *
    * @return array array of allowed keys
    */
    public function get_allowed_export_config() {
        return array();
    }

    /**
    * after the user submits their config
    * they're given a confirm screen
    * summarising what they've chosen.
    *
    * this function should return a table of nice strings => values
    * of what they've chosen
    * to be displayed in a table.
    *
    * @return array array of config items.
    */
    public function get_export_summary() {
        return false;
    }

    /**
    * called before the portfolio plugin gets control
    * this function should copy all the files it wants to
    * the temporary directory.
    *
    * @param string $tempdir path to tempdir to put files in
    */
    public abstract function prepare_package($tempdir);

    /**
    * array of formats this caller supports
    * the intersection of what this function returns
    * and what the selected portfolio plugin supports
    * will be used
    * use the constants PORTFOLIO_FORMAT_*
    *
    * @return array list of formats
    */
    public static function supported_formats() {
        return array(PORTFOLIO_FORMAT_FILE);
    }

    /**
    * this is the "return to where you were" url
    *
    * @return string url
    */
    public abstract function get_return_url();

    /**
    * callback to do whatever capability checks required
    * in the caller (called during the export process
    */
    public abstract function check_permissions();

    /**
    * nice name to display to the user about this caller location
    */
    public abstract static function display_name();
}

abstract class portfolio_module_caller_base extends portfolio_caller_base {

    protected $cm;
    protected $course;

    public function get_navigation() {
        $extranav = array('name' => $this->cm->name, 'link' => $this->get_return_url());
        return array($extranav, $this->cm);
    }

    public function get_return_url() {
        global $CFG;
        return $CFG->wwwroot . '/mod/' . $this->cm->modname . '/view.php?id=' . $this->cm->id;
    }

    public function get($key) {
        if ($key != 'course') {
            return parent::get($key);
        }
        global $DB;
        if (empty($this->course)) {
            $this->course = $DB->get_record('course', array('id' => $this->cm->course));
        }
        return $this->course;
    }
}

/**
* the base class for portfolio plguins
* all plugins must subclass this.
*/
abstract class portfolio_plugin_base {

    /**
    * boolean
    * whether this object needs writing out to the database
    */
    protected $dirty;

    /**
    * integer
    * id of instance
    */
    protected $id;

    /**
    * string
    * name of instance
    */
    protected $name;

    /**
    * string
    * plugin this instance belongs to
    */
    protected $plugin;

    /**
    * boolean
    * whether this instance is visible or not
    */
    protected $visible;

    /**
    * named array
    * admin configured config
    * use {@link set_config} and {@get_config} to access
    */
    protected $config;

    /**
    *
    * user config cache
    * named array of named arrays
    * keyed on userid and then on config field => value
    * use {@link get_user_config} and {@link set_user_config} to access.
    */
    protected $userconfig;

    /**
    * named array
    * export config during export
    * use {@link get_export_config} and {@link set export_config} to access.
    */
    protected $exportconfig;

    /**
    * stdclass object
    * user currently exporting data
    */
    protected $user;


    /**
    * array of formats this portfolio supports
    * the intersection of what this function returns
    * and what the caller supports will be used
    * use the constants PORTFOLIO_FORMAT_*
    *
    * @return array list of formats
    */
    public static function supported_formats() {
        return array(PORTFOLIO_FORMAT_FILE);
    }


    /**
    * how long does this reasonably expect to take..
    * should we offer the user the option to wait..
    * this is deliberately nonstatic so it can take filesize into account
    *
    * @param string $callertime - what the caller thinks
    *                             the portfolio plugin instance
    *                             is given the final say
    *                             because it might be (for example) download.
    * @return string (see PORTFOLIO_TIME_* constants)
    */
    public abstract function expected_time($callertime);

    /**
    * check sanity of plugin
    * if this function returns something non empty, ALL instances of your plugin
    * will be set to invisble and not be able to be set back until it's fixed
    *
    * @return mixed - string = error string KEY (must be inside plugin_$yourplugin) or 0/false if you're ok
    */
    public static function plugin_sanity_check() {
        return 0;
    }

    /**
    * check sanity of instances
    * if this function returns something non empty, the instance will be
    * set to invislbe and not be able to be set back until it's fixed.
    *
    * @return mixed - string = error string KEY (must be inside plugin_$yourplugin) or 0/false if you're ok
    */
    public function instance_sanity_check() {
        return 0;
    }

    /**
    * does this plugin need any configuration by the administrator?
    *
    * if you override this to return true,
    * you <b>must</b> implement {@link} admin_config_form
    */
    public static function has_admin_config() {
        return false;
    }

    /**
    * can this plugin be configured by the user in their profile?
    *
    * if you override this to return true,
    * you <b>must</b> implement {@link user_config_form
    */
    public function has_user_config() {
        return false;
    }

    /**
    * does this plugin need configuration during export time?
    *
    * if you override this to return true,
    * you <b>must</b> implement {@link export_config_form}
    */
    public function has_export_config() {
        return false;
    }

    /**
    * just like the moodle form validation function
    * this is passed in the data array from the form
    * and if a non empty array is returned, form processing will stop.
    *
    * @param array $data data from form.
    * @return array keyvalue pairs - form element => error string
    */
    public function export_config_validation() {}

    /**
    * just like the moodle form validation function
    * this is passed in the data array from the form
    * and if a non empty array is returned, form processing will stop.
    *
    * @param array $data data from form.
    * @return array keyvalue pairs - form element => error string
    */
    public function user_config_validation() {}

    /**
    * sets the export time config from the moodle form.
    * you can also use this to set export config that
    * isn't actually controlled by the user
    * eg things that your subclasses want to keep in state
    * across the export.
    * keys must be in {@link get_allowed_export_config}
    *
    * this is deliberately not final (see boxnet plugin)
    *
    * @param array $config named array of config items to set.
    */
    public function set_export_config($config) {
        $allowed = array_merge(
            array('wait', 'hidewait', 'format', 'hideformat'),
            $this->get_allowed_export_config()
        );
        foreach ($config as $key => $value) {
            if (!in_array($key, $allowed)) {
                continue; // @ todo throw exception
            }
            $this->exportconfig[$key] = $value;
        }
    }

    /**
    * gets an export time config value.
    * subclasses should not override this.
    *
    * @param string key field to fetch
    *
    * @return string config value
    *
    * @todo figure out the error cases
    */
    public final function get_export_config($key) {
        $allowed = array_merge(
            array('hidewait', 'wait', 'format', 'hideformat'),
            $this->get_allowed_export_config()
        );
        if (!in_array($key, $allowed)) {
            return false; // @todo throw exception?
        }
        if (!array_key_exists($key, $this->exportconfig)) {
            return null; // @todo what to return|
        }
        return $this->exportconfig[$key];
    }

    /**
    * after the user submits their config
    * they're given a confirm screen
    * summarising what they've chosen.
    *
    * this function should return a table of nice strings => values
    * of what they've chosen
    * to be displayed in a table.
    *
    * @return array array of config items.
    */
    public function get_export_summary() {
        return false;
    }

    /**
    * called before the portfolio plugin gets control
    * this function should copy all the files it wants to
    * the temporary directory.
    *
    * @param string $tempdir path to temporary directory
    */
    public abstract function prepare_package($tempdir);

    /**
    * this is the function that is responsible for sending
    * the package to the remote system,
    * or whatever request is necessary to initiate the transfer.
    *
    * @return boolean success
    */
    public abstract function send_package();


    /**
    * once everything is done and the user
    * has the finish page displayed to them
    * the base class takes care of printing them
    * "return to where you are" or "continue to portfolio" links
    * this function allows for exta finish options from the plugin
    *
    * @return array named array of links => titles
    */
    public function get_extra_finish_options() {
        return false;
    }

    /**
    * the url for the user to continue to their portfolio
    *
    * @return string url or false.
    */
    public abstract function get_continue_url();

    /**
    * mform to display to the user in their profile
    * if your plugin can't be configured by the user,
    * (see {@link has_user_config})
    * don't bother overriding this function
    *
    * @param moodleform $mform passed by reference, add elements to it
    */
    public function user_config_form(&$mform) {}

    /**
    * mform to display to the admin configuring the plugin.
    * if your plugin can't be configured by the admin,
    * (see {@link} has_admin_config)
    * don't bother overriding this function
    *
    * this function can be called statically or non statically,
    * depending on whether it's creating a new instance (statically),
    * or editing an existing one (non statically)
    *
    * @param moodleform $mform passed by reference, add elements to it.
    * @return mixed - if a string is returned, it means the plugin cannot create an instance
    *                 and the string is an error code
    */
    public function admin_config_form(&$mform) {}

    /**
    * just like the moodle form validation function
    * this is passed in the data array from the form
    * and if a non empty array is returned, form processing will stop.
    *
    * @param array $data data from form.
    * @return array keyvalue pairs - form element => error string
    */
    public static function admin_config_validation($data) {}
    /**
    * mform to display to the user exporting data using this plugin.
    * if your plugin doesn't need user input at this time,
    * (see {@link has_export_config}
    * don't bother overrideing this function
    *
    * @param moodleform $mform passed by reference, add elements to it.
    */
    public function export_config_form(&$mform) {}

    /**
    * override this if your plugin doesn't allow multiple instances
    *
    * @return boolean
    */
    public static function allows_multiple() {
        return true;
    }

    /**
    *
    * If at any point the caller wants to steal control
    * it can, by returning something that isn't false
    * in this function
    * The controller will redirect to whatever url
    * this function returns.
    * Afterwards, you can redirect back to portfolio/add.php?postcontrol=1
    * and {@link post_control} is called before the rest of the processing
    * for the stage is done
    *
    * @param int stage to steal control *before* (see constants PARAM_STAGE_*}
    *
    * @return boolean or string url
    */
    public function steal_control($stage) {
        return false;
    }

    /**
    * after a plugin has elected to steal control,
    * and control returns to portfolio/add.php|postcontrol=1,
    * this function is called, and passed the stage that was stolen control from
    * and the request (get and post but not cookie) parameters
    * this is useful for external systems that need to redirect the user back
    * with some extra data in the url (like auth tokens etc)
    * for an example implementation, see boxnet portfolio plugin.
    *
    * @param int $stage the stage before control was stolen
    * @param array $params a merge of $_GET and $_POST
    *
    */

    public function post_control($stage, $params) { }

    /**
    * this function creates a new instance of a plugin
    * saves it in the database, saves the config
    * and returns it.
    * you shouldn't need to override it
    * unless you're doing something really funky
    *
    * @return object subclass of portfolio_plugin_base
    */
    public static function create_instance($plugin, $name, $config) {
        global $DB, $CFG;
        $new = (object)array(
            'plugin' => $plugin,
            'name'   => $name,
        );
        $newid = $DB->insert_record('portfolio_instance', $new);
        require_once($CFG->dirroot . '/portfolio/type/' . $plugin . '/lib.php');
        $classname = 'portfolio_plugin_'  . $plugin;
        $obj = new $classname($newid);
        $obj->set_config($config);
        return $obj;
    }

    /**
    * construct a plugin instance
    * subclasses should not need  to override this unless they're doing something special
    * and should call parent::__construct afterwards
    *
    * @param int $instanceid id of plugin instance to construct
    * @param mixed $record stdclass object or named array - use this is you already have the record to avoid another query
    *
    * @return object subclass of portfolio_plugin_base
    */
    public function __construct($instanceid, $record=null) {
        global $DB;
        if (!$record) {
            if (!$record = $DB->get_record('portfolio_instance', array('id' => $instanceid))) {
                return false; // @todo throw exception?
            }
        }
        foreach ((array)$record as $key =>$value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        $this->config = new StdClass;
        $this->userconfig = array();
        $this->exportconfig = array();
        foreach ($DB->get_records('portfolio_instance_config', array('instance' => $instanceid)) as $config) {
            $this->config->{$config->name} = $config->value;
        }
        return $this;
    }

    /**
    * a list of fields that can be configured per instance.
    * this is used for the save handlers of the config form
    * and as checks in set_config and get_config
    *
    * @return array array of strings (config item names)
    */
    public static function get_allowed_config() {
        return array();
    }

    /**
    * a list of fields that can be configured by the user.
    * this is used for the save handlers in the config form
    * and as checks in set_user_config and get_user_config.
    *
    * @return array array of strings (config field names)
    */
    public function get_allowed_user_config() {
        return array();
    }

    /**
    * a list of fields that can be configured by the user.
    * this is used for the save handlers in the config form
    * and as checks in set_export_config and get_export_config.
    *
    * @return array array of strings (config field names)
    */
    public function get_allowed_export_config() {
        return array();
    }

    /**
    * saves (or updates) the config stored in portfolio_instance_config.
    * you shouldn't need to override this unless you're doing something funky.
    *
    * @param array $config array of config items.
    */
    public final function set_config($config) {
        global $DB;
        foreach ($config as $key => $value) {
            // try set it in $this first
            if ($this->set($key, $value)) {
                continue;
            }
            if (!in_array($key, $this->get_allowed_config())) {
                continue; // @todo throw exception?
            }
            if (!isset($this->config->{$key})) {
                $DB->insert_record('portfolio_instance_config', (object)array(
                    'instance' => $this->id,
                    'name' => $key,
                    'value' => $value,
                ));
            } else if ($this->config->{$key} != $value) {
                $DB->set_field('portfolio_instance_config', 'value', $value, array('name' => $key, 'instance' => $this->id));
            }
            $this->config->{$key} = $value;
        }
        return true; // @todo - if we're going to change here to throw exceptions, this can change
    }

    /**
    * gets the value of a particular config item
    *
    * @param string $key key to fetch
    *
    * @return string the corresponding value
    *
    * @todo determine what to return in the error case.
    */
    public final function get_config($key) {
        if (!in_array($key, $this->get_allowed_config())) {
            return false; // @todo throw exception?
        }
        if (isset($this->config->{$key})) {
            return $this->config->{$key};
        }
        return false; // @todo null?
    }

    /**
    * get the value of a config item for a particular user
    *
    * @param string $key key to fetch
    * @param integer $userid id of user (defaults to current)
    *
    * @return string the corresponding value
    *
    * @todo determine what to return in the error case
    */
    public final function get_user_config($key, $userid=0) {
        global $DB;

        if (empty($userid)) {
            $userid = $this->user->id;
        }

        if ($key != 'visible') { // handled by the parent class
            if (!in_array($key, $this->get_allowed_user_config())) {
                return false; // @todo throw exception?
            }
        }
        if (!array_key_exists($userid, $this->userconfig)) {
            $this->userconfig[$userid] = (object)array_fill_keys(array_merge(array('visible'), $this->get_allowed_user_config()), null);
            foreach ($DB->get_records('portfolio_instance_user', array('instance' => $this->id, 'userid' => $userid)) as $config) {
                $this->userconfig[$userid]->{$config->name} = $config->value;
            }
        }
        if ($this->userconfig[$userid]->visible === null) {
            $this->set_user_config(array('visible' => 1), $userid);
        }
        return $this->userconfig[$userid]->{$key};

    }

    /**
    *
    * sets config options for a given user
    *
    * @param mixed $config array or stdclass containing key/value pairs to set
    * @param integer $userid userid to set config for (defaults to current)
    *
    * @todo determine what to return in the error case
    */
    public final function set_user_config($config, $userid=0) {
        global $DB;

        if (empty($userid)) {
            $userid = $this->user->id;
        }

        foreach ($config as $key => $value) {
            if ($key != 'visible' && !in_array($key, $this->get_allowed_user_config())) {
                continue; // @todo throw exception?
            }
            if (!$existing = $DB->get_record('portfolio_instance_user', array('instance'=> $this->id, 'userid' => $userid, 'name' => $key))) {
                $DB->insert_record('portfolio_instance_user', (object)array(
                    'instance' => $this->id,
                    'name' => $key,
                    'value' => $value,
                    'userid' => $userid,
                ));
            } else if ($existing->value != $value) {
                $DB->set_field('portfolio_instance_user', 'value', $value, array('name' => $key, 'instance' => $this->id, 'userid' => $userid));
            }
            $this->userconfig[$userid]->{$key} = $value;
        }
        return true; // @todo

    }

    /**
    * generic getter for properties belonging to this instance
    * <b>outside</b> the subclasses
    * like name, visible etc.
    *
    * @todo  determine what to return in the error case
    */
    public final function get($field) {
        if (property_exists($this, $field)) {
            return $this->{$field};
        }
        return false; // @todo throw exception?
    }

    /**
    * generic setter for properties belonging to this instance
    * <b>outside</b> the subclass
    * like name, visible, etc.
    *
    * @todo  determine what to return in the error case
    */
    public final function set($field, $value) {
        if (property_exists($this, $field)) {
            $this->{$field} = $value;
            $this->dirty = true;
            return true;
        }
        return false; // @todo throw exception?

    }

    /**
    * saves stuff that's been stored in the object to the database
    * you shouldn't need to override this
    * unless you're doing something really funky.
    * and if so, call parent::save when you're done.
    */
    public function save() {
        global $DB;
        if (!$this->dirty) {
            return true;
        }
        $fordb = new StdClass();
        foreach (array('id', 'name', 'plugin', 'visible') as $field) {
            $fordb->{$field} = $this->{$field};
        }
        $DB->update_record('portfolio_instance', $fordb);
        $this->dirty = false;
        return true;
    }

    /**
    * deletes everything from the database about this plugin instance.
    * you shouldn't need to override this unless you're storing stuff
    * in your own tables.  and if so, call parent::delete when you're done.
    */
    public function delete() {
        global $DB;
        $DB->delete_records('portfolio_instance_config', array('instance' => $this->get('id')));
        $DB->delete_records('portfolio_instance_user', array('instance' => $this->get('id')));
        $DB->delete_records('portfolio_instance', array('id' => $this->get('id')));
        $this->dirty = false;
        return true;
    }
}

/**
* this is the form that is actually used while exporting.
* plugins and callers don't get to define their own class
* as we have to handle form elements from both places
* see the docs for portfolio_plugin_base and portfolio_caller for more information
*/
final class portfolio_export_form extends moodleform {

    public function definition() {

        $mform =& $this->_form;
        $mform->addElement('hidden', 'stage', PORTFOLIO_STAGE_CONFIG);
        $mform->addElement('hidden', 'instance', $this->_customdata['instance']->get('id'));

        if (array_key_exists('formats', $this->_customdata) && is_array($this->_customdata['formats'])) {
            if (count($this->_customdata['formats']) > 1) {
                $options = array();
                foreach ($this->_customdata['formats'] as $key) {
                    $options[$key] = get_string('format_' . $key, 'portfolio');
                }
                $mform->addElement('select', 'format', get_string('availableformats', 'portfolio'), $options);
            } else {
                $f = array_shift($this->_customdata['formats']);
                $mform->addElement('hidden', 'format', $f);
            }
        }

        if (array_key_exists('expectedtime', $this->_customdata) && $this->_customdata['expectedtime'] != PORTFOLIO_TIME_LOW) {
            //$mform->addElement('select', 'wait', get_string('waitlevel_' . $this->_customdata['expectedtime'], 'portfolio'), $options);


            $radioarray = array();
            $radioarray[] = &MoodleQuickForm::createElement('radio', 'wait', '', get_string('wait', 'portfolio'), 1);
            $radioarray[] = &MoodleQuickForm::createElement('radio', 'wait', '', get_string('dontwait', 'portfolio'),  0);
            $mform->addGroup($radioarray, 'radioar', get_string('wanttowait_' . $this->_customdata['expectedtime'], 'portfolio') , array(' '), false);

            $mform->setDefault('wait', 0);
        }
        else {
            $mform->addElement('hidden', 'wait', 1);
        }

        if (array_key_exists('plugin', $this->_customdata) && is_object($this->_customdata['plugin'])) {
            $this->_customdata['plugin']->export_config_form($mform, $this->_customdata['userid']);
        }

        if (array_key_exists('caller', $this->_customdata) && is_object($this->_customdata['caller'])) {
            $this->_customdata['caller']->export_config_form($mform, $this->_customdata['instance'], $this->_customdata['userid']);
        }

        $this->add_action_buttons(true, get_string('next'));
    }

    public function validation($data) {

        $errors = array();

        if (array_key_exists('plugin', $this->_customdata) && is_object($this->_customdata['plugin'])) {
            $pluginerrors = $this->_customdata['plugin']->export_config_validation($data);
            if (is_array($pluginerrors)) {
                $errors = $pluginerrors;
            }
        }
        if (array_key_exists('caller', $this->_customdata) && is_object($this->_customdata['caller'])) {
            $callererrors = $this->_customdata['caller']->export_config_validation($data);
            if (is_array($callererrors)) {
                $errors = array_merge($errors, $callererrors);
            }
        }
        return $errors;
    }
}

/**
* this form is extendable by plugins
* who want the admin to be able to configure
* more than just the name of the instance.
* this is NOT done by subclassing this class,
* see the docs for portfolio_plugin_base for more information
*/
final class portfolio_admin_form extends moodleform {

    protected $instance;
    protected $plugin;

    public function definition() {
        global $CFG;
        $this->plugin = $this->_customdata['plugin'];
        $this->instance = (isset($this->_customdata['instance'])
                && is_subclass_of($this->_customdata['instance'], 'portfolio_plugin_base'))
            ? $this->_customdata['instance'] : null;

        $mform =& $this->_form;
        $strrequired = get_string('required');

        $mform->addElement('hidden', 'edit',  ($this->instance) ? $this->instance->get('id') : 0);
        $mform->addElement('hidden', 'new',   $this->plugin);
        $mform->addElement('hidden', 'plugin', $this->plugin);

        $mform->addElement('text', 'name', get_string('name'), 'maxlength="100" size="30"');
        $mform->addRule('name', $strrequired, 'required', null, 'client');

        // let the plugin add the fields they want (either statically or not)
        if (portfolio_static_function($this->plugin, 'has_admin_config')) {
            if (!$this->instance) {
                $result = portfolio_static_function($this->plugin, 'admin_config_form', $mform);
            } else {
                $result = $this->instance->admin_config_form($mform);
            }
        }

        if (isset($result) && is_string($result)) { // something went wrong, stop
            return $this->raise_error($result, 'portfolio_' . $this->plugin, $CFG->wwwroot . '/' . $CFG->admin . '/portfolio.php');
        }

        // and set the data if we have some.
        if ($this->instance) {
            $data = array('name' => $this->instance->get('name'));
            foreach ($this->instance->get_allowed_config() as $config) {
                $data[$config] = $this->instance->get_config($config);
            }
            $this->set_data($data);
        }
        $this->add_action_buttons(true, get_string('save', 'portfolio'));
    }

    public function validation($data) {
        global $DB;

        $errors = array();
        if ($DB->count_records('portfolio_instance', array('name' => $data['name'], 'plugin' => $data['plugin'])) > 1) {
            $errors = array('name' => get_string('err_uniquename', 'portfolio'));
        }

        $pluginerrors = array();
        if ($this->instance) {
            $pluginerrors = $this->instance->admin_config_validation($data);
        }
        else {
            $pluginerrors = portfolio_static_function($this->plugin, 'admin_config_validation', $data);
        }
        if (is_array($pluginerrors)) {
            $errors = array_merge($errors, $pluginerrors);
        }
        return $errors;
    }
}

/**
* this is the form for letting the user configure an instance of a plugin.
* in order to extend this, you don't subclass this in the plugin..
* see the docs in portfolio_plugin_base for more information
*/
final class portfolio_user_form extends moodleform {

    protected $instance;
    protected $userid;

    public function definition() {
        $this->instance = $this->_customdata['instance'];
        $this->userid = $this->_customdata['userid'];

        $this->_form->addElement('hidden', 'config', $this->instance->get('id'));

        $this->instance->user_config_form($this->_form, $this->userid);

        $data = array();
        foreach ($this->instance->get_allowed_user_config() as $config) {
            $data[$config] = $this->instance->get_user_config($config, $this->userid);
        }
        $this->set_data($data);
        $this->add_action_buttons(true, get_string('save', 'portfolio'));
    }

    public function validation($data) {

        $errors = $this->instance->user_config_validation($data);

    }
}

/**
*
* Class that handles the various stages of the actual export
*/
final class portfolio_exporter {

    private $currentstage;
    private $caller;
    private $instance;
    private $noconfig;
    private $navigation;
    private $uniquekey;
    private $tempdir;
    private $user;

    public $instancefile;
    public $callerfile;

    /**
    * construct a new exporter for use
    *
    * @param portfolio_plugin_base subclass $instance portfolio instance (passed by reference)
    * @param portfolio_caller_base subclass $caller portfolio caller (passed by reference)
    * @param string $navigation result of build_navigation (passed to print_header)
    */
    public function __construct(&$instance, &$caller, $callerfile, $navigation) {
        $this->instance =& $instance;
        $this->caller =& $caller;
        if ($instance) {
            $this->instancefile = 'portfolio/type/' . $instance->get('plugin') . '/lib.php';
        }
        $this->callerfile = $callerfile;
        $this->stage = PORTFOLIO_STAGE_CONFIG;
        $this->navigation = $navigation;
    }

    /*
    * generic getter for properties belonging to this instance
    * <b>outside</b> the subclasses
    * like name, visible etc.
    *
    * @todo  determine what to return in the error case
    */
    public function get($field) {
        if (property_exists($this, $field)) {
            return $this->{$field};
        }
        return false; // @todo throw exception?
    }

    /**
    * generic setter for properties belonging to this instance
    * <b>outside</b> the subclass
    * like name, visible, etc.
    *
    * @todo  determine what to return in the error case
    */

    public function set($field, $value) {
        if (property_exists($this, $field)) {
            $this->{$field} = $value;
            if ($field == 'instance') {
                $this->instancefile = 'portfolio/type/' . $this->instance->get('plugin') . '/lib.php';
            }
            $this->dirty = true;
            return true;
        }
        return false; // @todo throw exception?

    }
    /**
    * process the given stage calling whatever functions are necessary
    *
    * @param int $stage (see PORTFOLIO_STAGE_* constants)
    * @param boolean $alreadystolen used to avoid letting plugins steal control twice.
    *
    * @return boolean whether or not to process the next stage. this is important as the function is called recursively.
    */
    public function process_stage($stage, $alreadystolen=false) {
        global $SESSION;
        if (!$alreadystolen && $url = $this->instance->steal_control($stage)) {
            $SESSION->portfolio->stagepresteal = $stage;
            redirect($url);
            break;
        }

        $waiting = $this->instance->get_export_config('wait');
        if ($stage > PORTFOLIO_STAGE_QUEUEORWAIT && empty($waiting)) {
            $stage = PORTFOLIO_STAGE_FINISHED;
        }
        $functionmap = array(
            PORTFOLIO_STAGE_CONFIG        => 'config',
            PORTFOLIO_STAGE_CONFIRM       => 'confirm',
            PORTFOLIO_STAGE_QUEUEORWAIT   => 'queueorwait',
            PORTFOLIO_STAGE_PACKAGE       => 'package',
            PORTFOLIO_STAGE_CLEANUP       => 'cleanup',
            PORTFOLIO_STAGE_SEND          => 'send',
            PORTFOLIO_STAGE_FINISHED      => 'finished'
        );

        $function = 'process_stage_' . $functionmap[$stage];
        if ($this->$function()) {
            // if we get through here it means control was returned
            // as opposed to wanting to stop processing
            // eg to wait for user input.
            $stage++;
            return $this->process_stage($stage);
        }
        return false;
    }

    /**
    * helper function to return the portfolio instance
    *
    * @return  portfolio_plugin_base subclass
    */
    public function instance() {
        return $this->instance;
    }

    /**
    * helper function to return the caller object
    *
    * @return portfolio_caller_base subclass
    */
    public function caller() {
        return $this->caller;
    }

    /**
    * processes the 'config' stage of the export
    *
    * @return boolean whether or not to process the next stage. this is important as the control function is called recursively.
    */
    public function process_stage_config() {

        global $SESSION;

        $pluginobj = $callerobj = null;
        if ($this->instance->has_export_config()) {
            $pluginobj = $this->instance;
        }
        if ($this->caller->has_export_config()) {
            $callerobj = $this->caller;
        }
        $formats = array_intersect($this->instance->supported_formats(), $this->caller->supported_formats());
        $allsupported = portfolio_supported_formats();
        foreach ($formats as $key => $format) {
            if (!in_array($format, $allsupported)) {
                debugging(get_string('invalidformat', 'portfolio', $format));
                unset($formats[$key]);
            }
        }
        $expectedtime = $this->instance->expected_time($this->caller->expected_time());
        if (count($formats) == 0) {
            // something went wrong, we should not have gotten this far.
            return $this->raise_error('nocommonformats', 'portfolio', get_class($caller));
        }
        // even if neither plugin or caller wants any config, we have to let the user choose their format, and decide to wait.
        if ($pluginobj || $callerobj || count($formats) > 1 || $expectedtime != PORTFOLIO_TIME_LOW) {
            $customdata = array(
                'instance' => $this->instance,
                'plugin' => $pluginobj,
                'caller' => $callerobj,
                'userid' => $this->user->id,
                'formats' => $formats,
                'expectedtime' => $expectedtime,
            );
            $mform = new portfolio_export_form('', $customdata);
            if ($mform->is_cancelled()){
                unset($SESSION->portfolio);
                redirect($this->caller->get_return_url());
                exit;
            } else if ($fromform = $mform->get_data()){
                if (!confirm_sesskey()) {
                    return $this->raise_error('confirmsesskeybad', '', $caller->get_return_url());
                }
                $pluginbits = array();
                $callerbits = array();
                foreach ($fromform as $key => $value) {
                    if (strpos($key, 'plugin_') === 0) {
                        $pluginbits[substr($key, 7)]  = $value;
                    } else if (strpos($key, 'caller_') === 0) {
                        $callerbits[substr($key, 7)] = $value;
                    }
                }
                $callerbits['format'] = $pluginbits['format'] = $fromform->format;
                $pluginbits['wait'] = $fromform->wait;
                if ($expectedtime == PORTFOLIO_TIME_LOW) {
                    $pluginbits['wait'] = 1;
                    $pluginbits['hidewait'] = 1;
                }
                $callerbits['hideformat'] = $pluginbits['hideformat'] = (count($formats) == 1);
                $this->caller->set_export_config($callerbits);
                $this->instance->set_export_config($pluginbits);
                return true;
            } else {
                $this->print_header();
                print_heading(get_string('configexport' ,'portfolio'));
                print_simple_box_start();
                $mform->display();
                print_simple_box_end();
                print_footer();
                return false;;
            }
        } else {
            $this->noexportconfig = true;
            $format = array_shift($formats);
            $this->instance->set_export_config(array('hidewait' => 1, 'wait' => 1, 'format' => $format, 'hideformat' => 1));
            $this->caller->set_export_config(array('format' => $format, 'hideformat' => 1));
            return true;
            // do not break - fall through to confirm
        }
    }

    /**
    * processes the 'confirm' stage of the export
    *
    * @return boolean whether or not to process the next stage. this is important as the control function is called recursively.
    */
    public function process_stage_confirm() {
        global $CFG, $DB;

        $previous = $DB->get_records(
            'portfolio_log',
            array(
                'userid'      => $this->user->id,
                'portfolio'   => $this->instance->get('id'),
                'caller_sha1' => $this->caller->get_sha1(),
            )
        );
        if (isset($this->noexportconfig) && empty($previous)) {
            return true;
        }
        $strconfirm = get_string('confirmexport', 'portfolio');
        $yesurl = $CFG->wwwroot . '/portfolio/add.php?stage=' . PORTFOLIO_STAGE_QUEUEORWAIT;
        $nourl  = $CFG->wwwroot . '/portfolio/add.php?cancel=1';
        $this->print_header();
        print_heading($strconfirm);
        print_simple_box_start();
        print_heading(get_string('confirmsummary', 'portfolio'), '', 4);
        $mainsummary = array();
        if (!$this->instance->get_export_config('hideformat')) {
            $mainsummary[get_string('selectedformat', 'portfolio')] = get_string('format_' . $this->instance->get_export_config('format'), 'portfolio');
        }
        if (!$this->instance->get_export_config('hidewait')) {
            $mainsummary[get_string('selectedwait', 'portfolio')] = get_string(($this->instance->get_export_config('wait') ? 'yes' : 'no'));
        }
        if ($previous) {
            $previousstr = '';
            foreach ($previous as $row) {
                $previousstr .= userdate($row->time);
                if ($row->caller_class != get_class($this->caller)) {
                    require_once($CFG->dirroot . '/' . $row->caller_file);
                    $previousstr .= ' (' . call_user_func(array($row->caller_class, 'display_name')) . ')';
                }
                $previousstr .= '<br />';
            }
            $mainsummary[get_string('exportedpreviously', 'portfolio')] = $previousstr;
        }
        if (!$csummary = $this->caller->get_export_summary()) {
            $csummary = array();
        }
        if (!$isummary = $this->instance->get_export_summary()) {
            $isummary = array();
        }
        $mainsummary = array_merge($mainsummary, $csummary, $isummary);
        $table = new StdClass;
        $table->data = array();
        foreach ($mainsummary as $string => $value) {
            $table->data[] = array($string, $value);
        }
        print_table($table);
        notice_yesno($strconfirm, $yesurl, $nourl);
        print_simple_box_end();
        print_footer();
        return false;
    }

    /**
    * processes the 'queueornext' stage of the export
    *
    * @return boolean whether or not to process the next stage. this is important as the control function is called recursively.
    */
    public function process_stage_queueorwait() {
        global $SESSION;
        $wait = $this->instance->get_export_config('wait');
        if (empty($wait)) {
            events_trigger('portfolio_send', $this);
            unset($SESSION->portfolio);
            return $this->process_stage_finished(true);
        }
        return true;
    }

    /**
    * processes the 'package' stage of the export
    *
    * @return boolean whether or not to process the next stage. this is important as the control function is called recursively.
    */
    public function process_stage_package() {
        // now we've agreed on a format,
        // the caller is given control to package it up however it wants
        // and then the portfolio plugin is given control to do whatever it wants.
        $unique = $this->user->id . '-' . time();
        $tempdir = temp_portfolio_working_directory($unique);
        $this->uniquekey = $unique;
        $this->tempdir = $tempdir;
        if (!$this->caller->prepare_package($tempdir)) {
            return $this->raise_error('callercouldnotpackage', 'portfolio', $this->caller->get_return_url());
        }
        if (!$package = $this->instance->prepare_package($tempdir)) {
            return $this->raise_error('plugincouldnotpackage', 'portfolio', $this->caller->get_return_url());
        }
        return true;
    }

    /**
    * processes the 'cleanup' stage of the export
    *
    * @return boolean whether or not to process the next stage. this is important as the control function is called recursively.
    */
    public function process_stage_cleanup() {
        global $CFG, $DB;
        // @todo this is unpleasant. fix it.
        require_once($CFG->dirroot . '/backup/lib.php');
        delete_dir_contents($this->tempdir);
        // @todo maybe add a hook in the plugin(s)
        if ($this->caller->get('tempdataid')) {
            $DB->delete_records('portfolio_tempdata', array('id' => $this->caller->get('tempdataid')));
        }

        return true;
    }

    /**
    * processes the 'send' stage of the export
    *
    * @return boolean whether or not to process the next stage. this is important as the control function is called recursively.
    */
    public function process_stage_send() {
        // send the file
        if (!$this->instance->send_package()) {
            return $this->raise_error('failedtosendpackage', 'portfolio');
        }
        // log the transfer
        global $DB;
        $l = array(
            'userid'         => $this->user->id,
            'portfolio'      => $this->instance->get('id'),
            'caller_file'    => $this->callerfile,
            'caller_sha1'    => $this->caller->get_sha1(),
            'caller_class'   => get_class($this->caller),
            'time'           => time(),
        );
        $DB->insert_record('portfolio_log', $l);
        return true;
    }

    /**
    * processes the 'finish' stage of the export
    *
    * @return boolean whether or not to process the next stage. this is important as the control function is called recursively.
    */
    public function process_stage_finished($queued=false) {
        global $SESSION;
        $returnurl = $this->caller->get_return_url();
        $continueurl = $this->instance->get_continue_url();
        $extras = $this->instance->get_extra_finish_options();

        $this->print_header();
        if ($queued) {
            print_heading(get_string('exportqueued', 'portfolio'));
        } else {
            print_heading(get_string('exportcomplete', 'portfolio'));
        }
        if ($returnurl) {
            echo '<a href="' . $returnurl . '">' . get_string('returntowhereyouwere', 'portfolio') . '</a><br />';
        }
        if ($continueurl) {
            echo '<a href="' . $continueurl . '">' . get_string('continuetoportfolio', 'portfolio') . '</a><br />';
        }
        if (is_array($extras)) {
            foreach ($extras as $link => $string) {
                echo '<a href="' . $link . '">' . $string . '</a><br />';
            }
        }
        print_footer();
        unset($SESSION->portfolio);
        return false;
    }


    /**
    * local print header function to be reused across the export
    *
    * @param string $titlestring key for a portfolio language string
    * @param string $headerstring key for a portfolio language string
    */
    public function print_header($titlestr='exporting', $headerstr='exporting') {
        $titlestr = get_string($titlestr, 'portfolio');
        $headerstr = get_string($headerstr, 'portfolio');

        print_header($titlestr, $headerstr, $this->navigation);
    }

    /**
    * error handler - decides whether we're running interactively or not
    * and behaves accordingly
    */
    public static function raise_error($string, $module='moodle', $continue=null) {
        if (defined('FULLME') && FULLME == 'cron') {
            debugging(get_string($string, $module));
            return false;
        }
        global $SESSION;
        unset($SESSION->portfolio);
        print_error($string, $module, $continue);
    }
}

class portfolio_instance_select extends moodleform {

    private $caller;

    function definition() {
        $this->caller = $this->_customdata['caller'];
        $options = portfolio_instance_select(
            portfolio_instances(),
            $this->caller->supported_formats(),
            get_class($this->caller),
            'instance',
            true,
            true
        );
        if (empty($options)) {
            portfolio_exporter::raise_error('noavailableplugins', 'portfolio');
        }
        $mform =& $this->_form;
        $mform->addElement('select', 'instance', get_string('selectplugin', 'portfolio'), $options);
        $this->add_action_buttons(true, get_string('next'));
    }
}

/**
* event handler for the portfolio_send event
*/
function portfolio_handle_event($eventdata) {
    global $CFG;
    require_once($CFG->dirroot . '/' . $eventdata->instancefile);
    require_once($CFG->dirroot . '/' . $eventdata->callerfile);
    $exporter = unserialize(serialize($eventdata));
    $exporter->process_stage_package();
    $exporter->process_stage_send();
    $exporter->process_stage_cleanup();
    return true;
}

?>