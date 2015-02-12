<?php 

if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) @ob_start('ob_gzhandler');
header('Content-type: text/css; charset=UTF-8');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

define('DS', DIRECTORY_SEPARATOR);
define('PATH_ROOT', dirname(__FILE__) . DS);

/* ie browser */
$is_ie7 = strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'msie 7') !== false;
$is_ie6 = !$is_ie7 && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'msie 6') !== false;

/* yootools black css */
$black_css = isset($_GET['black_css']) ? (string) preg_replace('/[^A-Z0-9_\.-]/i', '', $_GET['black_css']) : '';

/* general styling */
loadCSS(PATH_ROOT.'styles/style.css');

/* default styling */
loadCSS(PATH_ROOT.'styles/default/style.css');
loadCSS(PATH_ROOT.'styles/default/black/style.css');

if ($is_ie6) {
	loadCSS(PATH_ROOT.'styles/default/ie6hacks.css');
}

/* quick styling */
loadCSS(PATH_ROOT.'styles/quick/style.css');
loadCSS(PATH_ROOT.'styles/quick/black/style.css');

if ($is_ie6) {
	loadCSS(PATH_ROOT.'styles/quick/ie6hacks.css');
}

/* nifty default styling */
loadCSS(PATH_ROOT.'styles/niftydefault/style.css');
loadCSS(PATH_ROOT.'styles/niftydefault/black/style.css');

if ($is_ie6) {
	loadCSS(PATH_ROOT.'styles/niftydefault/ie6hacks.css');
}

/* nifty quick styling */
loadCSS(PATH_ROOT.'styles/niftyquick/style.css');
loadCSS(PATH_ROOT.'styles/niftyquick/black/style.css');

if ($is_ie6) {
	loadCSS(PATH_ROOT.'styles/niftyquick/ie6hacks.css');
}

/* css loader */
function loadCSS($file) {
	global $is_ie6;
	
	if (is_readable($file)) {
		$content = file_get_contents($file);
		if ($is_ie6) {
			$content = fixIE6Png($content);
		}
		echo $content;
	}
}

/* ie png fix */
function fixIE6Png($content) {
	if (strpos($content, 'ie6png') === false) return $content;
	$path    = dirname($_SERVER['SCRIPT_NAME']).'/';
	$regex   = "#(.*)background:.*url\((.*)\).*;[[:space:]]*/\*[[:space:]]*ie6png:(scale|crop)[[:space:]]*\*/#";
	$replace = "$1filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='".$path."$2', sizingMethod='$3'); background: none;";		
	return preg_replace($regex, $replace, $content);
}

?>