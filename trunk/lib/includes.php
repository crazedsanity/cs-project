<?
/*
 * SVN INFORMATION:::
 * SVN Signature: $Id:includes.php 8 2007-08-23 23:22:35Z crazedsanity $
 * Last Committted Date: $Date:2007-08-23 18:22:35 -0500 (Thu, 23 Aug 2007) $
 * Last Committed Path: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/lib/includes.php $
 */

require_once(dirname(__FILE__) ."/site_config.php");
require_once(dirname(__FILE__) ."/globalFunctions.php");

$GLOBALS['templateVars'] = array(
	"lib_path"	=> $GLOBALS['LIBDIR']
);
#error_reporting(E_ALL);
$langua = getenv("HTTP_ACCEPT_LANGUAGE");
$lib_path = $GLOBALS['LIBDIR'];
$img_path = "/img";

//set all the old PHProjekt vars as template vars...
#require_once("old_project_vars.php");
#foreach($oldProjectWording as $varName => $varVal) {
#debug_print("SETTING [$varName] as [$varVal]");
#	$GLOBALS['templateVars'][$varName] = $varVal;
#	$$varName = $varVal;
#}
#require_once("gpcs_vars.inc.php");

$GLOBALS['templateFiles'] = array(
);

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
define('FORMAT_WORDWRAP',	100);


require_once(dirname(__FILE__) ."/cs-content/contentSystemClass.php");
require_once(dirname(__FILE__) ."/logsClass.php");
require_once(dirname(__FILE__) ."/session_class.php");
require_once(dirname(__FILE__) ."/sessionCacheClass.php");
require_once(dirname(__FILE__) ."/mainRecordClass.php");
require_once(dirname(__FILE__) ."/projectClass.php");
require_once(dirname(__FILE__) ."/noteClass.php");
require_once(dirname(__FILE__) ."/todoClass.php");
require_once(dirname(__FILE__) ."/helpdeskClass.php");
require_once(dirname(__FILE__) ."/emailFaxClass.php");
require_once(dirname(__FILE__) ."/userClass.php");
require_once(dirname(__FILE__) ."/tagClass.php");
require_once(dirname(__FILE__) ."/prefClass.php");
?>
