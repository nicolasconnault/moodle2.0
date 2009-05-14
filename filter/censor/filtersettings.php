<?php  //$Id: filtersettings.php,v 1.1 2007/12/19 17:35:28 skodak Exp $

$settings->add(new admin_setting_configtextarea('filter_censor_badwords', get_string('badwordslist','admin'),
               get_string('badwordsconfig', 'admin').'<br />'.get_string('badwordsdefault', 'admin'), ''));

?>
