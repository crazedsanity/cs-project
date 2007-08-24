<?php
/*
 * Created on Jul 27, 2007
 * 
 */



//create the setup object.
require_once(dirname(__FILE__) ."/../lib/initialSetupClass.php");
$setupObj = new initialSetup;
$page->add_template_var("VERSION_STRING", $setupObj->get_version());


//now, let's determine what step we're on.
$currentStep = $_SESSION['step'];
$gfObj = new cs_globalFunctions;

$gfObj->debug_print($setupObj->get_step_data(),1);
exit;


?>