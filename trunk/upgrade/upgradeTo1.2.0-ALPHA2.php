<?php
/*
 * Created on Nov 20, 2007
 */


class upgrade_to_1_2_0_ALPHA2 extends dbAbstract {
	
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
		$this->update_tag_icons();
		
		$this->db->commitTrans(__METHOD__);
		
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	private function run_schema_changes() {
		
		$this->gfObj->debug_print(__METHOD__ .": running SQL file...");
		$this->run_sql_file(dirname(__FILE__) .'/../docs/sql/upgrades/upgradeTo1.2.0-ALPHA2.sql');
		
		$details = "Executed SQL file, '". $this->lastSQLFile ."'.  Encoded contents::: ". 
			base64_encode($this->fsObj->read($this->lastSQLFile));
		$this->logsObj->log_by_class($details, 'system');
	}//end run_schema_changes()
	//=========================================================================
	
	
	
	//=========================================================================
	private function update_tag_icons() {
		
		$sql = "SELECT tag_name_id, name, icon_name FROM tag_name_table ORDER BY tag_name_id";
		if($this->run_sql($sql) && $this->lastNumrows > 1) {
			$allTags = $this->db->farray_fieldnames('name', 'tag_name_id');
			
			$iconMods = array(
				'critical'			=> 'red_x',
				'bug'				=> 'bug',
				'feature request'	=> 'feature_request',
				'committed'			=> 'check_red',
				'verified'			=> 'check_yellow',
				'released'			=> 'check_green'
			);
			
			$updates = 0;
			foreach($iconMods as $name=>$icon) {
				if(isset($allTags[$name])) {
					//update.
					$sql = "UPDATE tag_name_table SET icon_name='". $icon ."' WHERE id=". $allTags[$name];
				}
				else {
					//insert.
					$sql = "INSERT INTO tag_name_table (name, icon_name) VALUES ('". $name ."', '". $icon ."');";
				}
				$this->run_sql($sql);
				$updates += $this->lastNumrows;
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to retrieve tag names");
		}
		
	}//end update_tag_modifiers()
	//=========================================================================
}

?>
