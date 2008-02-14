<?php
//contains local site information
/*
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id:site_config.php 626 2007-11-20 16:54:11Z crazedsanity $
 * Last Author::::::::: $Author:crazedsanity $ 
 * Current Revision:::: $Revision:626 $ 
 * Repository Location: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/lib/site_config.php $ 
 * Last Updated:::::::: $Date:2007-11-20 10:54:11 -0600 (Tue, 20 Nov 2007) $
 */


require(dirname(__FILE__) .'/globalFunctions.php');
require(dirname(__FILE__) .'/phpmailer/class.phpmailer.php');
require(dirname(__FILE__) .'/abstractClasses/dbAbstract.class.php');
require(dirname(__FILE__) .'/session_class.php');
require(dirname(__FILE__) .'/upgradeClass.php');
require_once(dirname(__FILE__) .'/cs-content/cs_phpDB.php');
require_once(dirname(__FILE__) .'/cs-content/contentSystemClass.php');
require_once(dirname(__FILE__) .'/cs-content/cs_fileSystemClass.php');
require_once(dirname(__FILE__) .'/cs-phpxml/xmlCreatorClass.php');
require_once(dirname(__FILE__) .'/cs-phpxml/xmlParserClass.php');
require_once(dirname(__FILE__) .'/cs-content/cs_globalFunctions.php');
require_once(dirname(__FILE__) .'/config.class.php');

define(CONFIG_FILENAME, 'config.xml');
//Set of functions that should be usefull to everyone


set_exception_handler('exception_handler');

//##########################################################################
function exception_handler($exception) {
	$exceptionMessage = $exception->getMessage();
	
	
	//attempt to log the problem; if it happens too early, we can't do much about it.
	try {
			print "<pre><h3>FATAL EXCEPTION ENCOUNTERED: </h3>". $exception->getMessage() ."</pre>";
		include_once(dirname(__FILE__) ."/globalFunctions.php");
		if(function_exists('get_config_db_params') && class_exists('cs_phpDB') && class_exists('logsClass')) {
			$db = new cs_phpDB;
			$db->connect(get_config_db_params(), TRUE);
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


$configObj = new config(dirname(__FILE__) .'/'. CONFIG_FILENAME, FALSE);
$configObj->read_config_file(TRUE, TRUE);

check_external_lib_versions();

	
if(!defined("PROJECT__INITIALSETUP") || PROJECT__INITIALSETUP !== TRUE) {
	$config = $configObj->read_config_file(FALSE);
	
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
		$configObj->read_config_file(TRUE);
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



$GLOBALS['templateVars'] = array(
	"PHP_SELF"		=> $_SERVER['PHP_SELF'],
	"cs-content_version"	=> VERSION_STRING,
	"PROJ_NAME"				=> PROJ_NAME
);


//define some constants...
define('SEQ_HELPDESK',		'special__helpdesk_public_id_seq');
define('SEQ_PROJECT',		'special__project_public_id_seq');
define('SEQ_MAIN',			'record_table_record_id_seq');
define('TABLE_TODOCOMMENT',	'todo_comment_table');
define('FORMAT_WORDWRAP',	90);

//=========================================================================
/**
 * Special PHP5 function: last-ditch effort to include all files necessary to 
 * make this class work.
 */
function __autoload($className) {
	$retval = FALSE;
	$possible = array(
		dirname(__FILE__) .'/'. $className .'.php',
		dirname(__FILE__) .'/'. $className .'Class.php',
		dirname(__FILE__) .'/'. $className .'.class.php',
		dirname(__FILE__) .'/abstractClasses/'. $className .'.abstract.php'
	);
	
	foreach($possible as $fileName) {
		if(file_exists($fileName)) {
			require_once($fileName);
			$retval = TRUE;
			break;
		}
	}
	
	if($retval !== TRUE) {
		throw new exception(__FUNCTION__ .": unable to find class file for (". $className .")");
	}
	
	return($retval);
	
}//end __autoload()
//=========================================================================

?>
