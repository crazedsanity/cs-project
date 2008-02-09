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
		
		$this->db->beginTrans(__METHOD__);
		
		$this->update_config_file();
		exit;
		
		$this->db->commitTrans(__METHOD__);
		
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	public function update_config_file() {
		$fs = new cs_fileSystemClass(dirname(__FILE__) .'/../');
		$sampleXmlObj = new XMLParser($fs->read('docs/samples/sample_config.xml'));
		$siteXmlObj = new XMLParser($fs->read('lib/config.xml'));
		
		$updateXml = new xmlCreator();
		$updateXml->load_xmlparser_data($siteXmlObj);
		
		$sampleIndexes = $sampleXmlObj->get_tree(TRUE);
		$sampleIndexes = $sampleIndexes['CONFIG'];
		$siteConfigIndexes = $siteXmlObj->get_tree(TRUE);
		
		foreach($sampleIndexes as $indexName=>$indexValue) {
			$path = '/CONFIG/'. $indexName;
			$attributes = $sampleXmlObj->get_attribute($path);
			#debug_print(__METHOD__ .": attributes from sample (/CONFIG/". $indexName ."::: ",1);
			#debug_print($attributes,1);
			
			if(is_array($attributes)) {
				$updateXml->add_attribute($path, $attributes);
			}
		}
		
		$this->gfObj->debug_print($updateXml->create_xml_string());
		$fs->openFile('lib/_test_config.xml');
		$fs->write($updateXml->create_xml_string());
		exit;
	}//end update_config_file()
	//=========================================================================
}

?>
