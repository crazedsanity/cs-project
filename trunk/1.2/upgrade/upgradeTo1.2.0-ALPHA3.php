<?php
/*
 * Created on Nov 20, 2007
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
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function run_upgrade() {
		
		
		$this->db->beginTrans(__METHOD__);
		
		
		exit;
		
		$this->db->commitTrans(__METHOD__);
		
	}//end run_upgrade()
	//=========================================================================
}

?>
