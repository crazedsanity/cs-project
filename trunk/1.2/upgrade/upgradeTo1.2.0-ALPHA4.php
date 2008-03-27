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
		
		//TODO: test that /rw is readable & writable
		//TODO: write to new config file location
		//TODO: delete old config file
		
		/* 
		 * PROBLEMS WITH MOVING THE CONFIG FILE:
		 * 1.) upgrades currently won't survive (through multiple version updates) 
		 * if the config location changes, since it is statically set.
		 * 
		 * 2.) upgrades from 1.1 MUST be able to work automatically.
		 * 
		 * 3.) upgrades MUST BE SURVIVABLE through multiple version changes (see #1) 
		 * 
		 * SOLUTION:
		 * 
		 * TODO: add "trigger" to check for old config file; if old config exists, bypass setup & go to upgrade.
		 * TODO: remove support for pre-alpha4 installs
		 * TODO: consolidate pre-alpha4 changes into alpha4 upgrade
		 * TODO: warn existing developers of changes and how to manually upgrade (update to r{x}, let setup run, update db + VERSION file)
		 * 
		 * NOTE: while it might cause headaches for one or two developers, this 
		 * minimizes pain for users upgrading from previous versions.  Yay.
		 */
		
	}//end update_config_file()
	//=========================================================================
}

?>
