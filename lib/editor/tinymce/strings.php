<?php
define('NO_MOODLE_COOKIES', true);

require_once('../../../config.php');

$contexturl = optional_param('context', null, PARAM_URL);
$isdialog = optional_param('dlg', false, PARAM_BOOL);
$lang = optional_param('lang', 'en_utf8', PARAM_ALPHANUMEXT);
$SESSION->lang = $lang;

$output = '';

// get the keys from the reference english translations
$string = array();
include_once($CFG->dirroot .'/lang/en_utf8/tinymce.php');
$keys = array_keys($string);

if (!is_null($contexturl)) {
    $context = array_pop(explode('/tinymce/jscripts/tiny_mce/', $contexturl));
    $contexts = explode('/', $context);
    $moduletype = $contexts[0];
    $modulename = $contexts[1];

    $dialogpostfix = '';
    if ($modulename && $isdialog) {
        $dialogpostfix = '_dlg';
    }

    $selectedkeys = preg_grep('/^'. $moduletype .'\/'. $modulename . $dialogpostfix .':/', $keys);
   
    $output = "tinyMCE.addI18n('$lang". ($modulename ? '.'.$modulename:'') ."$dialogpostfix',{\r\n";
    $i = count($selectedkeys);
    foreach($selectedkeys as $key) {
        $i--;
        $output .= substr($key, strpos($key, ':')+1) .':"'. addslashes_js(get_string($key, 'tinymce')) .'"';
        if ($i > 0) {
            $output .= ","; // must not add commas at the last element - breaks in IE 6 and 7.
        }
        $output .= "\r\n";
    }
    $output .= "});";
    

} else {
    $output = "tinyMCE.addI18n({". $lang .":{";
    $selectedkeys = preg_grep('/^main\//', $keys);
    $currentsection = '';
    $firstiteration = true;
    foreach($selectedkeys as $key) {
        $subkey = explode(':', array_pop(explode('/', $key)));
        $section = $subkey[0];
        $string = $subkey[1];
        if ($section != $currentsection) {
            $output .= "\r\n";
            if ($firstiteration) {
                $firstiteration = false;
            } else {
                $output .= "},\r\n"; 
            }
            $currentsection = $section;
            $output .= $currentsection .":{\r\n";
        } else {
            $output .= ",\r\n"; 
        }

        $output .= $string .':"'. addslashes_js(get_string($key, 'tinymce')) .'"';
    } 
    $output .= "\r\n}}});";
    
}

$lifetime = '86400';
@header('Content-type: text/javascript; charset=utf-8');
@header('Content-length: '.strlen($output));
@header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
@header('Cache-control: max-age='.$lifetime);
@header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .'GMT');
@header('Pragma: ');

echo $output;

?>
