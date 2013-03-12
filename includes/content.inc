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

if(is_numeric($_SESSION['login_contact_id'])) {
	$contactObj = new contactClass($page->db);
	$contactObj->set_contact_id($_SESSION['login_contact_id']);
	$contactData = $contactObj->get_contact();
	
	$page->add_template_var('LOGGED_USERNAME', $_SESSION['login_username']);
	$page->add_template_var('LOGGED_CONTACT_ID', $_SESSION['login_contact_id']);
	$page->add_template_var('LOGGED_CONTACT_NAME', $contactData['fname'] .' '. $contactData['lname']);
	$page->add_template_var('LOGGED_COMPANY', $contactData['company']);
}

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
	'tags'			=> "Tags",
	'contacts'		=> "Contacts",
	'settings'		=> "Settings",
);

if($user->is_admin()) {
	$tabNames['settings']	= "Settings<BR>\n<b>[Admin]</b>";
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
$bbCodeParser = new bbCodeParser($proj, $proj->helpdeskObj);

//before going on, determine how stuff should be sorted...
$currentSortArr = store_and_return_sorting($page, $_GET['sort'], $_GET['sortType']);



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