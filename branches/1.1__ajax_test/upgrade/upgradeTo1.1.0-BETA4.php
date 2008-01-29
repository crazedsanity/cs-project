<?php
/*
 * Created on Oct 29, 2007
 */


class upgrade_to_1_1_0_BETA4 extends dbAbstract {
	
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
		
		$sql = "ALTER TABLE attribute_table ADD COLUMN display_name text;";
		$this->run_sql($sql);
		
		//okay, now convert all records to have a proper display name.
		if($this->run_sql("SELECT attribute_id, name FROM attribute_table ORDER BY name")) {
			$attribData = $this->db->farray_nvp('attribute_id', 'name');
			
			foreach($attribData as $aId=>$name) {
				$newName = ucwords($name);
				if(preg_match('/^im_/', $name)) {
					$tmp = split("_", $name);
					$newName = "IM: ". ucwords($tmp[1]);
				}
				
				$sql = "UPDATE attribute_table SET display_name='". $newName ."' WHERE attribute_id=". $aId;
				if($this->run_sql($sql) && $this->lastNumrows == 1) {
					$this->logsObj->log_by_class(__METHOD__ .": updated attribute_id=". $aId ." with " .
						"name=(". $name ."): new display value is (". $newName .")");
				}
				else {
					$this->logsObj->log_dberror(__METHOD__ .": failed to update attribute_id=". $aId .": ". $this->lastError);
				}
			}
			
			//now set the "display_name" column as NOT NULL
			$this->run_sql("ALTER TABLE attribute_table ALTER COLUMN display_name SET NOT NULL");
		}
		else {
			throw new exception(__METHOD__ .": failed to retrieve any attributes for updating");
		}
		
		$this->db->commitTrans(__METHOD__);
		
	}//end run_upgrade()
	//=========================================================================
}

?>
