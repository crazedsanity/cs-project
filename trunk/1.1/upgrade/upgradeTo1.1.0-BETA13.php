<?php
/*
 * Created on Oct 29, 2007
 */


class upgrade_to_1_1_0_BETA13 extends dbAbstract {
	
	private $logsObj;
	
	//=========================================================================
	public function __construct(cs_phpDB &$db) {
		if(!$db->is_connected()) {
			throw new exception(__METHOD__ .": database is not connected");
		}
		$this->db = $db;
		
		$this->logsObj = new logsClass($this->db, 'Upgrade');
		
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function run_upgrade() {
		
		
		$this->db->beginTrans(__METHOD__);
		
		$this->run_schema_changes();
		$this->rewrite_config_file();
		
		
		$this->db->commitTrans(__METHOD__);
		
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	private function run_schema_changes() {
		
		$this->gfObj->debug_print(__METHOD__ .": running SQL file...");
		$this->run_sql_file(dirname(__FILE__) .'/../docs/sql/upgrades/upgradeTo1.1.0-BETA13.sql');
		
		$details = "Executed SQL file, '". $this->lastSQLFile ."'.  Encoded contents::: ". 
			base64_encode($this->fsObj->read($this->lastSQLFile));
		$this->logsObj->log_by_class($details, 'system');
	}//end run_schema_changes()
	//=========================================================================
	
	
	
	//=========================================================================
	private function rewrite_config_file() {
		//okay, first, let's read-in the config file.
		$fs = new cs_fileSystemClass;
		$configFile = dirname(__FILE__) ."/../lib/config.xml";
		$configContents = $fs->read($configFile);
		
		$encodedContents = base64_encode($configContents);
		$xmlObj = new XMLParser($configContents);
		
		$myData = $xmlObj->get_tree(TRUE);
		
		$xmlCreator = new XMLCreator('CONFIG', NULL);
		$xmlCreator->load_xmlparser_data($xmlObj);
		
		$oldHost = $myData['CONFIG']['CONFIG_EMAIL_SERVER_IP'];
		$xmlCreator->remove_path('/CONFIG/CONFIG_EMAIL_SERVER_IP');
		$xmlCreator->add_tag('/CONFIG/PHPMAILER_HOST', $oldHost);
		$xmlCreator->add_tag('/CONFIG/PHPMAILER_METHOD', 'IsSMTP');
		
		
		$newXmlConfig = $xmlCreator->create_xml_string();
		
		$fs->closeFile();
		$fs->create_file($configFile, TRUE);
		$fs->openFile($configFile);
		$retval = $fs->write($newXmlConfig, $configFile);
		$this->logsObj->log_by_class("Wrote new config file (". $retval .")", 'system');
		
	}//end rewrite_config_file()
	//=========================================================================
}

?>
