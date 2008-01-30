<?php
/*
 * Created on Jan 29, 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */


require_once(dirname(__FILE__) ."/../../public_html/xajax/xajax.inc.php");





$GLOBALS['objects'] = array();
$GLOBALS['objects']['page'] =& $page;
$GLOBALS['objects']['proj'] =& $proj;
$GLOBALS['objects']['tag'] =& $proj->tagObj;


/**
 * Handles creation of javascript functions; AJAX callback functions 
 * should start with "ajax__", and can then be referenced in the HTML 
 * with the prefix "xajax_ajax__".  If the callback function is named 
 * "ajax__function()", the javascript call would be "xajax_ajax__function()".
 * 
 * NOTE: Because PHP lowercases function names when calling get_defined_functions(),
 * javascript calls must use the lowercase version; i.e. if the callback 
 * is named "ajax__showMeStuff()", the javascript would be
 * "xajax_ajax__showmestuff()".
 */
function addAjax() {
	$xajax = new xajax($_SERVER['PHP_SELF']);
	
	//automatically register all functions starting with "ajax__"
	$funcList = get_defined_functions();
	$funcList = $funcList['user'];
	foreach($funcList as $name) {
		if(preg_match('/^ajax__/', $name)) {
			$xajax->registerFunction($name);
		}
	}
	
	$xajax->processRequests();
	
	//since Xajax prints things, capture it in a buffer so it can be added as a template var.
	ob_start();
	$xajax->printJavascript('/xajax');
	$xajaxHeaders = ob_get_contents();
	ob_end_clean();
	
	$page = $GLOBALS['objects']['page'];
	$page->add_template_var('XAJAX_HEADERS', $xajaxHeaders);
}//end addAjax()

?>
