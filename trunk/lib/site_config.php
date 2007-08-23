<?
//contains local site information
/*
 * SVN INFORMATION:::
 * SVN Signature: $Id$
 * Last Committted Date: $Date$
 * Last Committed Path: $HeadURL$
 */

require_once(dirname(__FILE__) .'/externals/cs-content/cs_fileSystemClass.php');
require_once(dirname(__FILE__) .'/externals/cs-phpxml/xmlCreatorClass.php');
require_once(dirname(__FILE__) .'/externals/cs-phpxml/xmlParserClass.php');
require_once(dirname(__FILE__) .'/externals/cs-content/cs_globalFunctions.php');
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
		write_config_file();
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



//-------------------------------------------------------------------
function write_config_file() {
	$fs = new cs_filesystemClass(dirname(__FILE__));
	$xmlCreator = new xmlCreator('config');
	
	$defaults = array(
		'isdevsite'							=> 1,
		'db_host'							=> 'localhost',
		'db_name'							=> 'cs_project',
		'db_port'							=> '5432',
		'db_user'							=> 'postgres',
		'db_pass'							=> '',
		'helpdesk-issue-announce-email'		=> 'project_helpdesk_notifications@avsupport.com',
		'workingonit'						=> 0,
		'max_idle'							=> '2 hours',
		'max_time'							=> '18 hours',
		'stop_logins_on_global_alert'		=> 1,
		'debugprintopt'						=> 1,
		'debugremovehr'						=> 0,
		'project_url'						=> 'project.cs',
		'config_session_name'				=> 'CS_PROJECT_SESSID',
		'version_string'					=> 'BETA-3.3.1',
		'proj_name'							=> 'CS-Project'
		
		//TODO: run an "initial setup" script to populate certain values, like log_category_id's for things... 
	);
	
	foreach($defaults as $index=>$value) {
		$xmlCreator->add_tag($index, $value);
	}
	
	$xmlCreator->add_attribute('/config', array('generated' => date('Y-m-d H:m:s')));
	
	$gf = new cs_globalFunctions;
	$xmlString = $xmlCreator->create_xml_string();
	
	$gf->debug_print($gf->cleanString($xmlString, 'htmlentity_plus_brackets'),1);
	
	$fs->create_file(CONFIG_FILENAME);
	$fs->write($xmlString, CONFIG_FILENAME);
	
}//end write_config_file()
//-------------------------------------------------------------------

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
