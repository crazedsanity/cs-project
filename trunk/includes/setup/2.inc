<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	//check that step 1 was successful.
	$stepOneData = get_setup_data(1, 'data');
	if(get_setup_data(1, 'result')) {
		
		//first, check to see if we can connect to the host's "template1" database.
		$db = new cs_phpDB;
		
		$result = test_db_stuff($db);
		
		if($result === TRUE) {
			//Good to go: load the schema!
			$page->gfObj->debug_print($result);
			
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
			$page->set_message_wrapper(
				array(
					'title'		=> "Connection to Host Failed",
					'message'	=> "Failed to connect to the 'template1' database of host ". $params['host'] .":<BR>\n" . $result,
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

//=============================================================================
function test_db_stuff(cs_phpDB &$db) {
	$retval = FALSE;
	
	$params = get_setup_data(1);
	$params['dbname'] = 'template1';
	
	try {
		$db->connect($params);
		try {
			$db2 = new cs_phpDB;
			$db2->connect(get_setup_data(1));
			$retval = FALSE;
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




class __tmpSetupClass {
	
	
	private $db;
	private $fs;
	private $page;
	private $url = "/setup/1";
	
	//=========================================================================
	public function __construct(cs_phpDB &$db, cs_genericPage &$page) {
		$this->db = $db;
		$this->fs = new cs_fileSystemClass();
		$this->page = $page;
	}//end __construct()
	//=========================================================================
	
	
	//=========================================================================
	public function go() {
		$retval = "Nothing done... something went horribly wrong.";
		if($this->create_database()) {
			//okay, keep going.
			$this->load_schema();
		}
		else {
			$setupData = get_setup_data(1, 'data');
			$retval = "Unable to create database... check that ". $setupData['host'] .
				" does not already have a database named '". $setupData['dbname'] ."'.  " .
				"Also, make sure no other user is connected to template1.";
		}
		
		return($retval);
	}//end go()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_database() {
		$stepOneData = get_setup_data(1, 'data');	
		//okay, let's try to create the database.
		$numrows = $this->db->exec("CREATE DATABASE ". $stepOneData['dbname']);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror)) {
			$retval = FALSE;
		}
		else {
			$retval = TRUE;
		}
		
		return($retval);
	}//end create_database()
	//=========================================================================
	
	
	//=========================================================================
	private function load_schema() {
	}//end load_schema()
	//=========================================================================
}

?>