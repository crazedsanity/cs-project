<?php
/*
 * Created on Jul 27, 2007
 * 
 * SVN INFORMATION:::
 * ------------------
 * Last Author::::::::: $Author:crazedsanity $ 
 * Current Revision:::: $Revision:638 $ 
 * Repository Location: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/includes/setup.inc $ 
 * Last Updated:::::::: $Date:2007-11-20 11:04:59 -0600 (Tue, 20 Nov 2007) $
 * 
 * TODO: check for the existence of the /lib/config.xml file
 * TODO: if the config.xml exists, check if the database connection works, and if we can get ANY version data: if so, give fatal message saying it won't work.
 * TODO: if step 2 is re-run, do something to reset the status/accessibility of the other steps.
 */


//create the setup object.
require_once(dirname(__FILE__) .'/../lib/simpletest/unit_tester.php');
require_once(dirname(__FILE__) .'/../lib/simpletest/reporter.php');

$configObj = new config(NULL, FALSE);
$configData = $configObj->read_config_file(FALSE);

if(strlen($configData['DATABASE__DBNAME'])) {
	//setup already complete.  Stop 'em.
	$page->set_message_wrapper(array(
		'title'		=> "Setup Unavailable",
		'message'	=> "It appears that CS-Project has already been installed.  Please use " .
					"the administrative section of the <a href=\"/content/settings\">Settings " .
					"Tab</a> to make changes to your configuration (<i><b>NOTE:</b> you must be " .
					"an admin to be able to see administrative options</i>).",
		'type'		=> "error"
	));
	$page->conditional_header('/login.php');
}
else {
	//setup hasn't run; we're good.
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
	$page->clear_content('infobar');
	
	
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
}

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



//=============================================================================
function reset_all_steps($leaveText=TRUE, $afterStep=NULL) {
	$retval=0;
	if(is_array($_SESSION['setup'])) {
		if(is_numeric($afterStep)) {
			$_SESSION['setup']['lastStep'] = $afterStep;
		}
		else {
			$_SESSION['setup']['lastStep'] = 1;
		}
		$unsetThis = array('data', 'result', 'accessible');
		
		if($leaveText !== TRUE) {
			$unsetThis[] = 'text';
		}
		foreach($unsetThis as $indexName) {
			if(isset($_SESSION['setup'][$indexName])) {
				if(is_numeric($afterStep)) {
					foreach($_SESSION['setup'][$indexName] as $stepNum=>$stepData) {
						if(is_numeric($stepNum) && $stepNum > $afterStep) {
							unset($_SESSION['setup'][$indexName][$stepNum]);
							$retval++;
						}
					}
				}
				else {
					unset($_SESSION['setup'][$indexName]);
					$retval++;
				}
			}
		}
	}
	else {
		throw new exception(__FUNCTION__ .": no step data found in session");
	}
	
	return($retval);
}//end reset_all_steps()
//=============================================================================



//=============================================================================
function test_db_stuff(cs_phpDB &$db=NULL) {
	if(is_null($db) || !is_object($db)) {
		$db = new cs_phpDB;
	}
	$stepOneData = get_setup_data(1);
	$params = get_db_params();
	$originalParams = $params;
	
	
	
	$params['dbname'] = 'template1';
	$retval = "Failed to connect to ". $params['host'] .":". $params['dbname'] ." (host connection failed)";
	
	$gf = new cs_globalFunctions;
	
	try {
		$db->connect($params);
		$result = "Connected successfully to ". $params['host'] .":". $params['dbname'] ." (host connection good)";
		try {
			$newParams = $originalParams;
			$db2 = new cs_phpDB;
			$db2->connect($originalParams );
			$retval = "Connected successfully to ". $newParams['host'] .":". $newParams['dbname'] ." (host connection good, DATABASE EXISTS)";
		}
		catch(exception $e) {
			//no database!
			//TODO: do a preg_match() on $e->getMessage() to see if it says something about the database not existing
			$retval = TRUE;
		}
	}
	catch(exception $e) {
		$retval = $e->getMessage();
	}
	
	return($retval);
}//end test_db_stuff()
//=============================================================================

?>