<?php
/*
 * Created on February 08, 2008
 */


class upgrade_to_1_2_0_ALPHA3 extends dbAbstract {
	
	private $logsObj;
	
	//=========================================================================
	public function __construct(cs_phpDB &$db) {
		if(!$db->is_connected()) {
			throw new exception(__METHOD__ .": database is not connected");
		}
		$this->db = $db;
		
		$this->logsObj = new logsClass($this->db, 'Upgrade');
		
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = 1;
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function run_upgrade() {
		
		$this->update_config_file();
		
		return('Upgrade complete');
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	public function update_config_file() {
		$fs = new cs_fileSystemClass(dirname(__FILE__) .'/../');
		$sampleXmlObj = new XMLParser($fs->read('docs/samples/sample_config.xml'));
		$siteXmlObj = new XMLParser($fs->read('lib/config.xml'));
		
		$updateXml = new xmlCreator();
		$updateXml->load_xmlparser_data($siteXmlObj);
		
		
		//BACKUP ORIGINAL XML CONFIG...
		$backupFile = 'lib/__BACKUP__'. time() .'__config.xml';
		$fs->create_file($backupFile);
		$fs->openFile($backupFile);
		$fs->write($updateXml->create_xml_string());
		
		$sampleIndexes = $sampleXmlObj->get_tree(TRUE);
		$sampleIndexes = $sampleIndexes['CONFIG'];
		
		$siteConfigIndexes = $siteXmlObj->get_tree(TRUE);
		$siteConfigIndexes = $siteConfigIndexes['CONFIG'];
		
		foreach($sampleIndexes as $indexName=>$indexValue) {
			$path = '/CONFIG/'. $indexName;
			$attributes = $sampleXmlObj->get_attribute($path);
			#debug_print(__METHOD__ .": attributes from sample (/CONFIG/". $indexName ."::: ",1);
			#debug_print($attributes,1);
			debug_print(__METHOD__ .': indexName=('. $indexName .'), indexValue=('. $indexValue .'), original config value=('. $siteConfigIndexes[$indexName] .')');
			
			//add tag if it's not there, update values otherwise.
			$tagValue = $attributes['DEFAULT'];
			if(isset($siteConfigIndexes[$indexName])) {
				$tagValue = $siteConfigIndexes[$indexName];
			}
			elseif($indexName == 'PHPMAILER_HOST' && isset($siteConfigIndexes['CONFIG_EMAIL_SERVER_IP'])) {
				$tagValue = $siteConfigIndexes['CONFIG_EMAIL_SERVER_IP'];
				$updateXml->remove_path('/CONFIG/CONFIG_EMAIL_SERVER_IP');
			}
			$updateXml->add_tag($path, $tagValue, $attributes);
		}
		
		$this->gfObj->debug_print($this->gfObj->cleanString($updateXml->create_xml_string(), 'htmlentity_plus_brackets'));
		$fs->openFile('lib/config.xml');
		$fs->write($updateXml->create_xml_string());
	}//end update_config_file()
	//=========================================================================
}

?>
