<?php
/*
 * Created on Nov 20, 2007
 */


class upgrade_to_1_1_0_BETA14 extends dbAbstract {
	
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
		
		$this->run_schema_changes();
		$this->update_tag_modifiers();
		
		$this->db->commitTrans(__METHOD__);
		
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	private function run_schema_changes() {
		
		$this->gfObj->debug_print(__METHOD__ .": running SQL file...");
		$this->run_sql_file(dirname(__FILE__) .'/../docs/sql/upgrades/upgradeTo1.1.0-BETA14.sql');
		
		$details = "Executed SQL file, '". $this->lastSQLFile ."'.  Encoded contents::: ". 
			base64_encode($this->fsObj->read($this->lastSQLFile));
		$this->logsObj->log_by_class($details, 'system');
	}//end run_schema_changes()
	//=========================================================================
	
	
	
	//=========================================================================
	private function update_tag_modifiers() {
		
		$sql = "SELECT tag_name_id, name FROM tag_name_table ORDER BY tag_name_id";
		if($this->run_sql($sql) && $this->lastNumrows > 1) {
			$allTags = $this->db->farray_nvp('tag_name_id', 'name');
			
			$specialModifiers = array(
				'critical'			=> -5,
				'exception'			=> -2,
				'invalid data'		=> -2,
				'authentication'	=> -1,
				'bug'				=> -1,
				'cannot fix'		=> -1,
				'data cleaning'		=> -1,
				'feature request'	=> -1,
				'email'				=> 0,
				'formatting'		=> 0,
				'has dependencies'	=> 0,
				'information'		=> 0,
				'network related'	=> 0,
				'session'			=> 0,
				'upgrade'			=> 1,
				'duplicate'			=> 2,
				'cannot reproduce'	=> 3,
			);
			
			$updates = 0;
			foreach($allTags as $id=>$name) {
				if(is_numeric($specialModifiers[$name])) {
					//change the modifier accordingly...
					$sql = "UPDATE tag_name_table SET modifier=". $specialModifiers[$name] ." WHERE tag_name_id=". $id;
					if($this->run_sql($sql) && $this->lastNumrows == 1) {
						$updates++;
					}
					else {
						throw new exception(__METHOD__ .": failed to update tag (". $name .") with special " .
								"modifier (". $specialModifiers[$name] .")");
					}
					
					//
					$details = "Changed modifier of [tag_name_id=". $id ."] to (". $specialModifiers[$name] .")";
					$this->logsObj->log_by_class($details, 'update');
				}
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to retrieve tag names");
		}
		
	}//end update_tag_modifiers()
	//=========================================================================
}

?>
