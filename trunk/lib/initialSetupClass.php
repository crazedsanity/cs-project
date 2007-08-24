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
	
	private $gfObj;
	private $fsObj;
	private $versionFileVersion=NULL;
	private $stepData = NULL;
	
	
	//=========================================================================
	public function __construct() {
		
		$this->gfObj = new cs_globalFunctions;
		$this->fsObj = new cs_fileSystemClass();
		$this->read_version_file();
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
		if(is_null($this->stepData) || !count($this->stepData)) {
			$this->stepData = array(
				"dbInfo"		=> array(
					'name'			=> "Database Information",
					'description'	=> "Setup initial values for database connectivity " .
						"(database name, hostname, port, username, password).  This " .
						"will be used later for creating the database, setting up users, " .
						"and setting some default values.",
					'isComplete'	=> FALSE,
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
	private function setup__dbInfo() {
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