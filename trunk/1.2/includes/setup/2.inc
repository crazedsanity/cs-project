<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	//check that step 1 was successful.
	$setupObj->reset_all_steps(FALSE, 2);
	$stepOneData = $setupObj->get_setup_data(1, 'data');
	if($setupObj->get_setup_data(1, 'result')) {
		
		//first, check to see if we can connect to the host's "template1" database.
		$db = new cs_phpDB;
		
		$setupObj->store_setup_data(2, 0, 'result');
		$result = $setupObj->test_db_stuff($db);
		
		if($result === TRUE) {
			//Good to go: load the schema!
			
			$obj = new setup($page, $db);
			$finalResult = $obj->handle_step(2);
			
			$page->set_message_wrapper(
				array(
					'title'		=> "Internal Failure",
					'message'	=> "The setup object must have encountered a fatal error.<BR>\n". $finalResult,
					'type'		=> "error"
				)
			);
			$page->conditional_header('/setup/1', TRUE);
		}
		else {
			//not so good.  Go back to step 1, so they can make changes.
			$params = $setupObj->get_setup_data(1, 'data');
			$page->set_message_wrapper(
				array(
					'title'		=> "Test Failed",
					'message'	=> "Result of testing host ". $params['host'] .":<BR>\n" . $result,
					'type'		=> "error"
				)
			);
			$page->conditional_header('/setup/1', TRUE);
		}
	}
	else {
		throw new exception("Please enter data for step #1 before attempting to load schema!");
	}
}
else {
}




?>