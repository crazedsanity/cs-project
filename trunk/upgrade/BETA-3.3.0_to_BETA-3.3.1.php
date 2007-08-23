<?php
/*
 * Created on Jul 2, 2007
 * 
 */


class upgradeToBeta_3_3_1 {
	
	
	private $db;
	private $gfObj;
	private $configExtra;
	private $newVersion = "BETA-3.3.1";
	
	
	//=========================================================================
	/**
	 * The constructor.  Der.
	 */
	public function __construct(phpDB &$db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debug_print(__METHOD__ .": running... ");
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * This is the method defined in config.xml and should be called by 
	 * upgrade::perform_upgrade().  Not surprisingly, it's supposed to do all 
	 * the stuff to upgrade the code & database.
	 */
	public function run_upgrade() {
		
		$this->update_config_file();
		$this->update_database_version();
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
			'maintenance'	=> "SELECT internal_data_set_value('version_maintenance', '1');",
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
	private function update_config_file() {
		$gf = new cs_globalFunctions;
		$myConfigFile = 'lib/'. CONFIG_FILENAME;
		$fs = new cs_fileSystemClass(dirname(__FILE__) .'/../');
		$xmlParser = new XMLParser($fs->read($myConfigFile));
		$xmlCreator = new XMLCreator;
		$xmlCreator->load_xmlparser_data($xmlParser);
		
		//define items that should be added to the config.
		$newElements = array(
			'version_string'				=> $this->newVersion
		);
		
		$gf->debug_print(__METHOD__ .": running... ");
		
		foreach($newElements as $name=>$value) {
			$xmlCreator->add_tag($name, $value);
		}
		
		$xmlString = $xmlCreator->create_xml_string();
		$fs->write($xmlString, $myConfigFile);
	}//end update_config_file()
	//=========================================================================
	
	
	
}
?>