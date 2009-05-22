<?php  /// $Id$
/// Load up any required Javascript libraries

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
?>

<!--<style type="text/css">/*<![CDATA[*/ body{behavior:url(<?php echo $CFG->httpswwwroot ?>/lib/csshover.htc);} /*]]>*/</style>-->

<script type="text/javascript" src="<?php echo $CFG->httpswwwroot ?>/lib/javascript-static.js"></script>
<script type="text/javascript" src="<?php echo $CFG->httpswwwroot ?>/lib/javascript-mod.php"></script>
<script type="text/javascript" src="<?php echo $CFG->httpswwwroot ?>/lib/overlib/overlib.js"></script>
<script type="text/javascript" src="<?php echo $CFG->httpswwwroot ?>/lib/overlib/overlib_cssstyle.js"></script>
<script type="text/javascript" src="<?php echo $CFG->httpswwwroot ?>/lib/cookies.js"></script>
<script type="text/javascript" src="<?php echo $CFG->httpswwwroot ?>/lib/ufo.js"></script>
<script type="text/javascript" src="<?php echo $CFG->httpswwwroot ?>/lib/dropdown.js"></script>

<script type="text/javascript" defer="defer">
//<![CDATA[
setTimeout('fix_column_widths()', 20);
//]]>
</script>
<script type="text/javascript">
//<![CDATA[
var id2clientid = {};
var id2itemid   = {};
<?php
if (!empty($focus)) {
    if(($pos = strpos($focus, '.')) !== false) {
        //old style focus using form name - no allowed inXHTML Strict
        $topelement = substr($focus, 0, $pos);
        echo "addonload(function() { if(document.$topelement) document.$focus.focus(); });\n";
    } else {
        //focus element with given id
        echo "addonload(function() { if(el = document.getElementById('$focus')) el.focus(); });\n";
    }
    $focus = false; // Prevent themes from adding it to body tag which breaks addonload(), MDL-10249
}
?>
//]]>
</script>
<?php
    // editors integrations
    //TODO: optimize loading of editors
    if (empty($CFG->texteditors)) {
        $CFG->texteditors = 'tinymce,textarea';
    }
    $activeeditors = explode(',', $CFG->texteditors);
    foreach ($activeeditors as $editor) {
        if ($editor = get_texteditor($editor)) {
            echo $editor->header_js();
        }
    }
?>