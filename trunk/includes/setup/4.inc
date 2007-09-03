<?php
/*
 * Created on Aug 23, 2007
 * 
 */


//format: {configName} => array({minLength}, {cleanStringArg}, {defaultValue})
$defaultValues = array(
	'isdevsite'						=> array(1,		'numeric',	0),
	'proj_name'						=> array(10,	'sql', 		"CS-Project"),
	'project_url'					=> array(5,		'sql',		$_SERVER['HTTP_HOST']),
	'helpdesk_issue_annouce_email'	=> array(7,		'email',	"new_issues@yourdomain.com"),
	'max_time'						=> array(5,		'sql',		"18 hours"),
	'max_idle'						=> array(5,		'sql',		"2 hours"),
	'config_session_name'			=> array(9,		'alpha',	"CS_PROJECT_SESSID"),
	'stop_logins_on_global_alert'	=> array(1,		'numeric',	1),
	'debugprintopt'					=> array(1,		'numeric',	0),
	'debugremovehr'					=> array(1,		'numeric',	0),
	'workingonit'					=> array(1,		'numeric',	0)
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
		#$priorSetupData = $defaultValues;
		$priorSetupData = array();
		foreach($defaultValues as $name=>$subData) {
			$priorSetupData[$name] = $subData[2];
		}
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

function verify_submitted_data(cs_genericPage &$page, array $checkThis, array $againstThis) {
	foreach($againstThis as $index=>$subData) {
		if(!is_array($subData) || count($subData) !== 3 || !is_numeric($subData[0])) {
			$retval = __FUNCTION__ .": failed to get valid data to check against";
		}
		else {
			$valueToCheck = $checkThis[$index];
			$minLength = $subData[0];
			$cleanAs = $subData[1];
			if(isset($checkThis[$index]) && strlen($valueToCheck) >= $minLength) {
				//preliminary checks seem good.
				$valueToCheck = $page->gfObj->cleanString($valueToCheck, $cleanAs);
				if(strlen($valueToCheck) >= $minLength) {
				}
				else {
					$retval = __FUNCTION__ .": ". $index ." failed minimum length check after cleaning";
				}
			}
			else {
				$retval = __FUNCTION__ .": ". $index ." contains invalid data";
			}
		}
	}
	
	return($retval);
}//end verify_submitted_data()

?>