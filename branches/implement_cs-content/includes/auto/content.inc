<?


//initialize everything we need to display the page & remember stuff in the session.
$page->ui = new sessionCache("/userInput/content");


//create a user class.
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
$tabNames = array();
$tabNames['summary']	= new Tab("", "Summary");
$tabNames['project']	= new Tab("", "Projects");
$tabNames['helpdesk']		= new Tab("", "Helpdesk");
#$tabNames['tags']		= new Tab("", "Tags");
if($user->is_admin()) {
	$tabNames['settings']	= new Tab("", "Settings<BR>\n<b>[Admin]</b>");
}
else {
	$tabNames['settings']	= new Tab("", "Settings");
}

foreach($tabNames as $moduleName=>$tab) {
	if($moduleName == $module) {
		$tab->selected = 1;
	}
	
	//check if there's already a URL or not...
	if(strlen($tab->url) < 5) {
		//automatically set the tab name.
		$tab->url = "/content/$moduleName";
	}
	$page->add_tab($tab->url,$tab->title, "header", $tab->selected);
}


$proj = new projectClass($page->db);

//before going on, determine how stuff should be sorted...
$currentSortArr = store_and_return_sorting($page, $_GET['sort'], $_GET['sortType']);

?>