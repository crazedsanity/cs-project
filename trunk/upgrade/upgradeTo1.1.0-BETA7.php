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
		
		//TODO: re-assign records created by contacts w/null email_contact_ids to ANONYMOUS
		//TODO: delete all attributes & contact_table records for contacts w/null email_contact_ids
		//TODO: add NOT NULL to contact_email_id column of contact_table
		//TODO: remove LOGCAT__ and RECTYPE__ entries from config.xml
		exit;
		
		
		$this->db->commitTrans(__METHOD__);
		
	}//end run_upgrade()
	//=========================================================================
}

?>
