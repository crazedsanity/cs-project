<?php
/*
 * Created on Mar 10, 2006
 *       by
 *  Dan Falconer
 */
/*This file should just be **SYMLINKED** as another filename: appropriate entries need to be made in the
	.htaccess file, to force it to be run as a PHP file.

EXAMPLE: public_html/content  ->  ./index.php
  ADD TO .htaccess:::
<files content>
	ForceType application/x-httpd-php
</files>
*/

require_once(dirname(__FILE__) ."/../lib/includes.php");

//done with redirection.  Here's where we define if they have to be logged-in, and then run the fast_templating engine.
$mustBeLoggedIn = 1;
require_once(dirname(__FILE__) ."/../includes/fast_templating.inc");

//REDIRECTION
if(!isset($page->ftsSections[1])) {
	//don't allow access to / or /content: just put them directly to /content/<lastModuleTheyWereOn>.
	$ui = new sessionCache();
	$defaultModule = $ui->get_cache("/userInput/content/module");
	if(!isset($defaultModule) || strlen($defaultModule) < 2) {
		$defaultModule = "project";
	}
	conditional_header("/content/$defaultModule");
	exit;
} elseif($_GET['module']) {
	//redirection for compatibility with old URL's.
	$getVarsArr = array();
	$goHere = "/content/". $_GET['module'];
	if($_GET['action']) {
		 $goHere .= "/". $_GET['action'];
		if(is_numeric($_GET['ID'])) {	
			$getVarsArr['ID'] = $_GET['ID'];
		}
	}
	$getVarsArr['autoRedir'] = "compatibility";
	$getVarStr = string_from_array($getVarsArr,"url","&");
	
	//set a message so they know about the auto-redirection.
	set_message_wrapper(array(
		"title"		=> "Automatic Redirection",
		"message"	=> "For compatibility with old URLs (from emails and such), you have been automatically redirected from " .
						"<b>". $_SERVER['REQUEST_URI'] ."</b>",
		"type"		=> "notice"
	));
	
	conditional_header($goHere .'?'. $getVarStr);
	exit;
}




if(!$moduleTitle) {
	$moduleTitle = ucfirst($module);
}
$htmlTitle = "$moduleTitle [". PROJ_NAME ."]";
if($titleSub) {
	$htmlTitle .= " -- ". $titleSub;
}
$page->add_template_var("html_title", $htmlTitle);

$page->print_page();
?>
