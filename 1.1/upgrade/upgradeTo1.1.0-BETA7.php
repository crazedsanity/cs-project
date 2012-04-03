<?php
/*
 * Created on Oct 29, 2007
 */


class upgrade_to_1_1_0_BETA7 extends dbAbstract {
	
	private $logsObj;
	
	//=========================================================================
	public function __construct(cs_phpDB &$db) {
		if(!$db->is_connected()) {
			throw new exception(__METHOD__ .": database is not connected");
		}
		$this->db = $db;
		
		$this->logsObj = new logsClass($this->db, 'Upgrade');
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function run_upgrade() {
		
		
		$this->db->beginTrans(__METHOD__);
		
		$this->alter_contact_table();
		$this->rewrite_config_file();
		
		
		$this->db->commitTrans(__METHOD__);
		
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	private function alter_contact_table() {
		$this->run_sql("ALTER TABLE contact_table ALTER COLUMN contact_email_id SET NOT NULL;");
		
		if(!strlen($this->lastError)) {
			$this->logsObj->log_by_class(__METHOD__ .": changed contact_table.contact_email_id to NOT NULL", 'system');
		}
		else {
			throw new exception(__METHOD__ .": failed to alter contact table: ". $this->lastError);
		}
	}//end alter_contact_table()
	//=========================================================================
	
	
	
	//=========================================================================
	private function rewrite_config_file() {
		//okay, first, let's read-in the config file.
		$fs = new cs_fileSystemClass;
		$configFile = dirname(__FILE__) ."/../lib/config.xml";
		$configContents = $fs->read($configFile);
		$xmlObj = new XMLParser($configContents);
		
		$myData = $xmlObj->get_tree();
		$removeIndexes = array();
		foreach($myData['CONFIG'] as $index=>$stuff) {
			if(preg_match('/^LOGCAT__/', $index) || preg_match('/^RECTYPE__/', $index)) {
				debug_print(__METHOD__ .": removing index (". $index .")");
				$removeIndexes[] = $index;
			}
		}
		
		if(count($removeIndexes)) {
			$xmlCreator = new XMLCreator('CONFIG', NULL);
			$xmlCreator->load_xmlparser_data($xmlObj);
			
			foreach($removeIndexes as $num=>$name) {
				debug_print(__METHOD__ .": removing #". $num .", name=(". $name .")");
				$oldValue = $myData['CONFIG'][$name]['value'];
				$xmlCreator->remove_path('/CONFIG/'. $name);
				$this->logsObj->log_by_class(__METHOD__ .": removing index (". $name ."), old value=(". $oldValue .")", 'system');
			}
			$details = "Removed ". count($removeIndexes) ." unneeded config indexes";
			$this->logsObj->log_by_class($details, 'system');
			$newXmlConfig = $xmlCreator->create_xml_string();
			debug_print(cleanString($newXmlConfig, 'htmlentity'));
			
			$fs->closeFile();
			$fs->create_file($configFile, TRUE);
			$fs->openFile($configFile);
			$retval = $fs->write($newXmlConfig, $configFile);
			$this->logsObj->log_by_class("Wrote new config file (". $retval .")", 'system');
		}
		else {
			$details = "No indexes removed";
			$this->logsObj->log_by_class($details, 'system');
		}
		debug_print(__METHOD__ .": ". $details);
		
	}//end rewrite_config_file()
	//=========================================================================
}

?>
