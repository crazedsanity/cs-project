<?php
/*
 * SVN INFORMATION:::
 * ------------------
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */

require_once(dirname(__FILE__) ."/../lib/includes.php");
require_once(dirname(__FILE__) ."/../lib/prefClass.php");

$page->clear_content("header");

$db = new cs_phpDB;
$db->connect(get_config_db_params());
$page->db = &$db;

$page->session = new Session($page->db);

if(!$_GET['logout'] && !$_POST && $page->session->sid_check == 1) {
	//they came here & need to be redirected.
	conditional_header("/content?from=login&reason=alreadyLoggedIn");
	exit;
} elseif($_GET['logout']) {
	//call the logout function.
	$res = $page->session->logout();
	conditional_header("/login.php?redirFrom=logout");
	exit;
} elseif($_POST['username'] && $_POST['password']) {


	$loginRes = $page->session->login($_POST['username'],$_POST['password']);
	
	if($loginRes == 1) {
		//TODO: use their "startmodule" (or whatever it's called) is if nothing specific is requested.
		$goHere = "/content?from=login";
		if(strlen($_SESSION['loginDestination']) && urldecode($_SESSION['loginDestination']) !== '/')
		{
			//go where they asked.
			$goHere = urldecode($_SESSION['loginDestination']);
			unset($_SESSION['loginDestination']);
		}
		else {
			$prefObj = new pref($page->db, $_SESSION['uid']);
			$startModule = $prefObj->get_pref_value_by_name('startModule');
			$goHere = "/content/". $startModule;
		}
		
		conditional_header($goHere);
		exit;
	}
}

if($_GET['destination'])
{
	$_SESSION['loginDestination'] = urlencode($_GET['destination']);
}
//show the default page.
$page->change_content(html_file_to_string("login.tmpl"));
$page->add_template_var("css_style", "/css/common.css");
$page->print_page();
exit;

?>