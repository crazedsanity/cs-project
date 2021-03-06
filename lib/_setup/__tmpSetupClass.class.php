<?php
/*
 * Created on Aug 23, 2007
 * 
 * SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */



class __tmpSetupClass {
	
	
	private $db;
	private $fs;
	private $page;
	private $url = "/setup/1";
	
	//=========================================================================
	public function __construct(cs_phpDB &$db, cs_genericPage &$page) {
		$this->db = $db;
		$this->fsObj = new cs_fileSystem(dirname(__FILE__) .'/../../docs/sql/setup');
		$this->gfObj = new cs_globalFunctions;
		$this->page = $page;
		
		store_setup_data(2, 0, 'result');
		store_setup_data(2, 'Initializing...', 'text');
	}//end __construct()
	//=========================================================================
	
	
	//=========================================================================
	public function go() {
		$retval = "Nothing done... something went horribly wrong.";
		if($this->create_database()) {
			$retval = $this->handle_plpgsql();
			
			if($retval === TRUE) {
				$retval = $this->load_schema();
				if($retval === TRUE) {
					$this->page->set_message_wrapper(
						array(
							'title'		=> "Step Successful",
							'message'	=> "Finished step two with result:::<BR>\n". get_setup_data(2,'text'),
							'type'		=> "status"
						)
					);
					$this->page->conditional_header('/setup/3', TRUE);
				}
				else {
					$retval = "There was an error while testing PL/PGSQL functionality: ". $retval;
					store_setup_data(2, $retval, 'text');
				}
			}
		}
		else {
			store_setup_data(2, 0, 'result');
			store_setup_data(2, 'Failed to create database', 'text');
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
		$params = get_db_params();
		
		//okay, let's try to create the database.
		$numrows = $this->db->exec("CREATE DATABASE ". $params['dbname'] ." WITH ENCODING='SQL_ASCII'");
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror)) {
			$retval = FALSE;
		}
		else {
			$retval = TRUE;
			
			//okay.  Now destroy our database handle & create a new one, connected to the proper database.
			unset($this->db);
			$this->db = new cs_phpDb;
			$this->db->connect(get_db_params());
		}
		
		return($retval);
	}//end create_database()
	//=========================================================================
	
	
	//=========================================================================
	private function load_schema() {
		
		store_setup_data(2, "Schema not loaded... ", 'text');
		store_setup_data(2, 0, 'result');
		
		$fileData = $this->fsObj->read("01__storedprocs.sql");
		
		//now we'll try to push that into the database.
		$this->db->beginTrans();
		
		$this->gfObj->debug_print("Loading stored procedures... ");
		
		$this->db->exec($fileData);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror)) {
			$this->db->rollbackTrans();
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
				$this->db->rollbackTrans();
				$retval = $dberror;
			}
			else {
				$retval = "Done loading tables!!! Creating indexes and miscellaneous other things...";
				$this->gfObj->debug_print($retval);
				
				$fileData = $this->fsObj->read("03__indexes_etc.sql");
				$this->db->exec($fileData);
				$dberror = $this->db->errorMsg();
				
				if(strlen($dberror)) {
					$this->db->rollbackTrans();
					$retval = $dberror;
				}
				else {
					$retval = "All stored procedures, tables, and indexes have been created!";
					$this->gfObj->debug_print($retval);
					
					$this->db->commitTrans();
					store_setup_data(2, array(), 'data');
					store_setup_data(2, 1, 'result');
					store_setup_data(2, $retval, 'text');
					store_setup_data(3, 1, 'accessible');
					
					$retval = TRUE;
				}
			}
		}
		
		return($retval);
	}//end load_schema()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Try to load PL/pgsql functions...
	 * 
	 * NOTE: this is a terrible requirement, which requires PostgreSQL to be 
	 * compiled with certain options...
	 */
	private function handle_plpgsql() {
		$this->db->beginTrans();
		$fileData = $this->fsObj->read("plpgsql.sql");
		
		$numrows = $this->db->exec($fileData);
		$dberror = $this->db->errorMsg();
		
		if(!strlen($dberror)) {
			$this->db->commitTrans();
			$retval = TRUE;
		}
		else {
			//figure out WHY this failed: if they're already loaded it's okay, otherwise it's bad.
			$this->db->rollbackTrans();
			
			if(preg_match('/"plpgsql_call_handler" already exists/', $dberror)) {
				$retval = TRUE;
			}
			else {
				$retval = $dberror;
			}
		}
		
		return($retval);
	}//end handle_plpgsql()
	//=========================================================================
}

?>