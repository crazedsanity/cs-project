<?php
/*
 * Created on Jul 27, 2007
 * 
 */



//create the setup object.
require_once(dirname(__FILE__) ."/../lib/cs-content/cs_phpDB.php");

if(!is_numeric($_SESSION['setup']['lastStep'])) {
	$_SESSION['setup']['lastStep'] = 1;
}

$stepNames = array(
	1	=> "Database Information",
	2	=> "Create Database + Build Schema",
	3	=> "Create Default Values",
	4	=> "Get Extra Information",
	5	=> "Write Config File + Tests"
);

//do some checking to make sure they're not doing something we don't want them to.
if(count($sectionArr) > 2 || ((count($sectionArr)) == 2 && !is_numeric($sectionArr[1]))) {
	//too many things in the URL.
	$page->set_message_wrapper(
		array(
			'title'		=> "Invalid URL",
			'message'	=> "The page you were trying to go view was invalid.",
			'type'		=> "error"
		)
	);
	$page->conditional_header("/setup". $_SESSION['setup']['lastStep'], TRUE);
}
elseif(count($sectionArr) == 2 && is_numeric($sectionArr[1]) && $sectionArr[1] != 1) {
	if(!is_numeric(get_setup_data($sectionArr[1], 'accessible'))) {
		$page->set_message_wrapper(
			array(
				'title'		=> "Incomplete Step",
				'message'	=> "Tried to go to a step that wasn't complete... ",
				'type'		=> "error"
			)
		);
		$page->conditional_header("/setup/". $_SESSION['setup']['lastStep'], TRUE);
	}
}


$page->add_template_var("VERSION_STRING", read_version_file());
$page->rip_all_block_rows('stepData');


foreach($stepNames as $num=>$name) {
	$stepResult = get_setup_data($num, 'result');
	if(!is_numeric($stepResult)) {
		$passFail = "Incomplete";
		$bgColor  = "yellow";
	}
	else {
		$passFail = interpret_bool($stepResult, array('FAIL', 'Pass'));
		$bgColor = interpret_bool($stepResult, array('red', 'green'));
		store_setup_data($num, 1, 'accessible');
		if($passFail == 'Pass') {
			$_SESSION['setup']['lastStep'] = $num;
		}
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