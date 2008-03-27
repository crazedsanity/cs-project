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
		
		$configFileContents = $fs->read(CONFIG_FILE_LOCATION);
		
		/* 
		 * PROBLEM: the config file MUST be readable in the specified location...
		 * once it is moved, the site config needs to be updated to point at the 
		 * new location... we could throw an error here, explaining that the 
		 * file needs to be moved manually, but if the user upgrades to the NEXT 
		 * version, the system would believe that there's a problem because the 
		 * config file would be MISSING (they'd have to go through setup all over
		 * again, even though they have an existing WORKING db)...
		 * 
		 * STEPS THAT NEED TO OCCUR:::
		 * 1.) move the config file to /rw/config.xml from /lib/config.xml
		 * 2.) ensure the site_config points to the CURRENT config file
		 * 		OPTIONS:
		 * 			a.) set "trigger" to look for old config when rw/config.xml
		 * 				is missing & do the proper change (must be set prior 
		 * 				to the upgrade/setup system checks)
		 * 					++ ALPHA3 -> ALPHA4 would work
		 * 					-- potentially breaks future upgrades
		 * 					-- bypasses upgrade system
		 * 			b.) scriptify direct modification of the site_config.php file
		 * 				so it points to the new location during this upgrade
		 * 					++ ALPHA3 -> ALPHA4 would work
		 * 					++ doesn't bypass upgrade system
		 * 			c.) drop all auto-upgrades prior to this one (ALPHA4), with 
		 * 				moving the config file being the FIRST step, then run 
		 * 				ALL other changes; it's an ALPHA, so things are 
		 * 				expected to possibly be hairy.
		 * 					-- developers running pre-ALPHA4 will have problems
		 * 					++ ALPHA3 -> ALPHA4 would work
		 * 					++ doesn't bypass upgrade system
		 * 3.) systems upgrading from 1.1 must be able to do so automatically.
		 * 
		 * PROBLEMS:
		 * 1.) setup MUST know where current config file is: if it is pointing 
		 * to the NEW location and it exists in the OLD, setup will run; if it 
		 * is pointing to the OLD location but it's in the NEW, setup will run.
		 * 		a.) if 1.2 (post-ALPHA4) always expects config to be in the new
		 * 			location, those caught in the mix can just manually move it
		 * 			to the new location
		 * 		b.) no reason to believe it would EVER point to the old location 
		 * 			when there's one in the new location.
		 * 
		 * 
		 * TODO: remove support for pre-alpha4 installs
		 * TODO: consolidate pre-alpha4 changes into alpha4 upgrade
		 * TODO: warn existing developers of changes and how to manually upgrade (update to r{x}, let setup run, update db + VERSION file)
		 */
		
		$this->gfObj->debug_print($this->gfObj->cleanString($updateXml->create_xml_string(), 'htmlentity_plus_brackets'));
		$fs->openFile(CONFIG_FILE_LOCATION);
		$fs->write($updateXml->create_xml_string());
	}//end update_config_file()
	//=========================================================================
}

?>
