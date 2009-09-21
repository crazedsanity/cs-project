<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	$obj = new __finalStep($page, $stepNames);
	$writeConfigResult = $obj->write_config($page);
	
	$page->set_message_wrapper(array(
		'title'		=> "Setup Complete",
		'message'	=> $writeConfigResult,
		'type'		=> "status",
		'linkText'	=> "Proceed To Login",
		'linkURL'	=> '/setup/5?removeData=1'
	));
	$page->conditional_header("/setup/5", TRUE);
}
elseif($_GET['removeData'] == 1) {
	unset($_SESSION['setup']);
	$page->conditional_header('/login.php');
}
else {
	if(get_setup_data(5, 'result')) {
		$page->clear_content();
		
		$test = &new TestSuite("All Tests");
		$test->addTestFile(dirname(__FILE__) .'/../../lib/cs-content/tests/testOfCSContent.php');
		$test->addTestFile(dirname(__FILE__) .'/../../lib/cs-content/tests/testOfCSFileSystem.php');
		$test->addTestFile(dirname(__FILE__) .'/../../lib/cs-content/tests/testOfCSGlobalFunctions.php');
		$test->addTestFile(dirname(__FILE__) .'/../../lib/cs-content/tests/testOfCSVersionAbstract.php');
		$test->addTestFile(dirname(__FILE__) .'/../../lib/cs-phpxml/tests/testOfA2P.php');
		$test->addTestFile(dirname(__FILE__) .'/../../lib/cs-phpxml/tests/testOfCSPHPXML.php');
		$test->addTestFile(dirname(__FILE__) .'/../../lib/cs-webapplibs/tests/testOfCSWebAppLibs.php');
		$display = new HtmlReporter();
		$test->run($display);
		
		$page->gfObj->debug_print("Passes: (". $display->getPassCount() .")");
		$page->gfObj->debug_print("Fails: (". $display->getFailCount() .")");
		
		//log our result into the database.
		$db = new cs_phpDB;
		$db->connect(get_config_db_params());
		
		$log = new logsClass($db, 'SETUP');
		$log->log_by_class('UNIT TEST DATA::: passes='. $display->getPassCount() .', fails='. $display->getFailCount() .', exceptions='. $display->getExceptionCount(), 'Information');
		
		$title = "Setup Complete";
		$issueLink = '<a href="http://project.crazedsanity.com/extern/helpdesk/create?from='. $page->get_version() .
				'&version='. $page->get_version() .'&isdevsite='. constant('ISDEVSITE') .'CS-Project Helpdesk</a>';
		if($display->getPassCount() > 0) {
			$type = 'status';
			$message = "Setup has been completed successfully.  If you would like to remove setup data and proceed to login, click the link below.";
			if($display->getFailCount() > 0 && $display->getPassCount() > 0) {
				$type = 'warning';
				$message = "Setup completed, but there seem to be errors (see above).  You may be able to ignore them, especially " .
						"if you are running a test site.  Please report them at ". $issueLink;
			}
			elseif($display->getPassCount() == 0) {
				$title = "TOTAL FAILURE :(";
				$type = 'fatal';
				$message = "Setup finished, but it appears all the tests failed.  Your installation will likely be " .
						"highly unstable, if it is useable at all.  Please report the problem to ". $issueLink .", along with " .
						"information regarding your server's environment, database, the exception information, and anything " .
						"else that could help track the problem down.";
			}
		}
		else {
			
		}
		
		$page->set_message_wrapper(array(
			'title'		=> $title,
			'message'	=> $message,
			'type'		=> $type,
			'linkText'	=> "Proceed to Login",
			'linkURL'	=> "/setup/5?removeData=1"
		));
	}
}


?>
