<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	#$obj = new __finalStep($page, $stepNames);
	#$writeConfigResult = $obj->write_config($page);
	$obj = new setup($page);
	$writeConfigResult = $obj->handle_step(5, $stepNames);
	
	$page->set_message_wrapper(array(
		'title'		=> "Reloaded... ",
		'message'	=> $writeConfigResult,
		'type'		=> "status",
		'linkURL'	=> '/login'
	));
	$page->conditional_header("/setup/5", TRUE);
}
else {
	if(get_setup_data(5, 'result')) {
		$page->clear_content();
		#$page->set_block_row('content', 'submitButton');
		
		$test = &new TestSuite("All Tests");
		$test->addTestFile(dirname(__FILE__) .'/../../lib/_unitTests_/cs-content_tests.php');
		#$test->addTestFile(dirname(__FILE__) .'/../../lib/_unitTests_/cs-arrayToPath_tests.php');
		#$test->addTestFile(dirname(__FILE__) .'/../../lib/_unitTests_/cs-phpxml_tests.php');
		$display = new HtmlReporter();
		$test->run($display);
		
		$page->gfObj->debug_print("Passes: (". $display->getPassCount() .")");
	}
	else {
		
	}
}


?>
