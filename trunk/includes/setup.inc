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


//=============================================================================
function store_setup_data($step, $data, $type='data') {
	$_SESSION['setup'][$type][$step] = $data;
}//end store_setup_data()
//=============================================================================



//=============================================================================
function get_setup_data($step, $type='data') {
	return($_SESSION['setup'][$type][$step]);
}//end get_setup_data()
//=============================================================================

?>