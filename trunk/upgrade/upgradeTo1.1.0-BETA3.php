<?php
/*
 * Created on Oct 24, 2007
 */


class upgrade_to_1_1_0_BETA3 extends dbAbstract {
	
	private $gfObj;
	private $logsObj;
	
	//=========================================================================
	public function __construct(cs_phpDB &$db) {
		if($db->is_connected()) {
			$this->db = $db;
		}
		else {
			throw new exception(__METHOD__ .": database isn't connected");
		}
		
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
		
		//now create the logging object.
		$this->logsObj = new logsClass($this->db, "Upgrade");
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function run_upgrade() {
		$this->gfObj->debug_print(__METHOD__ .": running SQL file...");
		$this->run_sql_file(dirname(__FILE__) .'/../docs/sql/upgrades/upgradeTo1.1.0-BETA3_part1.sql');
		
		$details = "Executed SQL file, '". $this->lastSQLFile ."'.  Encoded contents::: ". 
			base64_encode($this->fsObj->read($this->lastSQLFile));
		$this->logsObj->log_by_class($details, 'system');
		
		
		$this->convert_data();
		
		$this->run_sql_file(dirname(__FILE__) .'/../docs/sql/upgrades/upgradeTo1.1.0-BETA3_part2.sql');$this->run_sql_file(dirname(__FILE__) .'/../docs/sql/upgrades/upgradeTo1.1.0-BETA3_part1.sql');
		
		$details = "Executed SQL file, '". $this->lastSQLFile ."'.  Encoded contents::: ". 
			base64_encode($this->fsObj->read($this->lastSQLFile));
		$this->logsObj->log_by_class($details, 'system');
		
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	private function convert_data() {
		
		//convert data from the logs.
		$sql = "SELECT l.*, rc.module, rc.name as module_name FROM log_table AS l " .
			"INNER JOIN record_type_table AS rc ON (l.record_type_id=" .
			"rc.record_type_id) WHERE l.record_type_id IS NOT NULL AND l.record_id " .
			"IS NOT NULL";
		
		$retval = 0;
		if($this->run_sql($sql)) {
			$logRecords = $this->db->farray_fieldnames('log_id', NULL, 0);
			
			foreach($logRecords as $logId => $data) {
				//update each log with a link to the issue/project, then log the change.
				$addThis = " ". strtoupper($data['module_name']) ."Link: [". $data['module'] .
					"_id=". $data['record_id'] ."]";
				$sql = "UPDATE log_table SET details=details || '". $addThis ."' WHERE log_id=". $logId;
				$this->run_sql($sql);
				
				if($this->lastNumrows == 1) {
					//okay, now log it.
					$details = "Data added to log_id=". $logId ."::: ". $addThis;
					$this->logsObj->log_by_class($details, 'update');
				}
				else {
					throw new exception(__METHOD__ .": updated more than one record (". $this->lastNumrows .")");
				}
			}
		}
		
		return($retval);
	}//end convert_data()
	//=========================================================================
}

?>
