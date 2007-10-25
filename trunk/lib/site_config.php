<?
//contains local site information
/*
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */

//set some PHP settings.
ini_set('session.auto_start', 1);				//automatically start a session
ini_set('session.hash_bits_per_character', 4);	//ensure the session id is 32 characters long.

require_once(dirname(__FILE__) .'/cs-content/contentSystemClass.php');
require_once(dirname(__FILE__) .'/cs-content/cs_fileSystemClass.php');
require_once(dirname(__FILE__) .'/cs-phpxml/xmlCreatorClass.php');
require_once(dirname(__FILE__) .'/cs-phpxml/xmlParserClass.php');
require_once(dirname(__FILE__) .'/cs-content/cs_globalFunctions.php');
require_once(dirname(__FILE__) .'/upgradeClass.php');

define(CONFIG_FILENAME, 'config.xml');
//Set of functions that should be usefull to everyone


set_exception_handler('exception_handler');

//##########################################################################
function exception_handler($exception) {
	$exceptionMessage = $exception->getMessage();
	
	
	//attempt to log the problem; if it happens too early, we can't do much about it.
	try {
		include(dirname(__FILE__) ."/includes.php");
		include_once(dirname(__FILE__) ."/globalFunctions.php");
		if(function_exists('get_config_db_params') && class_exists('cs_phpDB') && class_exists('logsClass')) {
			$db = new cs_phpDB;
			$db->connect(get_config_db_params());
			$logs = new logsClass($db, "EXCEPTION");
			
			$details = "Uncaught exception: ". $exceptionMessage;
			if(function_exists('cs_debug_backtrace')) {
				$details .= "\n\n: BACKTRACE FOLLOWS: ". cs_debug_backtrace(0);
			}
			$logs->log_by_class($details, 'error');
		}
		else {
			//that's right.  
			throw new exception(__FUNCTION__ .": unable to log error, class or function not available");
		}
	}
	catch(exception $e) {
		//do something here...
		print "<pre><h3>SECOND FATAL ENCOUNTER OCCURRED (while handling first error)</h3> ". $e->getMessage() ."</pre>\n";
	}
}//exception_handler()
//##########################################################################


//-------------------------------------------------------------------
function read_config_file($setEverything=TRUE) {
	if(!file_exists(dirname(__FILE__) .'/'. CONFIG_FILENAME)) {
		$gf = new cs_globalFunctions;
		$gf->conditional_header("/setup?from=". urlencode($_SERVER['REQUEST_URI']));
		exit;
	}
	
	$fs = new cs_fileSystemClass(dirname(__FILE__));
	
	$xmlString = $fs->read(CONFIG_FILENAME);
	
	
	//parse the file.
	$xmlParser = new xmlParser($xmlString);
	
	$config = $xmlParser->get_tree(TRUE);
	$config = $config['CONFIG'];
	unset($config['type'], $config['attributes']);
	
	$conditionallySet = array('VERSION_STRING', 'WORKINGONIT');
	foreach($config as $index=>$value) {
		if(in_array($index, $conditionallySet)) {
			//only set this part if we're told to.
			if($setEverything) {
				define($index, $value);
			}
		}
		else {
			define($index, $value);
		}
	}
	
	return($config);
	
}//end read_config_file()
//-------------------------------------------------------------------

check_external_lib_versions();

	
if(!defined("PROJECT__INITIALSETUP") || PROJECT__INITIALSETUP !== TRUE) {
	$config = read_config_file(FALSE);
	
	if(($config['WORKINGONIT'] != "0" && strlen($config['WORKINGONIT'])) || strlen($config['WORKINGONIT']) > 1) {
		//TODO: consider making this look prettier...
		$details = "The website/database is under construction... try back in a bit.";
		if(preg_match('/upgrade/i', $config['WORKINGONIT'])) {
			$details = "<b>Upgrade in progress</b>: ". $config['WORKINGONIT'];
		}
		elseif(strlen($config['WORKINGONIT']) > 1) {
			$details .= "MORE INFORMATION::: ". $config['WORKINGONIT'];
		}
		throw new exception($details);
	}
	else {
		//don't panic: we're going to check for upgrades, but this doesn't
		//	necessarily mean anything will ACTUALLY be upgraded.
		$upgrade = new upgrade;
		if($upgrade->upgrade_in_progress()) {
			throw new exception("Upgrade in progress... reload the page after a few minutes and it should be complete.  :) ");
		}
		else {
			$upgrade->check_versions();
		}
		read_config_file(TRUE);
	}
}

if($_SERVER['DOCUMENT_ROOT']) {
	//it was called from the web...
	$GLOBALS['SITE_ROOT'] = $_SERVER['DOCUMENT_ROOT'];
	$GLOBALS['SITE_ROOT'] = str_replace("/public_html", "", $GLOBALS['SITE_ROOT']);
}
else {
	//called from the command line.
	$GLOBALS['SITE_ROOT'] = $_SERVER['HOME'] ."/partslogistics2002";
}

$GLOBALS['LIBDIR']=$GLOBALS['SITE_ROOT'] . "/lib";
$GLOBALS['TMPLDIR']=$GLOBALS['SITE_ROOT'] . "/templates";

//define an array of status_id's that are "NOT ENDED".
$GLOBALS['STATUS_NOTENDED'] = array(0,1,2,6);

?>
