<?php
/*
 * Created on September 28th, 2007
 * 
 * For the 1.x series, only projects will have todo's.
 * 	* WORKFLOW:
 * 		-- create project (overview of what will be done)
 * 		-- create features
 * 		-- accept features (complete issue)
 * 			* complete issue
 * 			* create todo(s) based on completed feature
 * 		-- assign bugs
 * 			* note all work for bug on issue
 * 			* completing issue == bug is fixed.
 * 
 * For the 2.x series, no more project todo's; they'll be converted to helpdesk tasks.
 * 	* WORKFLOW:
 * 		-- create project (overview of what will be done)
 * 		-- create list of features
 * 			* tasks = research
 * 		-- accept features
 * 			* tasks = to be complete before feature is complete
 * 		-- assign bugs
 * 			* tasks = list of things to fix
 * 	* SUBPROJECTS: just like regular projects, but a smaller subset.
 */


class upgradeTo1_0_0_alpha3 {
	
	
	private $db;
	private $gfObj;
	private $fsObj;
	
	
	//=========================================================================
	/**
	 * The constructor.  Der.
	 */
	public function __construct(cs_phpDB &$db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->fsObj = new cs_fileSystemClass(dirname(__FILE__) .'/../docs/sql/upgrades');
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * This is the method defined in config.xml and should be called by 
	 * upgrade::perform_upgrade().  Not surprisingly, it's supposed to do all 
	 * the stuff to upgrade the code & database.
	 */
	public function run_upgrade() {
		
		$retval = $this->perform_db_changes();
		
		return($retval);
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	private function perform_db_changes() {
		$retval = 0;
			
		$contents = $this->fsObj->read("1.0.0-ALPHA2_to_1.0.0-ALPHA3.sql");
		if($this->run_sql($contents, 0)) {
			$retval++;
		}
		else {
			throw new exception(__METHOD__ .": failed to execute SQL... ");
		}
		
		return($retval);
	}//end perform_db_changes()
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
	
}//end tempUpgradeClass{}
?>