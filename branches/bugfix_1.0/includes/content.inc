<?php
/*
 * SVN INFORMATION:::
 * ------------------
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */

require_once(dirname(__FILE__) ."/../lib/cs-content/cs_tabsClass.php");
require_once(dirname(__FILE__) .'/../lib/bbCode/bbCodeParser.class.php');


$db = new cs_phpDB;
$db->connect(get_config_db_params());
$page->db = &$db;

$page->session = new Session($page->db);

//initialize everything we need to display the page & remember stuff in the session.
$page->ui = new sessionCache("/userInput/content");

//The "sectionArr" is provided from contentSystem::finish() as a local var, and ftsSections is a leftover from fast_templating.
$page->ftsSections = $sectionArr;


$page->add_template_var('cs-project_version', VERSION_STRING);


//done with redirection.  Here's where we define if they have to be logged-in, and then run the fast_templating engine.
//TODO: update this to be "cs-content friendly".  :) 
$mustBeLoggedIn = 1;

$userSettings = $page->ui->get_cache("userData/settings");
$defaultModule = $userSettings['startmodule'];
if(!isset($defaultModule) || strlen($defaultModule) < 2) {
	$defaultModule = "project";
}

$user = new userClass($page->db, $_SESSION['uid']);

if(!$page->ui->get_cache("userData/settings")) {
	$page->ui->set_cache("userData/settings", $user->get_settings());
}


//REDIRECTION
if(is_numeric($_GET['changeGroup'])) {
	$lastModule = $page->ui->get_cache('lastModule');
	
	if(!strlen($lastModule)) {
		$lastModule = 'project';
	}
	
	//TODO: make sure the group_id given is valid!
	$_SESSION['group_id'] = $_GET['changeGroup'];
	conditional_header("/content/". $lastModule);
	exit;
}
elseif(!isset($page->ftsSections[1]) || (count($page->ftsSections) == 2 && $page->ftsSections[1] == 'index')) {
	//don't allow access to / or /content: just put them directly to /content/<lastModuleTheyWereOn>.
	$ui = new sessionCache();
	conditional_header("/content/". $defaultModule);
	exit;
}





//remember what the last module used was, for possible redirection later.
$page->ui->set_cache("lastModule", $page->ftsSections[1]);




//before going on, let's see if the URL is set properly, so we load the correct section.
//NOTE: "ftsSections" is a special array from the "fast_templating" engine.
if(!isset($page->ftsSections[1])) {
	//not set properly.  conditional_header() their @ss.
	if($defaultModule == "projects") {
		//check for old misspellings, and fix them.
		$defaultModule = "project";
	}
	conditional_header("/content/$defaultModule");
	exit;
}

//now, get the user's group list.
$page->add_template_var("group_option_list", $user->get_group_list());

//check to see if they're the "chef" of the current group...
$groupList = $user->get_groups(FALSE);
if($groupList[$_SESSION['user_group']]['leader_uid'] == $_SESSION['uid']) {
	$_SESSION['isGroupOwner'] = 1;
} else {
	$_SESSION['isGroupOwner'] = 0;
}

//TODO: this should be taken care of already, so there should ALWAYS be a module selected.
$module = $page->ftsSections[1];
if($module && strlen($module) > 1) {
	//got something from the call above...
	$page->ui->set_cache("module", $module);
}
else {
	//use the default module...
	$module = $defaultModule;
	$page->ui->set_cache("module", $module);
}
$page->add_template_var("module", $module);


//okay, setup the tabs so they look... right.
$tabNames = array(
	'summary'		=> "Summary",
	'project'		=> "Projects",
	'helpdesk'		=> "Helpdesk",
);

if($user->is_admin()) {
	$tabNames['settings']	= "Settings<BR>\n<b>[Admin]</b>";
}
else {
	$tabNames['settings']	= "Settings";
}

#$page->add_template_var('tabs', $page->file_to_string('tabs.shared.tmpl'));
$tabObj = new cs_tabs($page);
foreach($tabNames as $moduleName=>$name) {
	$tabUrl = "/content/". $moduleName;
	
	if($moduleName == $page->ftsSections[1]) {
		$tabObj->select_tab($name);
	}
	$tabObj->add_tab($name, $tabUrl);
}
$tabObj->display_tabs();



$proj = new projectClass($page->db);
$helpdeskObj = new helpdeskClass($page->db);
$bbCodeParser = new bbCodeParser($proj, $helpdeskObj);

//before going on, determine how stuff should be sorted...
$currentSortArr = store_and_return_sorting($page, $_GET['sort'], $_GET['sortType']);



//=============================================================================
function create_page_title(cs_genericPage &$page, array $parts) {
	$titleCacheURL = '/pageData/title';
	$argCacheURL = '/pageData/pieces';
	
	$cachedParts = $page->ui->get_cache($argCacheURL);
	if(!strlen($parts['module']) || !strlen($parts['title'])) {
		if(!strlen($parts['module']) && !strlen($cachedParts['module'])) {
			throw new exception(__METHOD__ .": found cache, but module missing");
		}
	}

	if(!strlen($parts['module'])) {
		$parts['module'] = $cachedParts['module'];
	}
	$retval = ucfirst($parts['module']) ."" . " [". PROJ_NAME ."]";
	if(strlen($parts['title'])) {
		$retval = ucwords($parts['title']) ." -- ". $retval;
	}
	
	$page->ui->set_cache($argCacheURL, $parts);
	$page->ui->set_cache($titleCacheURL, $retval);
	
	$page->add_template_var('html_title', $retval);
	
	return($retval);
}//end create_page_title();
//=============================================================================



//=============================================================================
function get_page_title(cs_genericPage &$page) {
	$cacheURL = '/pageData/title';
	return($page->ui->get_cache($cacheURL));
}//end get_page_title()
//=============================================================================



$titlePart = NULL;
if(isset($sectionArr[2])) {
	$titlePart = $sectionArr[2];
	if(strlen($_GET['ID'])) {
		$titlePart .= " #". $_GET['ID'];
	}
}

if(ISDEVSITE) {
	$titlePart = "**DEV** ". $titlePart;
}

$titleParts = array(
	'module'	=> $module,
	'title'		=> $titlePart
);
create_page_title($page, $titleParts);


?>