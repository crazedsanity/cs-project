<?php
/*
 * Created on Mar 20, 2006
 *       by
 *  Dan Falconer
 */

//check to make sure if an action is specified, that we've got everything needed: if
//	 we don't, set a message & do a conditional_header().
//initialize everything we need to display the page & remember stuff in the session.
$proj = new projectClass($page->db);
$page->ui = new sessionCache("/userInput/extern");

//alright... if they're trying to go to an old URL, redirect them to the proper URL.
if($_SERVER['PHP_SELF'] == "/extern/helpdesk_cust.php") {
	if(strlen($_GET['check']) == 32 && !isset($_GET['action'])) {
		$_GET['action'] = "respond";
	}
	$url = "/extern/helpdesk/";
	switch($_GET['action']) {
		//----------------------------------------------------------------
		case "view": {
			$url = "/extern/helpdesk/view";
			unset($_GET['action']);
		}
		break;
		//----------------------------------------------------------------
		
		
		//----------------------------------------------------------------
		case "respond":{
			$url = "/extern/helpdesk/view";
			$_GET['from'] = "respondLink";
			unset($_GET['action']);
		}
		break;
		//----------------------------------------------------------------
	}
	
	//add any extra vars in $_GET to the url... 
	if(count($_GET > 0)) {
		$extraGetVarsStr = string_from_array($_GET, "url");
		$url .= "?". $extraGetVarsStr;
	}
	conditional_header($url);
	exit;
} elseif(!isset($page->ftsSections[1])) {
	//they went to a url like "/extern/".  
	set_message_wrapper(array(
		"title"		=> "Automatic Redirection",
		"message"	=> "You have been automatically redirected here from " .
				"<b>". $_SERVER['REQUEST_URI'] ."</b>.",
		"type"		=> "notice"
	));
	conditional_header("/extern/helpdesk");
	exit;
}

?>
