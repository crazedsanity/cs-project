<?php
/*
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */


require_once(dirname(__FILE__) .'/../lib/simpletest/unit_tester.php');
require_once(dirname(__FILE__) .'/../lib/simpletest/reporter.php');


class setup {
	
	private $pageObj;
	private $dbObj;

	//=============================================================================
    function __construct(cs_genericPage $page, cs_phpDB $db=NULL) {
    	$this->pageObj = $page;
		
		//TODO: determine what step we're on (if > 1, can connect db)
		try {
			$connectionParams = $this->get_db_params();
			
			//TODO: make this a setup parameter (so we can integrate with mysql).
			$this->dbObj = new cs_phpDB('pgsql');
			$this->dbObj->connect($connectionParams);
		}
		catch(exception $e) {
		}
    }//end __construct()
	//=============================================================================
    
    
    
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
		$stepOneData = $this->get_setup_data(1);
		$params = $this->get_db_params();
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
	
	
	
	//=============================================================================
	public function handle_step($step, $extraData=NULL) {
		if(is_null($step) || !is_numeric($step)) {
			throw new exception(__METHOD__ .": invalid step (". $step .")");
		}
		else {
			switch($step) {
				
				//-------------------------------------------------------------
				case 1: {
					$obj = new __tmpSetupClass($this->dbObj, $this->pageObj);
					$retval = $obj->go();
					break;
				}//end case 1
				//-------------------------------------------------------------
				
				
				//-------------------------------------------------------------
				case 3: {
					$obj = new __setupDefaultValues();
					$retval = $obj->go();
					break;
				}//end case 3
				//-------------------------------------------------------------
				
				
				//-------------------------------------------------------------
				case 5: {
					$obj = new __finalStep($this->pageObj, $extraData);
					$retval = $obj->write_config($this->pageObj);
					break;
				}//end case 5
				//-------------------------------------------------------------
				
				
				//-------------------------------------------------------------
				default: {
					throw new exception(__METHOD__ .": invalid step (". $step .")");
				}//end case default
				//-------------------------------------------------------------
			}
		}
		
		return($retval);
	}//end handle_step()
	//=============================================================================
	
	
}//end setup{}




/* ######################################################################
 * ######################################################################
 * ######################################################################
 * 
 * HELPER CLASSES
 * 	(eventually, they should probably be consolidated into setup{}... or separated into other files...?)
 * 
 * ######################################################################
 * ######################################################################
 * ######################################################################
 */













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










/*
 * STEP 3
 */







class __setupDefaultValues extends upgrade {
	
	
	protected $db;
	private $gfObj;
	private $totalRecords=0;
	
	private $data=array();
	
	private $lastNumrows=NULL;
	private $lastDberror=NULL;
	
