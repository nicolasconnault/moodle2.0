<?PHP /*  $Id: docstyles.php,v 1.4 2008/06/19 22:27:14 skodak Exp $ */

/// We use PHP so we can do value substitutions into the styles
    define('NO_MOODLE_COOKIES', true);                  // session not used here

    require_once("../../config.php");
    $themename = optional_param('themename', NULL, PARAM_SAFEDIR);

    $themeurl = style_sheet_setup(filemtime("styles.php"), 300, $themename);

///
/// You can hardcode colours in this file if you
/// don't care about this.

?>
