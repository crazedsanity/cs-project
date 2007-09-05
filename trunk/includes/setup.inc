<?php
/*
 * Created on Jul 27, 2007
 * 
 * TODO: check for the existence of the /lib/config.xml file
 * TODO: if the config.xml exists, check if the database connection works, and if we can get ANY version data: if so, give fatal message saying it won't work.
 * TODO: if step 2 is re-run, do something to reset the status/accessibility of the other steps.
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
	$page->conditional_header("/setup/". $_SESSION['setup']['lastStep'], TRUE);
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


//determine the current step based on the URL ($sectionArr is provided by contentSystem).
$currentStep = $sectionArr[1];


$tmplStepTitle = "Main Setup Screen";
foreach($stepNames as $num=>$name) {
	$stepResult = get_setup_data($num, 'result');
	if(!is_numeric($stepResult)) {
		$passFail = "Incomplete";
		$bgColor  = "yellow";
		if(strlen(get_setup_data($num, 'text'))) {
			$stepText = get_setup_data($num, 'text');
		}
		else {
			$stepText = "Step incomplete...";
		}
	}
	else {
		$passFail = interpret_bool($stepResult, array('FAIL', 'Pass'));
		$bgColor = interpret_bool($stepResult, array('red', 'green'));
		
		if(strlen(get_setup_data($num, 'text'))) {
			$stepText = get_setup_data($num, 'text');
		}
		else {
			$stepText = "&nbsp;";
		}
		
		store_setup_data($num, 1, 'accessible');
		if($passFail == 'Pass') {
			$_SESSION['setup']['lastStep'] = $num;
		}
	}
	$curStepL = "&nbsp;";
	$curStepR = "&nbsp;";
	if($currentStep == $num) {
		$curStepL = "&gt;";
		$curStepR = "&lt;";
		$tmplStepTitle = "Step #". $num .": ". $name;
	}
	$repArr = array(
		'stepNum'		=> $num,
		'stepName'		=> $name,
		'passFail'		=> $passFail,
		'stepBGColor'	=> $bgColor,
		'stepText'		=> $stepText,
		'isCurStepL'	=> $curStepL,
		'isCurStepR'	=> $curStepR
	);
	$myRows .= $page->gfObj->mini_parser($page->templateRows['step_data_row'], $repArr, '%%', '%%');
}
$page->add_template_var('stepTitle', $tmplStepTitle);
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



//=============================================================================
function get_db_params() {
	
	$stepOneData = get_setup_data(1, 'data');
	if(is_array($stepOneData)) {
		$params = array();
		foreach($stepOneData as $name=>$value) {
			$index = preg_replace('/^database__/', '', $name);
			$params[$index] = $value;
		}
	}
	else {
		throw new exception(__FUNCTION__ .": unable to retrieve step one data...");
	}
	
	return($params);
}//end get_db_params()
//=============================================================================

?>