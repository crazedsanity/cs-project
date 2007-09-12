<?
//contains local site information
/*
 * SVN INFORMATION:::
 * SVN Signature: $Id$
 * Last Committted Date: $Date$
 * Last Committed Path: $HeadURL$
 */

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
function exception_handler($exception)
{
	$exceptionMessage = $exception->getMessage();
	print "<pre><h2>FATAL EXCEPTION ENCOUNTERED</h2> ". $exceptionMessage ."\n\n";
}//exception_handler()
//##########################################################################


//-------------------------------------------------------------------
function read_config_file() {
	$GLOBALS['DEBUGPRINTOPT'] = 1;
	if(!file_exists(dirname(__FILE__) .'/'. CONFIG_FILENAME)) {
		$gf = new cs_globalFunctions;
		$gf->conditional_header("/setup?from=". urlencode($_SERVER['REQUEST_URI']));
		exit;
	}
	
	$fs = new cs_fileSystemClass(dirname(__FILE__));
	
	$xmlString = $fs->read(CONFIG_FILENAME);
	
	
	//parse the file.
	$xmlParser = new xmlParser($xmlString);
	
	$config = $xmlParser->get_tree();
	$config = $config['CONFIG'];
	unset($config['type'], $config['attributes']);
	
	foreach($config as $index=>$subArray) {
		$value = $subArray['value'];
		define($index, $value);
	}
	
}//end read_config_file()
//-------------------------------------------------------------------

check_external_lib_versions();

	
if(!defined("PROJECT__INITIALSETUP") || PROJECT__INITIALSETUP !== TRUE) {
	read_config_file();
	
	//don't panic: we're going to check for upgrades, but this doesn't
	//	necessarily mean anything will ACTUALLY be upgraded.
	$upgrade = new upgrade;
	if($upgrade->upgrade_in_progress()) {
		throw new exception("Upgrade in progress... reload the page after a few minutes and it should be complete.  :) ");
	}
	else {
		$upgrade->check_versions();
		read_config_file();
	}
}

if($_SERVER['DOCUMENT_ROOT']) {
	//it was called from the web...
	$GLOBALS['SITE_ROOT'] = $_SERVER['DOCUMENT_ROOT'];
	$GLOBALS['SITE_ROOT'] = str_replace("/public_html", "", $GLOBALS['SITE_ROOT']);
} else {
	//called from the command line.
	$GLOBALS['SITE_ROOT'] = $_SERVER['HOME'] ."/partslogistics2002";
}

$GLOBALS['LIBDIR']=$GLOBALS['SITE_ROOT'] . "/lib";
$GLOBALS['TMPLDIR']=$GLOBALS['SITE_ROOT'] . "/templates";

//define an array of status_id's that are "NOT ENDED".
$GLOBALS['STATUS_NOTENDED'] = array(0,1,2,6);

?>
