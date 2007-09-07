<?php

require_once(dirname(__FILE__) ."/../lib/cs-content/cs_tabsClass.php");


//initialize everything we need to display the page & remember stuff in the session.
$page->ui = new sessionCache("/userInput/content");

//The "sectionArr" is provided from contentSystem::finish() as a local var, and ftsSections is a leftover from fast_templating.
$page->ftsSections = $sectionArr;


$page->add_template_var('cs-content_version', VERSION_STRING);


//done with redirection.  Here's where we define if they have to be logged-in, and then run the fast_templating engine.
//TODO: update this to be "cs-content friendly".  :) 
$mustBeLoggedIn = 1;

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



























//create a user class.
$page->db = new cs_phpDB;
$page->db->connect(get_config_db_params());
$user = new userClass($page->db, $_SESSION['uid']);

if(!$page->ui->get_cache("userData/settings")) {
	$page->ui->set_cache("userData/settings", $user->get_settings());
}

$userSettings = $page->ui->get_cache("userData/settings");
$defaultModule = $userSettings['startmodule'];
if(!isset($defaultModule) || strlen($defaultModule) < 2) {
	$defaultModule = "project";
} 

if(is_numeric($_GET['changeGroup'])) {
	$lastModule = $page->ftsSections[1];
	$_SESSION['group_id'] = $_GET['changeGroup'];
	conditional_header("/content/". $lastModule);
	exit;
}

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
} else {
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

//before going on, determine how stuff should be sorted...
$currentSortArr = store_and_return_sorting($page, $_GET['sort'], $_GET['sortType']);




if(!$moduleTitle) {
	$moduleTitle = ucfirst($module);
}
$htmlTitle = "$moduleTitle [". PROJ_NAME ."]";
if($titleSub) {
	$htmlTitle .= " -- ". $titleSub;
}
$page->add_template_var("html_title", $htmlTitle);

?>