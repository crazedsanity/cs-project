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
		$this->fsObj = new cs_fileSystemClass(dirname(__FILE__) .'/../../docs/sql/setup');
		$this->gfObj = new cs_globalFunctions;
		$this->page = $page;
	}//end __construct()
	//=========================================================================
	
	
	//=========================================================================
	public function go() {
		$retval = "Nothing done... something went horribly wrong.";
		if($this->create_database()) {
			$retval = $this->load_schema();
			
			if($retval === TRUE) {
				$this->page->set_message_wrapper(
					array(
						'title'		=> "Step Successful",
						'message'	=> "Finished step two with result:::<BR>\n". get_setup_data(2,'data'),
						'type'		=> "status"
					)
				);
				$this->page->conditional_header('/setup/3', TRUE);
			}
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
			
			//okay.  Now destroy our database handle & create a new one, connected to the proper database.
			unset($this->db);
			$this->db = new cs_phpDb;
			$this->db->connect($stepOneData);
		}
		
		return($retval);
	}//end create_database()
	//=========================================================================
	
	
	//=========================================================================
	private function load_schema() {
		
		store_setup_data(2, "Schema not loaded... ", 'data');
		store_setup_data(2, 0, 'result');
		
		$fileData = $this->fsObj->read("01__storedprocs.sql");
		
		//now we'll try to push that into the database.
		$this->db->beginTrans();
		
		$this->gfObj->debug_print("Loading stored procedures... ");
		
		$this->db->exec($fileData);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror)) {
			$retval = $dberror;
		}
		else {
			//keep going
			$retval = "Successfully loaded stored procedures!  Loading tables....";
			$this->gfObj->debug_print($retval);
			
			$fileData = $this->fsObj->read("02__tables.sql");
			$this->db->exec($fileData);
			$dberror = $this->db->errorMsg();
			
			if(strlen($dberror)) {
				$retval = $dberror;
			}
			else {
				$retval = "Done loading tables!!! Creating indexes and miscellaneous other things...";
				$this->gfObj->debug_print($retval);
				
				$fileData = $this->fsObj->read("03__indexes_etc.sql");
				$this->db->exec($fileData);
				$dberror = $this->db->errorMsg();
				
				if(strlen($dberror)) {
					$retval = $dberror;
				}
				else {
					$retval = "All stored procedures, tables, and indexes have been created!";
					$this->gfObj->debug_print($retval);
					
					store_setup_data(2, $retval, 'data');
					store_setup_data(2, 1, 'result');
					
					$retval = TRUE;
				}
			}
		}
		
		return($retval);
	}//end load_schema()
	//=========================================================================
}

?>