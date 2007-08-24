<?php
/*
 * Created on May 11, 2007
 * 
 * SVN INFORMATION:::
 * SVN Signature: $Id$
 * Last Committted Date: $Date$
 * Last Committed Path: $HeadURL$
 */


class initialSetup {
	
	protected $gfObj;
	protected $fsObj;
	protected $versionFileVersion=NULL;
	protected $stepData = NULL;
	protected $userData=array();
	
	
	//=========================================================================
	protected function __construct() {
		
		$this->gfObj = new cs_globalFunctions;
		$this->fsObj = new cs_fileSystemClass();
		$this->read_version_file();
		$this->get_step_data();
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	private function read_version_file() {
		$retval = NULL;
		
		//okay, all files present: check the version in the VERSION file.
		$versionFileContents = $this->fsObj->read('VERSION');
		
		//okay, rip it into bits. NOTE: this *depends* on "VERSION: " being on the third line.
		$lines = explode("\n", $versionFileContents);
		$versionLine = $lines[2];
		if(preg_match('/^VERSION: /', $versionLine)) {
			
			$retval = trim(preg_replace('/VERSION: /', '', $versionLine));
			$this->versionFileVersion = $retval;
		}
		else {
			throw new exception(__METHOD__ .": could not find VERSION data");
		}
		
		return($retval);
	}//end read_version_file()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_version() {
		return($this->versionFileVersion);
	}//end get_version()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_step_data($stepName=NULL) {
		if(isset($_SESSION['storedStepData']) && is_array($_SESSION['storedStepData'])) {
			$this->stepData = $_SESSION['storedStepData'];
		}
		if(is_null($this->stepData) || !count($this->stepData)) {
			$this->stepData = array(
				"dbInfo"		=> array(
					'name'			=> "Database Information",
					'description'	=> "Setup initial values for database connectivity " .
						"(database name, hostname, port, username, password).  This " .
						"will be used later for creating the database, setting up users, " .
						"and setting some default values.",
					'isComplete'	=> FALSE,
					'requiredFields'=> array(
						'Database Name (cs_project)'	=> array(
							'name'		=> "db_name",
							'desc'		=> "Name of the database within PostgreSQL (i.e. \"cs_project\")",
							'default'	=> "cs_project"
						),
						'Hostname (localhost)'			=> array(
							'name'		=> "db_host",
							'desc'		=> "Fully qualified host name (like \"taz.google.com\" or \"localhost\").",
							'default'	=> "localhost"
						),
						'Port (5432)'					=> array(
							'name'		=> "db_port",
							'desc'		=> "Port to connect to PostgreSQL on (default is 5432).",
							'default'	=> "5432"
						),
						'Database Username (postgres)'	=> array(
							'name'		=> "db_user",
							'desc'		=> "Username for connecting to PostgreSQL (if you don't know, it's probably \"postgres\", " .
								"though connecting as a SUPERUSER is generally accepted as a BAD THING).",
							'default'	=> "postgres"
						),
						'Database Password'				=> array(
							'name'		=> "db_pass",
							'desc'		=> "Password for connecting to PostgreSQL (for a trusted connection, this can be blank).",
							'default'	=> ""
						)
					),
					'result'		=> NULL
				), 
				"createDb"		=> array(
					'name'			=> "Build Database",
					'description'	=> "A blank database will be created, and schema for " .
						"cs-project will be loaded.",
					'isComplete'	=> FALSE,
					'result'		=> NULL
				),
				"setDefaults"	=> array(
					'name'			=> "Set Database Values",
					'description'	=> "Required records are created, like the anonymous " .
						"user, and records required for logging",
					'isComplete'	=> FALSE,
					'result'		=> NULL
				), 
				"extraValues"	=> array(
					'name'			=> "Set Extra Final Values",
					'description'	=> "Various information can be set, including the " .
						"mailing list (email address) for announcing new helpdesk " .
						"issues, name of the project, password for the administrator, " .
						"and one user.",
					'isComplete'	=> FALSE,
					'requiredFields'=> array(
						'New Issue Announcement Address'	=> "helpdesk-issue-announce-email",
						'Project Name'						=> "proj__name",
						'Project URL (project.domain.com)'	=> "project_url",
						'Cookie Name (CS_PROJECT_SESSID)'	=> "config_session_name",
						'First Username'					=> "first_username",
						'Password For User'					=> "first_username__password",
						'Session: Max Idle Time (2 hours)'	=> "max_idle",
						'Session: Max Length (18 hours)'	=> "max_time"
					),
					'internalFields'=> array(
						'isdevsite'						=> 0,
						'debugprintopt'					=> 0,
						'debugremovehr'					=> 0,
						'stop_logins_on_global_alert'	=> 1
					),
					'result'		=> NULL
				),
				"writeConfig"	=> array(
					'name'			=> "Create Website Config File",
					'description'	=> "All information entered thusfar will be stored " .
						"in an XML file in the \"lib\" directory.  Filesystem permissions " .
						"issues will need to be tackled here.",
					'isComplete'	=> FALSE,
					'result'		=> NULL
				),
				"finalTests"	=> array(
					'name'			=> "Final Tests",
					'description'	=> "Useability tests are performed to attempt to " .
						"ensure that your installation works properly.  Any errors " .
						"encountered here need to be addressed immediately.",
					'isComplete'	=> FALSE,
					'result'		=> NULL
				)
			);
			
			$this->stepOrder = array_keys($this->stepData);
		}
		
