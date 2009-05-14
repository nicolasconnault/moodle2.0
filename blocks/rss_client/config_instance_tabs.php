<?php  // $Id: config_instance_tabs.php,v 1.15 2009/05/07 08:55:11 tjhunt Exp $
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $inactive, $activetab and $currentaction have been set

global $USER;
$tabs = $row = array();

    // TODO - temporary hack to get the block context only if it already exists.
    global $DB;
    if ($DB->record_exists('context', array('contextlevel' => CONTEXT_BLOCK, 'instanceid' => $this->instance->id))) {
        $context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
    } else {
        $context = get_context_instance(CONTEXT_SYSTEM); // pinned blocks do not have own context
    }

if (has_capability('moodle/site:manageblocks', $context)) {
    $script = $page->url->out(array('instanceid' => $this->instance->id, 'sesskey' => sesskey(), 'blockaction' => 'config', 'currentaction' => 'configblock', 'id' => $id, 'section' => 'rss'));
    $row[] = new tabobject('configblock', $script,
                get_string('configblock', 'block_rss_client'));
}

$script = $page->url->out(array('instanceid' => $this->instance->id, 'sesskey' => sesskey(), 'blockaction' => 'config', 'currentaction' => 'managefeeds', 'id' => $id, 'section' => 'rss'));
$row[] = new tabobject('managefeeds', $script,
            get_string('managefeeds', 'block_rss_client'));

$tabs[] = $row;

/// Print out the tabs and continue!
print "\n".'<div class="tabs">'."\n";
print_tabs($tabs, $currentaction);
print '</div>' . print_location_comment(__FILE__, __LINE__, true);
?>
