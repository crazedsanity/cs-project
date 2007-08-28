<?php
/*
 * Created on Jul 27, 2007
 * 
 */



//create the setup object.
require_once(dirname(__FILE__) ."/../lib/cs-content/cs_phpDB.php");


$page->add_template_var("VERSION_STRING", read_version_file());


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



//=============================================================================
function read_version_file() {
	$retval = NULL;
	$fsObj = new cs_fileSystemClass(dirname(__FILE__) .'/..');
	
	//okay, all files present: check the version in the VERSION file.
	$lines = $fsObj->read('VERSION', TRUE);
	$versionLine = $lines[2];
	if(preg_match('/^VERSION: /', $versionLine)) {
		
		$retval = trim(preg_replace('/VERSION: /', '', $versionLine));
	}
	else {
		throw new exception(__METHOD__ .": could not find VERSION data");
	}
	
	return($retval);
}//end read_version_file()
//=============================================================================

?>