		//did they request a specific part of the step data?
		if(is_null($stepName)) {
			$retval = $this->stepData;
		}
		elseif(isset($this->stepData[$stepName])) {
			$retval = $this->stepData[$stepName];
		}
		else {
			throw new exception(__METHOD__ .": invalid step requested (". $stepName .")");
		}
		
		return($retval);
	}//end get_step_data()
	//=========================================================================
	
	
	
	//=========================================================================
	public function process_step($stepName, array $data) {
		$this->gfObj->debug_print($data);
		$methodName = "setup__". $stepName;
		
		if(method_exists($this, $methodName) && $this->stepData[$stepName]) {
			$x = array_keys($this->stepData);
			$stepNum = array_search($stepName, $x);
			$retval = $this->$methodName($data['fields']);
			$_SESSION['step_results'][$stepName] = array(
				
			);
		}
		else {
			throw new exception(__METHOD__ .": method (". $methodName .") does not exist");
		}
	}//end process_step()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Set database connection information, and attempt a connection to the db.
	 * 
	 * TODO: should a pre-existing database be allowed (non-existent eliminates lots of problems).
	 */
	private function setup__dbInfo(array $data) {
		
		//format: ourName => indexForPhpDBConnect
		$requiredFields = array(
			'db_host'	=> "host",
			'db_name'	=> "dbname",
			'db_port'	=> "port",
			'db_user'	=> "user",
			'db_pass'	=> "password"
		);
		$connectionParams = array();
		foreach($requiredFields as $ourName => $phpDbName) {
			if(isset($data[$ourName])) {
				#define($field, $data[$field]);
				$connectionParams[$phpDbName] = $data[$ourName];
			}
			else {
				throw new exception(__METHOD__ .": required data (". $ourName .") missing");
			}
		}
		
		$phpDb = new phpDB;
		$phpDb->connect($connectionParams);
		if(!strlen($phpDb->errorMsg())) {
			//store our data.
			$this->userData['dbInfo'] = $connectionParams;
			$this->userData['dbInfo']['dbExists'] = TRUE;
			$retval = "Connection can be established, database already exists.";
		}
		else {
			//attempt to connect to "template1": if we can, then we'll set a flag so we know to create the database later.
			$myConParms = $connectionParams;
			$myConParms['dbname'] = "template1";
			$phpDb = new phpDB;
			$phpDb->connect($myConParms);
			
			if(!strlen($phpDb->errorMsg())) {
				$this->userData['dbInfo'] = $connectionParams;
				$this->userData['dbInfo']['dbExists'] = FALSE;
				$retval = "Connection can be established, database doesn't exist.";
			}
			else {
				$this->gfObj->debug_print($phpDb);
				#throw new exception(__METHOD__ .": failed to connect to the database");
				$retval = "Failed to connect to server: ". $phpDb->errorMsg();
			}
		}
		
		//store the setup information in the session.
		$_SESSION['stepData'] = $this->userData;
		
		return($retval);
	}//end setup_dbInfo()
	//=========================================================================
	
	
	
	//=========================================================================
	private function setup__createDb() {
	}//end setup__createDb()
	//=========================================================================
	
	
	
	//=========================================================================
	private function setup__setDefaults() {
	}//end setup__setDefaults()
	//=========================================================================
	
	
	
	//=========================================================================
	private function setup__extraValues() {
	}//end setup__extraValues()
	//=========================================================================
	
	
	
	//=========================================================================
	private function setup__writeConfig() {
	}//end setup__writeConfig()
	//=========================================================================
	
	
	
	//=========================================================================
	private function setup__finalTests() {
	}//end setup__finalTests()
	//=========================================================================
	
	
}
?>