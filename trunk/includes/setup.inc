<?php
/*
 * Created on Jul 27, 2007
 * 
 */



//create the setup object.
require_once(dirname(__FILE__) ."/../lib/initialSetupClass.php");
require_once(dirname(__FILE__) ."/../lib/displaySetupClass.php");


$setupObj = new displaySetup($page);
$page->add_template_var("VERSION_STRING", $setupObj->get_version());


//now, let's determine what step we're on.
$gfObj = new cs_globalFunctions;

$currentStep = $setupObj->get_current_step();
if($_POST) {
	$result = $setupObj->process_step($currentStep, $_POST);
	
	$page->conditional_header("/setup/step/". $currentStep ."?result=". $result);
	exit;
}
else {
	if(!is_null($currentStep)) {
		
		$setupObj->display_step();
	}
	else {
		throw new exception(__FILE__ .", line #". __LINE__ .": failed to get appropriate step.");
	}
}





?>