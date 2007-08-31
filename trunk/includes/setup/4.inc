<?php
/*
 * Created on Aug 23, 2007
 * 
 */



$defaultValues = array(
	'isdevsite'						=> 0,
	'proj_name'						=> "CS-Project",
	'project_url'					=> $_SERVER['HTTP_HOST'],
	'helpdesk_issue_annouce_email'	=> "new_issues@yourdomain.com",
	'max_time'						=> "18 hours",
	'max_idle'						=> "2 hours",
	'config_session_name'			=> "CS_PROJECT_SESSID",
	'stop_logins_on_global_alert'	=> 1,
	'debugprintopt'					=> 0,
	'debugremovehr'					=> 0,
	'workingonit'					=> 0
);


if($_POST) {
	//TODO: something to *CHECK* that they actually provided something...
	store_setup_data(4, $_POST['fields'], 'data');
	#store_setup_data(4, 1, 'result');
	#store_setup_data(4, 'Values stored successfully', 'text');
	#store_setup_data(5, 1, 'accessible');
	
	$page->conditional_header("/setup/4", TRUE);
}
else {
	$priorSetupData = get_setup_data(4, 'data');
	if(!is_array($priorSetupData) || !count($priorSetupData)) {
		$priorSetupData = $defaultValues;
	}
	
	foreach($priorSetupData as $index=>$value) {
		//check if it's a yes/no type thing.
		if(is_numeric($defaultValues[$index])) {
			$index .= "__". $value ."__checked";
			$value = "selected";
		}
		$page->add_template_var($index, $value);
	}
	
	$page->add_template_var("currentUrl", $_SERVER['HTTP_HOST']);
}

?>