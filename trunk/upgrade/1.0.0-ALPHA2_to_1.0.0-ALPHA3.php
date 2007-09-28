<?php
/*
 * Created on September 28th, 2007
 * 
 * 
 * options for todo's (assuming helpdesk will also have them):::
 * 		1.) single todo table + single linker table
 * 			* remove "record_id" constraint
 * 			* create linker table with constraint to todo_id, and a column indicating helpdesk/project
 * 			* PROBLEM: no linked records might disappear, no db constraint
 * 		2.) single todo table + multiple linker tables
 * 			* remove record_id constraint
 * 			* create table for linking to helpdesk (constraints for todo & helpdesk)
 * 			* create table for linking to project (constraints for todo & project)
 * 			* PROBLEM: future linking requires additional tables, todo can be linked to project AND helpdesk records
 * 		3.) multiple todo tables
 * 			* create helpdesk_todo table
 * 			* create project_todo table
 * 			* convert data from old table into new table(s)
 * 			* PROBLEM: code is duplicated (once helpdesk gets records)
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
		$this->fsObj = new cs_fileSystemClass(dirname(__FILE__) .'/../docs/sql/setup/1.0.0-ALPHA2_to_1.0.0-ALPHA3');
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * This is the method defined in config.xml and should be called by 
	 * upgrade::perform_upgrade().  Not surprisingly, it's supposed to do all 
	 * the stuff to upgrade the code & database.
	 */
	public function run_upgrade() {
		
		$this->perform_db_changes();
		$this->convert_note_records();
		
		throw new exception(__METHOD__ .": cowardly");
		
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	private function perform_db_changes() {
		$fileList = array(
			'project_tables', 'helpdesk_tables.sql', 'populate_tables.sql'
		);
	}//end perform_db_changes()
	//=========================================================================
	
	
	
	//=========================================================================
	private function convert_note_records() {
	}//end convert_note_records()
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
	
	
	
	//=========================================================================
	private function convert_existing_data() {
		//update some preferences.
		$sqlArr = array(
			"UPDATE pref_type_table SET default_value='helpdesk' WHERE name='startModule' AND default_value='rts'" => 1,
			"UPDATE pref_option_table SET effective_value='helpdesk' WHERE name='Helpdesk'"	=> 1,
			"UPDATE record_type_table SET module='helpdesk' WHERE module='rts'"	=> 1
		);
		
		$retval = 0;
		foreach($sqlArr as $sql=>$expectedRows) {
			$result = $this->run_sql($sql, $expectedRows);
			if($result === TRUE) {
				$retval++;
			}
			else {
				throw new exception(__METHOD__ .": could not run update... ");
			}
		}
		
		$this->gfObj->debug_print(__METHOD__ .": finished with ($retval)");
		return($retval);
	}//end convert_existing_data()
	//=========================================================================
	
}//end tempUpgradeClass{}
?>