	//=========================================================================
	public function __construct() {
		$this->db = new cs_phpDB;
		$this->gfObj = new cs_globalFunctions;
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function go() {
		store_setup_data(3, 0, 'result');
		try {
			$params = get_db_params();
			$this->db->connect($params);
			$this->db->beginTrans(__METHOD__);
			$retval = "Connected successfully to the database.";
			
			//now that we've connected, start doing stuff.
			$retval = $this->set_version();
			$retval .= "<BR>\n". $this->create_log_categories_and_classes();
			$retval .= "<BR>\n". $this->create_record_type_data();
			$retval .= "<BR>\n". $this->create_attributes();
			$retval .= "<BR>\n". $this->create_anonymous_contact_data();
			$retval .= "<BR>\n". $this->create_status_records();
			$retval .= "<BR>\n". $this->create_tag_names();
			$retval .= "<BR>\n". $this->build_preferences();
			$retval .= "<BR>\n". $this->create_users();
			$retval .= "<BR>\n". $this->create_user_group_records();
						
			$commitRes = $this->db->commitTrans();
			if($commitRes == 1) {
				$retval .= "<BR>\n ----------- Created (". $this->totalRecords ."), result of commit: (". $commitRes .").";
				store_setup_data(3, 1, 'result');
				store_setup_data(4, 1, 'accessible');
			}
			else {
				$this->db->rollbackTrans();
				store_setup_data(3, 0, 'result');
				throw new exception(__METHOD__ .": failed to commit the transaction (". $commitRes .")");
			}
			
		}
		catch(exception $e) {
			//TODO: rollback the transaction
			$retval = "An error occurred: ". $e->getMessage();
		}
		
		return($retval);
		
	}//end go()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Set version information into the database for future upgradeability.
	 */
	private function set_version() {
		//get the version string.
		$fullVersionString = read_version_file();
		
		$suffixData = explode('-', $fullVersionString);
		if(count($suffixData) == 2 && preg_match('/\./', $suffixData[0]) && !preg_match('/\./', $suffixData[1])) {
			//there's a suffix, and it doesn't contain periods (i.e. "1.0.0-ALPHA1")
			$suffix = $suffixData[1];
		}
		elseif(count($suffixData) == 1) {
			//no suffix.
			$suffix = "";
		}
		else {
			//there's a dash in the name, but it's invalid or contains periods (i.e. "BETA-1.0.0" or "1.0.0-ALPHA1.0")
			throw new exception(__METHOD__ .": version string is invalid (". $fullVersionString ."), suffix contains dashes, or there is a prefix");
		}
		
		//remove the suffix & parse it.
		$versionString = $suffixData[0];
		$versionData = $this->parse_version_string($fullVersionString);
		$sqlData = $versionData;
		
		
		#$sqlData = array(
		#	'version_string'		=> $fullVersionString,
		#	'version_major'			=> $versionData[0],
		#	'version_minor'			=> $versionData[1],
		#	'version_maintenance'	=> $versionData[2],
		#	'version_suffix'		=> $suffix
		#);
		
		$retval = 0;
		foreach($sqlData as $name => $value) {
			$sql = "SELECT internal_data_set_value('". $name ."', '". $value ."')";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval++;
				$this->totalRecords++;
			}
			else {
				throw new exception(__METHOD__ .": failed to set (". $name .") as (". $value .")::: ". $dberror);
			}
		}
		
		if($retval == count($sqlData)) {
			//okay, the final test: run a query that straps everything together, to ensure it all has the same version.
			$sql = "SELECT internal_data_get_value('version_major') || '.' || internal_data_get_value('version_minor') " .
				" || '.' || internal_data_get_value('version_maintenance') as text, " .
				"internal_data_get_value('version_suffix') as version_suffix;";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$data = $this->db->farray();
				$dbVersionString = $data['text'];
				if(strlen($data['version_suffix'])) {
					$dbVersionString .= "-". $data['version_suffix'];
				}
				
				if($dbVersionString === $fullVersionString) {
					//okay, one final test: check that the "version_string" in the database matches ours.
					$sql = "SELECT internal_data_get_value('version_string')";
					$numrows = $this->db->exec($sql);
					$dberror = $this->db->errorMsg();
					
					if(!strlen($dberror) && $numrows == 1) {
						$data = $this->db->farray();
						$dbVersionString = $data[0];
						
						if($dbVersionString === $fullVersionString) {
							$this->data['version_string'] = $fullVersionString;
							$retval = "Successfully set version string";
						}
						else {
							throw new exception(__METHOD__ .": derived database version string (". $dbVersionString .") doesn't match our version (". $fullVersionString .")");
						}
					}
					else {
						throw new exception(__METHOD__ .": failed to retrieve full version_string from database::: ". $dberror ."<BR>\nSQL::: ". $sql);
					}
				}
				else {
					throw new exception(__METHOD__ .": derived database version string (". $dbVersionString .") doesn't match our version (". $fullVersionString .")");
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to retrieve derived database version string::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			//it's cryptic, but what should it really say???
			throw new exception(__METHOD__ .": internal error, checksum didn't match");
		}
		
		return($retval);
		
	}//end set_version()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_log_categories_and_classes() {
		
		$counter = 0;
		
		$classes = array(
			1	=> 'Error',
			2	=> 'Information',
			3	=> 'Create',
			4	=> 'Update',
			5	=> 'Delete',
			6	=> 'REPORT',
			7	=> 'DEBUG'
		);
		
		
		foreach($classes as $num=>$name) {
			$insertArr = array(
				'log_class_id'	=> $num,
				'name'			=> $name
			);
			$sql = "INSERT INTO log_class_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				//good.
				$counter++;
				$this->totalRecords++;
			}
			else {
				throw new exception(__METHOD__ .": failed to create class record for (". $name .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		
		//Reset sequence, so new records can be created.
		$sql = "SELECT setval('log_class_table_log_class_id_seq', (SELECT max(log_class_id) FROM log_class_table))";
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(!strlen($dberror) && $numrows == 1) {
			$categories = array(
				1	=> 'Database',
				2	=> 'Authentication',
				3	=> 'Users',
				4	=> 'General',
				5	=> 'Project',
				6	=> 'Helpdesk',
				7	=> 'Task',
				8	=> 'Tags',
				9	=> 'Estimates',
				10	=> 'Navigation',
				11	=> 'Preferences'
			);
			
			
			foreach($categories as $num=>$name) {
				$insertArr = array(
					'log_category_id'	=> $num,
					'name'				=> $name
				);
				$sql = "INSERT INTO log_category_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
				
				$numrows = $this->db->exec($sql);
				$dberror = $this->db->errorMsg();
				
				if(!strlen($dberror) && $numrows == 1) {
					//good.
					$counter++;
					$this->totalRecords++;
					$this->data['logcat__'. strtolower($name)] = $num;
				}
				else {
					throw new exception(__METHOD__ .": failed to create category record for (". $name .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
				}
			}
			
			//Reset sequence, so new records can be created.
			$sql = "SELECT setval('log_category_table_log_category_id_seq', (SELECT max(log_category_id) FROM log_category_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				
				//format (primary index is log_event_id): log_class_id, log_category_id, description
				$logEvents = array(
				// *  log_event_id | log_class_id | log_category_id |              description
					1	=> array(3,	5,	'Project: created record'),
					2	=> array(5,	5,	'Project: deleted record'),
					3	=> array(4,	5,	'Project: updated record'),
					4	=> array(1,	5,	'Project: ERROR'),
					5	=> array(3,	6,	'Helpdesk: Created record'),
					6	=> array(4,	6,	'Helpdesk: Updated record'),
					7	=> array(2,	6,	'Helpdesk: Information'),
					8	=> array(1,	6,	'Helpdesk: ERROR'),
					9	=> array(6,	6,	'Helpdesk: Report'),
					10	=> array(3,	7,	'Task: created record'),
					11	=> array(5,	7,	'Task: deleted record'),
					12	=> array(4,	7,	'Task: updated record'),
					13	=> array(1,	1,	'Database Error'),
					14	=> array(6,	5,	'Project: Activity Report'),
					15	=> array(6,	7,	'Task: Activity Report'),
					16	=> array(3,	2,	'User logged-in'),
					17	=> array(5,	2,	'User logged-out'),
					18	=> array(6,	2,	'Login/Logout Report'),
					19	=> array(3,	8,	'Tags: created record'),
					20	=> array(5,	8,	'Tags: deleted record'),
					21	=> array(4,	8,	'Tags: updated record'),
					22	=> array(6,	8,	'Tags: Activity Report'),
					23	=> array(1,	2,	'Authentication: ERROR'),
					24	=> array(2,	10,	'Navigation: Viewed page'),
					25	=> array(4,	9,	'Update: Estimates'),
					26	=> array(1,	9,	'Error: Estimates'),
					27	=> array(2,	5,	'Information: Project'),
					28	=> array(4,	3,	'Update: Users'),
					29	=> array(1,	7,	'Error: Task'),
					30	=> array(3,	3,	'Create: Users')
				);
				
				foreach($logEvents as $logEventId => $subArr) {
				 	$insertArr = array(
						'log_event_id'		=> $logEventId,
						'log_class_id'		=> $subArr[0],
						'log_category_id'	=> $subArr[1],
						'description'		=> $subArr[2]
					);
					$sql = "INSERT INTO log_event_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
					$numrows = $this->db->exec($sql);
					$dberror = $this->db->errorMsg();
					
					if(!strlen($dberror) && $numrows == 1) {
						$counter++;
						$this->totalRecords++;
					}
					else {
						throw new exception(__METHOD__ .": failed to create log_event_id #". $insertArr['log_event_id'] 
						.", description: ". $insertArr['description'] ."<BR>\nERROR::: ". $dberror ."<BR>\nSQL::: ". $sql);
					}
				}
				
				//FINAL SANITY CHECKS!!!
				if($counter == (count($classes) + count($categories) + count($logEvents))) {
					
					//reset the sequence.
					$sql = "SELECT setval('log_event_table_log_event_id_seq', (SELECT max(log_event_id) FROM log_event_table))";
					if($this->run_sql($sql) === TRUE) {
						$retval = "Successfully created all category, class, and log event records (". $counter .")";
					}
					else {
						throw new exception(__METHOD__ .": failed to reset sequence for log_event table::: ". $dberror ."<BR>\nSQL::: ". $sql);
					}
				}
				else {
					throw new exception(__METHOD__ .": Internal error, failed to create all category and class records");
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to reset sequence for log_category_table::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to reset sequence for log_class_table::: ". $dberror ."<BR>\nSQL::: ". $sql);
		}
		
		return($retval);
		
	}//end create_log_categories_and_classes()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_record_type_data() {
		//format:
		//	{record_type_id} => array({name}, {module})
		$recordTypes = array(
			1	=> array('Project',		'project'),
			2	=> array('Task',		'task'),
			3	=> array('Helpdesk',	'helpdesk')
		);
		
		$retval = 0;
		foreach($recordTypes as $recTypeId => $subData) {
			$insertArr = array(
				'record_type_id'	=> $recTypeId,
				'name'				=> $subData[0],
				'module'			=> $subData[1]
			);
			$sql = "INSERT INTO record_type_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$this->totalRecords++;
				$retval++;
				$this->data['rectype__'. $insertArr['module']] = $recTypeId;
			}
			else {
				throw new exception(__METHOD__ .": failed to create record for ". $insertArr['name'] 
					.", dberror::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if(count($recordTypes) == $retval) {
			
			$sql = "SELECT setval('record_type_table_record_type_id_seq'::text, (SELECT max(record_type_id) FROM record_type_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				if($retval == count($recordTypes)) {
					$retval = "Created all record types (". $retval .")";
				}
				else {
					throw new exception(__METHOD__ .": internal error (sanity check failed)");
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to reset sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error");
		}
		
		return($retval);
	}//end create_record_type_data()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_attributes() {
		$attributes = array(
			3	=> array('phone',		'phone',		'Phone'),
			4	=> array('fax',			'phone',		'Fax'),
			5	=> array('cell',		'phone',		'Cell'),
			6	=> array('im_yahoo',	'alphanumeric',	'IM: Yahoo'),
			7	=> array('im_skype',	'alphanumeric',	'IM: Skype'),
			8	=> array('im_aol',		'alphanumeric',	'IM: AOL'),
			9	=> array('im_msn',		'alphanumeric',	'IM: MSN'),
			10	=> array('im_icq',		'alphanumeric',	'IM: ICQ'),
			11	=> array('address',		'sql',			'Address'),
			12	=> array('city',		'alphanumeric',	'City'),
			13	=> array('state',		'alphanumeric',	'State'),
			14	=> array('zip',			'alphanumeric',	'Zip')
		);
		
		$retval = 0;
		foreach($attributes as $attributeId => $subData) {
			$insertArr = array(
				'attribute_id'	=> $attributeId,
				'name'			=> $subData[0],
				'clean_as'		=> $subData[1],
				'display_name'	=> $subData[2]
			);
			$sql = "INSERT INTO attribute_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval++;
				$this->totalRecords++;
			}
			else {
				throw new exception(__METHOD__ .": failed to insert data for attribute (". $insertArr['name'] .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if($retval == count($attributes)) {
			$sql = "SELECT setval('attribute_table_attribute_id_seq'::text, (SELECT max(attribute_id) FROM attribute_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval = "Successfully created all attributes (". $retval .")!";
			}
			else {
				throw new exception(__METHOD__ .": failed to reset sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error (sanity check failed)");
		}
		
		return($retval);
	}//end create_attributes()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Create the anonymous user.  This includes their contact data and the 
	 * default group.
	 * 
	 * TODO: change "short_name" into "display_name".
	 */
	private function create_anonymous_contact_data() {
		$allRecords = array(
			'disabled group'	=> array(
				'name' => 'group_table',
				'data' => array(
					'group_id'		=> 0,
					'name'			=> 'disabled',
					'short_name'	=> 'DISABLED',
					'leader_uid'	=> 0
				),
			),
			'anonymous record in user table' => array(
				'name' => 'user_table',
				'data' => array(
					'uid'			=> 0,
					'username'		=> 'Anonymous',
					'password'		=> 'disabled',		//this is PURPOSELY an invalid password (passwords should be 32-char md5's
					'is_admin'		=> 'f',
					'is_active'		=> 'f',
					'group_id'		=> 0,
					'contact_id'	=> 0
				),
			),
			'default group' => array(
				'name' => 'group_table',
				'data' => array(
					'group_id'		=> 1,
					'name'			=> 'default',
					'short_name'	=> '-DEFAULT-',
					'leader_uid'	=> 0
				),
			),
			'anonymous contact record' => array(
				'name' => 'contact_table',
				'data' => array(
					'contact_id'		=> 0,
					'company'			=> '',
					'fname'				=> 'Anonymous',
					'lname'				=> '',
					'contact_email_id'	=> '-1'
				)
			),
			'anonymous email record' => array(
				'name'	=> 'contact_email_table',
				'data'	=> array(
					'contact_id'	=> 0,
					'email'			=> 'anonymous@null.com'
				)
			)
		);
		
		$retval = 0;
		foreach($allRecords as $operationName => $subData) {
			$tableName = $subData['name'];
			$insertArr = $subData['data'];
			
			$sql = "INSERT INTO ". $tableName ." ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$this->totalRecords++;
				$retval++;
			} else {
				throw new exception(__METHOD__. ": failed perform operation (". $operationName .") for table (". $tableName .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		$sql = "SELECT currval('contact_email_table_contact_email_id_seq'::text)";
		if($this->run_sql($sql)) {
			$data = $this->db->farray();
			$sql = "UPDATE contact_table SET contact_email_id=". $data[0] ." WHERE contact_id=0";
			if($this->run_sql($sql)) {
				$this->totalRecords++;
			}
			else {
				throw new exception(__METHOD__ .": failed to set contact_email_id for anonymous");
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to get sequence for contact_email_table");
		}
		
		if($retval == count($allRecords)) {
			//reset the sequence for the group table...
			$sql = "SELECT setval('group_table_group_id_seq'::text, (SELECT max(group_id) FROM group_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				//good to go.
				$retval = "Successfully created anonymous user, anonymous contact record, and the two default groups (". $retval .")!";
			}
			else {
				throw new exception(__METHOD__ .": failed to reset group sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error (failed sanity check)");
		}
		
		return($retval);
	}//end create_anonymous_contact_data()
	//=========================================================================
	
	
	//=========================================================================
	private function create_status_records() {
		//format: {status_id}	=> array({name}, {description})
		$statuses = array(
			0	=> array('New/Offered',			'New record'),
			1	=> array('Pending',				'Not new: pending review, or nearly complete.'),
			2	=> array('Running/Accepted',	'Work is underway'),
			3	=> array('Stalled',				'Unable to complete, or dependent on other things.'),
			4	=> array('Ended/Solved',		'Work is complete!'),
			5	=> array('Rejected',			'Denied.'),
			6	=> array('Re-opened',			'Was solved, but is once again open.'),
		);
		
		$retval = 0;
		foreach($statuses as $statusId => $subData) {
			$insertArr = array(
				'status_id'		=> $statusId,
				'name'			=> $subData[0],
				'description'	=> $subData[1]
			);
			$sql = "INSERT INTO status_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval++;
				$this->totalRecords++;
			}
			else {
				throw new exception(__METHOD__ .": failed to create status (". $insertArr['name'] .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if($retval == count($statuses)) {
			//reset the status_table.status_id sequence...
			$sql = "SELECT setval('status_table_status_id_seq'::text, (SELECT max(status_id) FROM status_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				//good to go.
				$retval = "Successfully created all status records (". $retval .")!";
			}
			else {
				throw new exception(__METHOD__ .": failed to reset status sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error (failed sanity check)");
		}
		
		return($retval);
		
	}//end create_status_records()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_tag_names() {
		$tags = array(
			1	=> 'bug',
			2	=> 'feature request',
			3	=> 'information',
			4	=> 'network related',
			5	=> 'critical',
			6	=> 'exception'
		);
		
		$retval = 0;
		foreach($tags as $id=>$name) {
			$insertArr = array(
				'tag_name_id'	=> $id,
				'name'			=> $name
			);
			$sql = "INSERT INTO tag_name_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval++;
				$this->totalRecords++;
			}
			else {
				throw new exception(__METHOD__ .": failed to insert data for (". $name .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if($retval == count($tags)) {
			//reset sequence for tag_name_table.tag_name_id
			$sql = "SELECT setval('tag_name_table_tag_name_id_seq'::text, (SELECT max(tag_name_id) FROM tag_name_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				//good to go.
				$retval = "Successfully created all tags (". $retval .")!";
			}
			else {
				throw new exception(__METHOD__ .": failed to reset tag_name sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error (sanity check failed)");
		}
		
		return($retval);
	}//end create_tag_names()
	//=========================================================================
	
	
	
	//=========================================================================
	private function build_preferences() {
		//format: {pref_type_id} => array({name}, {default_value}, {display_name}, {description})
		$prefTypes = array(
			1	=> array('startModule', 'helpdesk',	'Starting on Module',	'Defines which section will be loaded upon login if nothing was selected.'),
			5	=> array('sorting_helpdesk', 'public_id|DESC', 'Helpdesk Sorting', 'Define the type of sorting for the helpdesk page.'),
			6	=> array('sorting_project', 'priority|ASC', 'Project Sorting', 'Define the type of sorting for the helpdesk page.'),
			7	=> array('projectDetails_taskDisplayOnlyMine', 'all', 'Project Details: Task display', 'Define what tasks are displayed on a project\'s details page.'),
			8	=> array('projectDetails_showCompletedIssues', '1', 'Project Details: Display completed issues', 'Should completed issues display in the details of a project?')
		);
		
		$retval = 0;
		foreach($prefTypes as $prefTypeId => $subData) {
			$insertArr = array(
				'pref_type_id'	=> $prefTypeId,
				'name'			=> $subData[0],
				'default_value'	=> $subData[1],
				'display_name'	=> $subData[2],
				'description'	=> $subData[3]
			);
			
			$sql = "INSERT INTO pref_type_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval++;
				$this->totalRecords++;
			}
			else {
				throw new exception(__METHOD__ .": failed to insert data for (". $insertArr['name'] .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if($retval == count($prefTypes)) {
			//format: {pref_option_id}	=> array({pref_type_id}, {name}, {effective_value})
			$prefOptions = array(
				1	=>array(1,	'Helpdesk',							'helpdesk'),
				2	=>array(1,	'Projects',							'project'),
				3	=>array(1,	'Summary ',							'summary'),
				8	=>array(5,	'ID - Descending',					'public_id|DESC'),
				9	=>array(5,	'ID - Ascending',					'public_id|ASC'),
				10	=>array(5,	'Priority - Descending',			'priority|DESC'),
				11	=>array(5,	'Priority - Ascending',				'priority|ASC'),
				12	=>array(5,	'Submit - Descending',				'start_date|DESC'),
				13	=>array(5,	'Submit - Ascending',				'start_date|ASC'),
				14	=>array(5,	'Title - Descending',				'name|DESC'),
				15	=>array(5,	'Title - Ascending',				'name|DESC'),
				16	=>array(5,	'Status ID - Descending',			'status_id|DESC'),
				17	=>array(5,	'Status ID - Ascending',			'status_id|DESC'),
				18	=>array(5,	'Assigned - Descending',			'assigned|DESC'),
				19	=>array(5,	'Assigned - Ascending',				'assigned|DESC'),
				20	=>array(6,	'Priority - Ascending',				'priority|ASC'),
				21	=>array(6,	'Priority - Descending',			'priority|DESC'),
				22	=>array(6,	'Name of Project - Ascending',		'name|ASC'),
				23	=>array(6,	'Name of Project - Descending',		'name|DESC'),
				24	=>array(6,	'Begin - Ascending',				'start_date|ASC'),
				25	=>array(6,	'Begin - Descending',				'start_date|DESC'),
				26	=>array(6,	'End - Ascending',					'end|ASC'),
				27	=>array(6,	'End - Descending',					'end|DESC'),
				28	=>array(6,	'Status ID - Ascending',			'status_id|ASC'),
				29	=>array(6,	'Status ID - Descending',			'status_id|DESC'),
				30	=>array(6,	'Progress - Ascending',				'progress|ASC'),
				31	=>array(6,	'Progress - Descending',			'progress|DESC'),
				32	=>array(6,	'Leader - Ascending',				'leader_contact_id|ASC'),
				33	=>array(6,	'Leader - Descending',				'leader_contact_id|DESC'),
				34	=>array(7,	'Show everything',					'all'),
				35	=>array(7,	'All of mine (created & assigned)',	'mine'),
				36	=>array(7,	'Only my assigned items',			'assigned'),
				37	=>array(8,	'Yes',								'1'),
				38	=>array(8,	'No',								'0'),
			);
			
			foreach($prefOptions as $prefOptionId => $subData) {
				$insertArr = array(
					'pref_option_id'	=> $prefOptionId,
					'pref_type_id'		=> $subData[0],
					'name'				=> $subData[1],
					'effective_value'	=> $subData[2]
				);
				$sql = "INSERT INTO pref_option_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
				$numrows = $this->db->exec($sql);
				$dberror = $this->db->errorMsg();
				
				if(!strlen($dberror) && $numrows == 1) {
					$retval++;
					$this->totalRecords++;
				}
				else {
					throw new exception(__METHOD__ .": failed to insert data for pref option (". $insertArr['name'] .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
				}
			}
			
			if($retval == (count($prefTypes) + count($prefOptions))) {
				//reset the pref_type_table.pref_type_id sequence.
				$sql = "SELECT setval('pref_type_table_pref_type_id_seq'::text, (SELECT max(pref_type_id) FROM pref_type_table))";
				$numrows = $this->db->exec($sql);
				$dberror = $this->db->errorMsg();
				
				if(!strlen($dberror) && $numrows == 1) {
					if($retval == (count($prefTypes) + count($prefOptions))) {
						//reset the pref_option_table.pref_option_id sequence.
						$sql = "SELECT setval('pref_option_table_pref_option_id_seq'::text, (SELECT max(pref_option_id) FROM pref_option_table))";
						$numrows = $this->db->exec($sql);
						$dberror = $this->db->errorMsg();
						
						if(!strlen($dberror) && $numrows == 1) {
							$retval = "Successfully created all preferences and options (". $retval .")!";
						}
						else {
							throw new exception(__METHOD__ .": failed to reset the pref_option sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
						}
					}
					else {
						throw new exception(__METHOD__ .": internal error (sanity check #2 failed)");
					}
				}
				else {
					throw new exception(__METHOD__ .": failed to reset pref_type sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
				}
			}
			else {
				throw new exception(__METHOD__ .": internal error (sanity check #2 failed): (". $retval ." != ". (count($prefTypes) + count($prefOptions)) .")");
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error (sanity check #1 failed)");
		}
		
		return($retval);
		
	}//end build_preferences()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_users() {
		
		//retrieve the user information.
		$userData = get_setup_data(3, 'post_info');
		if(!is_array($userData) || count($userData) != 2) {
			throw new exception(__METHOD__ .": no user data...?". $this->gfObj->debug_print($userData,0));
		}
		
		$counter = 0;
		$retval = "Successfully created records for::: ";
		foreach($userData as $num => $subData) {
			
			//split their name up, based upon spaces.
			$nameData = explode(' ', $subData['name']);
			$insertArr = array();
			if(count($nameData) == 1) {
				$insertArr['fname'] = $nameData[0];
				$insertArr['lname'] = "";
			}
			elseif(count($nameData) == 2) {
				$insertArr['fname'] = $nameData[0];
				$insertArr['lname'] = $nameData[1];
			}
			else {
				$insertArr['fname'] = $nameData[0];
				$insertArr['lname'] = preg_replace('/^'. $insertArr['fname'] .' /', '', $subData['name']);
			}
			$insertArr['contact_email_id'] = '-1';
			
			//create their contact record.
			$sql = "INSERT INTO contact_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$sql = "SELECT currval('contact_table_contact_id_seq'::text)";
				if($this->run_sql($sql, 1) === TRUE) {
					$this->totalRecords++;
					
					$data = $this->db->farray();
					$contactId = $data[0];
					
					//now create the user.
					$xUser = $subData['username'];
					$insertArr = array(
						'username'		=> $subData['username'],
						'password'		=> md5($subData['password'] .'_'. $contactId),
						'is_admin'		=> interpret_bool($subData['is_admin'], array('f', 't')),
						'contact_id'	=> $contactId
					);
					$sql = "INSERT INTO user_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
					
					if($this->run_sql($sql, 1) === TRUE) {
						$sql = "SELECT currval('user_table_uid_seq'::text)";
						if($this->run_sql($sql,1) === TRUE) {
							$data = $this->db->farray();
							$lastUid = $data[0];
							
							//create an email address for them.
							$sql = "INSERT INTO contact_email_table (contact_id, email) VALUES (". $contactId .", '". strtolower($subData['email']) ."')";
							if($this->run_sql($sql,1) === TRUE) {
								$counter++;
								//get the newly inserted id, so we can update the contact table.
								if($this->run_sql("SELECT currval('contact_email_table_contact_email_id_seq'::text)")) {
									$data = $this->db->farray();
									$contactEmailId = $data[0];
									
									$sql = "UPDATE contact_table SET contact_email_id=". $contactEmailId ." WHERE contact_id=". $contactId;
									if($this->run_sql($sql)) {
										$retval = $this->gfObj->create_list($retval, " --- Created record for ". $subData['username'] ." (". $lastUid .")", "<BR>\n");
									}
									else {
										throw new exception(__METHOD__ .": unable to update contact table with new contact_email_id...");
									}
								}
								else {
									throw new exception(__METHOD__ .": failed to retrieve contact_email_id");
								}
							}
							else {
								throw new exception(__METHOD__ .": failed to create email for ". $subData['username'] ."::: ". $dberror ."<BR>\nSQL::: ". $sql);
							}
						}
						else {
							throw new exception(__METHOD__ .": failed to retrieve uid for ". $subData['username'] ."::: ". $dberror ."<BR>\nSQL::: ". $sql);
						}
					}
					else {
						throw new exception(__METHOD__ .": failed to create user record for ". $subData['username'] ."::: ". $dberror ."<BR>\nSQL::: ". $sql);
					}
				}
				else {
					throw new exception(__METHOD__ .": unable to retrieve contact_id for ". $subData['username'] ."::: ". $dberror ."<BR>\nSQL::: ". $sql);
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to create contact record for ". $subData['username'] ."::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if($counter == 2) {
			$retval = $this->gfObj->create_list($retval, " --->>> done, created  (". $counter .") records", "<BR>\n");
		}
		else {
			throw new exception(__METHOD__ .": failed to create users (". $counter .")::: ". $this->gfObj->debug_print($userData,0));
		}
		
		return($retval);
	}//end create_users()
	//=========================================================================
	
	
	
	//=========================================================================
	public function finish(cs_genericPage &$page) {
		
		$stepRes = get_setup_data(3, 'result');
		if($stepRes == 1) {
			$page->set_message_wrapper(array(
				'title'		=> "Successfully Setup Data",
				'message'	=> "All default data was stored in the new database successfully!",
				'type'		=> "status"
			));
			store_setup_data(3, $this->data, 'data');
			$page->conditional_header("/setup/4", TRUE);
		}
		else {
			$page->set_message_wrapper(array(
				'title'		=> "Step Failed",
				'message'	=> "Please review the errors below and proceed accordingly.",
				'type'		=> "error"
			));
			$page->conditional_header("/setup/3", TRUE);
		}
		
	}//end finish()
	//=========================================================================
	
	
	
	//=========================================================================
	protected function run_sql($sql, $expectedNumrows=NULL) {
		if(strlen($sql)) {
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			$this->lastNumrows = $numrows;
			$this->lastDberror = $dberror;
			
			if(!strlen($dberror) && $numrows >= 0) {
				if(is_numeric($expectedNumrows)) {
					if($expectedNumrows == $numrows) {
						$retval = TRUE;
					}
					else {
						$retval = FALSE;
					}
				}
				else {
					//don't care if it's numeric.
					$retval = TRUE;
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to run SQL, got numrows=(". $numrows ."), dberror::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": no SQL to run...");
		}
		
		return($retval);
	}//end run_sql()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_user_group_records() {
		$sql = "INSERT INTO user_group_table (uid, group_id) SELECT uid, group_id FROM user_table";
		if($this->run_sql($sql, 3) === TRUE) {
			$retval = "Successfully created user_group linkage.";
		}
		else {
			throw new exception(__METHOD__ .": unable to create user group records::: ". $this->lastDberror ."<BR>\nSQL::: ". $sql);
		}
		return($retval);
	}//end create_user_group_records()
	//=========================================================================
	
	
}//end __setupDefaultValues{}



class _setupUpgrade extends upgrade {
	
	public function __construct(cs_phpDB $db) {
		$this->db = $db;
		parent::__construct();
	}//end __construct()
	
	public function finalize_conversion() {
		$myVersion = parent::read_version_file();
		$setDataResult = parent::run_sql("SELECT internal_data_set_value('converted_from_version', '". parent::read_version_file() ."')");
		parent::update_num_users_to_convert();
		$retval = parent::update_database_version($myVersion);
		debug_print(__METHOD__ .": myVersion=(". $myVersion ."), setDataResult=(". $setDataResult ."), retval=(". $retval .")");
		
		return($myVersion);
	}//end finalize_conversion()
}//end _setupUpgrade{}









/*
 * STEP 5
 */

class __finalStep {
	
	
	private $page;
	private $gfObj;
	
	
	//=========================================================================
	public function __construct(cs_genericPage $page, array $stepData) {
		$this->page = $page;
		$this->stepData = $stepData;
		unset($this->stepData[5]);
		
		$this->gfObj = new cs_globalFunctions;
		$this->fsObj = new cs_fileSystemClass(dirname(__FILE__) ."/../../". CONFIG_DIRECTORY);
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	function write_config() {
		if($this->fsObj->is_writable(NULL)) {
			$lsData = $this->fsObj->ls();
			if(!is_array($lsData[CONFIG_FILENAME])) {
				$myData = array();
				foreach($this->stepData as $stepNum=>$garbage) {
					$tempStepData = get_setup_data($stepNum, 'data');
					if(is_array($tempStepData)) {
						$myData = array_merge($tempStepData, $myData);
					}
					else {
						throw new exception(__METHOD__ .": step #". $stepNum ." has no valid data... ". $this->gfObj->debug_print($tempStepData,0));
					}
				}
				
				//now that we've built the array successfully, now let's turn it into XML.
				$xmlCreator = new xmlCreator('config');
				foreach($myData as $index=>$value) {
					$xmlCreator->add_tag($index, $value);
				}
				$extraAttributes = array(
					'generated'		=> date('Y-m-d H:m:s'),
					'version'		=> $myData['version_string']
				);
				$xmlCreator->add_attribute('/config', $extraAttributes);
				
				//now, create an XML string...
				$xmlString = $xmlCreator->create_xml_string();
				
				$this->fsObj->create_file(CONFIG_FILENAME, TRUE);
				$writeRes = $this->fsObj->write($xmlString, CONFIG_FILENAME);
				
				if($writeRes > 0) {
					$retval = "Successfully created the XML config file";
					store_setup_data(5, 1, 'result');
					store_setup_data(5, $retval, 'text');
				}
				else {
					throw new exception(__METHOD__ .": failed to write any data to the config file");
				}
			}
			else {
				throw new exception(__METHOD__ .": ". CONFIG_FILE_LOCATION ." already exists!");
			}
		}
		else {
			throw new exception(__METHOD__ .": the config directory is not writable!");
		}
		
		$configObj = new config(CONFIG_FILE_LOCATION);
		$configObj->remove_setup_config();
		
		return($retval);
	}//end write_config()
	//=========================================================================
}


//#######################################################################################
/**
 * Built to avoid always printing-out the results (so we can retrieve result data separately.
 */
class MyDisplay extends SimpleReporter {
    
    function paintHeader($test_name) {
    }
    
    function paintFooter($test_name) {
    }
    
    function paintStart($test_name, $size) {
        parent::paintStart($test_name, $size);
    }
    
    function paintEnd($test_name, $size) {
        parent::paintEnd($test_name, $size);
    }
    
    function paintPass($message) {
        parent::paintPass($message);
    }
    
    function paintFail($message) {
        parent::paintFail($message);
    }
}
//#######################################################################################


class unitTest extends UnitTestCase {
	
	function testOfTester () {
		$this->assertTrue(FALSE);
	}
}


?>