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
 * TODO: check for the existence of the config file (CONFIG_FILE_LOCATION)
 * TODO: if the config.xml exists, check if the database connection works, and if we can get ANY version data: if so, give fatal message saying it won't work.
 * TODO: if step 2 is re-run, do something to reset the status/accessibility of the other steps.
 */


//create the setup object.
require_once(dirname(__FILE__) .'/../lib/simpletest/unit_tester.php');
require_once(dirname(__FILE__) .'/../lib/simpletest/reporter.php');
require_once(dirname(__FILE__) .'/../lib/setup.class.php');

$configObj = new config(NULL, FALSE);
$configData = $configObj->read_config_file(FALSE);

$setupObj = new setup($page);

if(strlen($configData['DATABASE__DBNAME'])) {
	//setup already complete.  Stop 'em.
	$page->set_message_wrapper(array(
		'title'		=> "Setup Unavailable",
		'message'	=> "It appears that CS-Project has already been installed.",
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
		if(!is_numeric($setupObj->get_setup_data($sectionArr[1], 'accessible'))) {
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
	
	
	$page->add_template_var("VERSION_STRING", $setupObj->read_version_file());
	$page->rip_all_block_rows('stepData');
	$page->clear_content('infobar');
	
	
	//determine the current step based on the URL ($sectionArr is provided by contentSystem).
	$currentStep = $sectionArr[1];
	
	
	$tmplStepTitle = "Main Setup Screen";
	foreach($stepNames as $num=>$name) {
		$stepResult = $setupObj->get_setup_data($num, 'result');
		if(!is_numeric($stepResult)) {
			$passFail = "Incomplete";
			$bgColor  = "yellow";
			if(strlen($setupObj->get_setup_data($num, 'text'))) {
				$stepText = $setupObj->get_setup_data($num, 'text');
			}
			else {
				$stepText = "Step incomplete...";
			}
		}
		else {
			$passFail = interpret_bool($stepResult, array('FAIL', 'Pass'));
			$bgColor = interpret_bool($stepResult, array('red', 'green'));
			
			if(strlen($setupObj->get_setup_data($num, 'text'))) {
				$stepText = $setupObj->get_setup_data($num, 'text');
			}
			else {
				$stepText = "&nbsp;";
			}
			
			$setupObj->store_setup_data($num, 1, 'accessible');
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

?>