<?php
/*
 * Created on Jul 27, 2007
 * 
 */



//create the setup object.
require_once(dirname(__FILE__) ."/../lib/cs-content/cs_phpDB.php");


$page->add_template_var("VERSION_STRING", read_version_file());
$page->rip_all_block_rows();

$stepNames = array(
	1	=> "Database Information",
	2	=> "Create Database + Build Schema",
	3	=> "Create Default Values",
	4	=> "Get Extra Information",
	5	=> "Write Config File + Tests"
);


foreach($stepNames as $num=>$name) {
	$stepResult = get_setup_data($num, 'result');
	if(!is_numeric($stepResult)) {
		$passFail = "Incomplete";
		$bgColor  = "yellow";
	}
	else {
		$passFail = interpret_bool($stepResult, array('FAIL', 'Pass'));
		$bgColor = interpret_bool($stepResult, array('red', 'green'));
	}
	$repArr = array(
		'stepNum'		=> $num,
		'stepName'		=> $name,
		'passFail'		=> $passFail,
		'stepBGColor'	=> $bgColor
	);
	$myRows .= $page->gfObj->mini_parser($page->templateRows['step_data_row'], $repArr, '%%', '%%');
}
$page->add_template_var('step_data_row', $myRows);


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