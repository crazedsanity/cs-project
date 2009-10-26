<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	//check that step 1 was successful.
	reset_all_steps(FALSE, 2);
	$stepOneData = get_setup_data(1, 'data');
	if(get_setup_data(1, 'result')) {
		
		//first, check to see if we can connect to the host's "template1" database.
		$db = new cs_phpDB;
		
		store_setup_data(2, 0, 'result');
		$result = test_db_stuff($db);
		
		if($result === TRUE) {
			//Good to go: load the schema!
			
			//now create a temporary local class that does the rest.
			$obj = new __tmpSetupClass($db, $page);
			$finalResult = $obj->go();
			
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
			$params = get_setup_data(1, 'data');
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