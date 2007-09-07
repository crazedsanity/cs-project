<?php
/*
 * Created on Jul 2, 2007
 * 
 */


class upgradeToBeta_3_3_0 {
	
	
	private $db;
	private $gfObj;
	private $configExtra;
	private $newVersion = "BETA-3.3.0";
	
	
	//=========================================================================
	/**
	 * The constructor.  Der.
	 */
	public function __construct(phpDB &$db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * This is the method defined in config.xml and should be called by 
	 * upgrade::perform_upgrade().  Not surprisingly, it's supposed to do all 
	 * the stuff to upgrade the code & database.
	 */
	public function run_upgrade() {
		
		$this->convert_existing_data();
		$this->update_log_category_and_class_data();
		$this->update_config_file();
		$this->update_database_version();
		
		#throw new exception(__METHOD__ .": cowardly");
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Updates information that's stored in the database, internal to cs-project, 
	 * so the version there is consistent with all the others.
	 */
	private function update_database_version() {
		
		$queryArr = array(
			'major'			=> "SELECT internal_data_set_value('version_major', 'BETA-3');",
			'minor'			=> "SELECT internal_data_set_value('version_minor', '3');",
			'maintenance'	=> "SELECT internal_data_set_value('version_maintenance', '0');",
			'version'		=> "SELECT internal_data_set_value('version_string', '". $this->newVersion ."')"
		);
		
		$retval = NULL;
		foreach($queryArr as $name=>$sql) {
			if($this->run_sql($sql, 1)) {
				$retval++;
			}
		}
		
		//okay, now check that the version string matches the updated bits.
		if(!$this->check_database_version($this->newVersion)) {
			throw new exception(__METHOD__ .": database version information is invalid: (". $this->newVersion .")");
		}
		
		return($retval);
		
	}//end update_database_version()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Checks consistency of version information in the database, and optionally 
	 * against a given version string.
	 */
	private function check_database_version($checkThisVersion=NULL) {
		//retrieve the internal version information.
		$sql = "select internal_data_get_value('version_string') as version_string, (" .
			"internal_data_get_value('version_major') || '.' || " .
			"internal_data_get_value('version_minor') || '.' || " .
			"internal_data_get_value('version_maintenance')) as check_version";
		
		$retval = NULL;
		if($this->run_sql($sql,1)) {
			$data = $this->db->farray_fieldnames();
			if($data['version_string'] == $data['check_version']) {
				$retval = TRUE; 
			}
			else {
				$retval = FALSE;
			}
		}
		else {
			$retval = FALSE;
		}
		
		return($retval);
		
	}//end check_database_version()
	//=========================================================================
	
	
	
	//=========================================================================
	private function run_sql($sql, $expectedNumrows=1) {
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror)) {
			$details = "DBERROR::: ". $dberror;
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: ". $details);
		}
		elseif(!is_null($expectedNumrows) && $numrows != $expectedNumrows) {
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: " .
				"rows affected didn't match expectation (". $numrows ." != ". $expectedNumrows .")");
		}
		elseif(is_null($expectedNumrows) && $numrows < 1) {
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: " .
				"invalid number of rows affected (". $numrows .")");
		}
		else {
			$retval = TRUE;
		}
		
		return($retval);
	}//end run_sql()
	//=========================================================================
	
	
	
	//=========================================================================
	private function convert_existing_data() {
		//update some preferences.
		$sqlArr = array(
			"UPDATE pref_type_table SET default_value='helpdesk' WHERE name='startModule' AND default_value='rts'" => 1,
			"UPDATE pref_option_table SET effective_value='helpdesk' WHERE name='Helpdesk'"	=> 1,
			"UPDATE record_type_table SET module='helpdesk' WHERE module='rts'"	=> 1
		);
		
		$retval = 0;
		foreach($sqlArr as $sql=>$expectedRows) {
			$result = $this->run_sql($sql, $expectedRows);
			if($result === TRUE) {
				$retval++;
			}
			else {
				throw new exception(__METHOD__ .": could not run update... ");
			}
		}
		
		$this->gfObj->debug_print(__METHOD__ .": finished with ($retval)");
		return($retval);
	}//end convert_existing_data()
	//=========================================================================
	
	
	
	//=========================================================================
	private function update_config_file() {
		$gf = new cs_globalFunctions;
		$myConfigFile = 'lib/'. CONFIG_FILENAME;
		$fs = new cs_fileSystemClass(dirname(__FILE__) .'/../');
		$xmlParser = new XMLParser($fs->read($myConfigFile));
		$xmlCreator = new XMLCreator;
		$xmlCreator->load_xmlparser_data($xmlParser);
		
		//define items that should be added to the config.
		$newElements = array(
			'proj_name'						=> 'CS-Project',
			'version_string'				=> 'BETA-3.3.0'
		);
		
		$newElements = array_merge($this->configExtra, $newElements);
		foreach($newElements as $name=>$value) {
			$xmlCreator->add_tag($name, $value);
		}
		
		$xmlString = $xmlCreator->create_xml_string();
		$fs->write($xmlString, $myConfigFile);
	}//end update_config_file()
	//=========================================================================
	
	
	
	//=========================================================================
	private function update_log_category_and_class_data() {
		$sqlArr = array(
			'rectype__project'	=> "select record_type_id FROM record_type_table WHERE module='project';",
			'rectype__helpdesk'	=> "select record_type_id FROM record_type_table WHERE module='helpdesk';",
			'rectype__todo'		=> "select record_type_id FROM record_type_table WHERE module='todo';",
			'logcat__helpdesk'	=> "select log_category_id FROM log_category_table WHERE name='Helpdesk';",
			'logcat__prefs'		=> "select log_category_id FROM log_category_table WHERE name='Preferences';",
			'logcat__project'	=> "select log_category_id FROM log_category_table WHERE name='Project';",
			'logcat__session'	=> "select log_category_id FROM log_category_table WHERE name='Authentication';"
		);
		
		$retval = 0;
		foreach($sqlArr as $configIndex=>$sql) {
			$result = $this->run_sql($sql);
			if($result === TRUE) {
				$data = $this->db->farray();
				$this->configExtra[$configIndex] = $data[0];
				$retval++;
			}
			else {
				throw new exception(__METHOD__ .": failed to get data for ". $configIndex);
			}
		}
		
		return($retval);
	}//end update_log_category_and_class_data()
	//=========================================================================
	
}//end tempUpgradeClass{}
?>