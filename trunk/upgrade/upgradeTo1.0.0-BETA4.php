<?php
/*
 * Created on Jul 2, 2007
 * 
 */


class upgradeTo1_0_0_BETA4 {
	
	
	private $db;
	private $gfObj;
	private $configExtra;
	
	
	//=========================================================================
	/**
	 * The constructor.  Der.
	 */
	public function __construct(cs_phpDB &$db) {
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
		
		$sql = "update pref_type_table SET name='sorting_helpdesk' WHERE name='sorting_rts';";
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		$retval = FALSE;
		if(!strlen($dberror)) {
			$this->gfObj->debug_print(__METHOD__ .": done!");
			$retval = TRUE;
		}
		else {
			throw new exception(__METHOD__ .": there was an error: numrows=(". $numrows ."), dberror:::". $dberror);
		}
		
		return($retval);
	}//end run_upgrade()
	//=========================================================================
	
	
	
}
?>