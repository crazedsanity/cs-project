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
		
		//log our result into the database.
		$db = new cs_phpDB;
		$db->connect(get_config_db_params());
		
		$log = new logsClass($db, 'SETUP');
		$log->log_by_class('UNIT TEST DATA::: passes='. $display->getPassCount() .', fails='. $display->getFailCount() .', exceptions='. $display->getExceptionCount(), 'Information');
		
		if(!is_array($_SESSION['message'])) {
			$page->set_message_wrapper(array(
				'title'		=> "Setup Complete",
				'message'	=> "Setup has been completed successfully.  If you would like to remove setup data and proceed to login, click the link below.",
				'type'		=> 'status',
				'linkText'	=> "Proceed to Login",
				'linkURL'	=> "/setup/5?removeData=1"
			));
		}
	}
}


?>
