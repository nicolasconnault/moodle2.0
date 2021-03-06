<?php   // $Id: timezone.php,v 1.11 2009/01/02 11:00:55 skodak Exp $

    require_once('../config.php');

    $zone = optional_param('zone', '', PARAM_RAW);

    if (!is_numeric($zone)) {
         //not a path, but it looks like it anyway
         $zone = clean_param($zone, PARAM_PATH);
    }    

    require_login();

    require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

    $strtimezone = get_string("timezone");
    $strsavechanges = get_string("savechanges");
    $strusers = get_string("users");
    $strall = get_string("all");

    print_header($strtimezone, $strtimezone, build_navigation(array(array('name' => $strtimezone, 'link' => null, 'type' => 'misc'))));

    print_heading("");

    if (data_submitted() and !empty($zone) and confirm_sesskey()) {
        echo "<center>";
        $DB->execute("UPDATE {user} SET timezone = ?", array($zone));
        echo "</center>";

        $USER->timezone = $zone;
        $current = $zone;
        notify('Timezone of all users changed', 'notifysuccess');
    } else {
        $current = 99;
    }

    require_once($CFG->dirroot.'/calendar/lib.php');
    $timezones = get_list_of_timezones();

    echo '<center><form action="timezone.php" method="post">';
    echo "$strusers ($strall): ";
    choose_from_menu ($timezones, "zone", $current, get_string("serverlocaltime"), "", "99");
    echo "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\" />";
    echo '<input type="submit" value="'.s($strsavechanges).'" />';
    echo "</form></center>";

    print_footer();

?>
