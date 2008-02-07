<?php
/*
 * Created on Oct 29, 2007
 */


class upgrade_to_1_1_0_BETA10 extends dbAbstract {
	
	private $logsObj;
	private $gfObj;
	
	private $attribId2Name = array();
	
	//=========================================================================
	public function __construct(cs_phpDB &$db) {
		if(!$db->is_connected()) {
			throw new exception(__METHOD__ .": database is not connected");
		}
		$this->db = $db;
		
		$this->gfObj = new cs_globalFunctions;
		$this->logsObj = new logsClass($this->db, 'Upgrade');
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function run_upgrade() {
		
		
		$this->db->beginTrans(__METHOD__);
		
		
		$this->offendingAttributes = array(
			'company', 'fname', 'lname', 'email'
		);
		
		$this->get_offending_attribute_ids();
		$this->destroy_offending_attributes();
		
		$this->db->commitTrans(__METHOD__);
		
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_offending_attribute_ids() {
		$retval = NULL;
		$sql = "SELECT attribute_id, name FROM attribute_table WHERE name IN " .
				"('company', 'fname', 'lname', 'email')";
		if($this->run_sql($sql)) {
			$retval = $this->db->farray_nvp('attribute_id', 'name');
			$this->attribId2Name = $retval;
			$this->attribNameList = $this->gfObj->string_from_array(array_values($retval));
			$retval = $this->gfObj->string_from_array(array_keys($retval));
		}
		else {
			$this->attribNameList = "company, fname, lname, email";
		}
		
		return($retval);
	}//end get_offending_attribute_ids()
	//=========================================================================
	
	
	
	//=========================================================================
	private function destroy_offending_attributes() {
		$attribIdString = $this->get_offending_attribute_ids();
		if(!is_null($attribIdString)) {
			$sql = "SELECT cal.*, u.uid FROM contact_attribute_link_table AS cal " .
					"LEFT OUTER JOIN user_table AS u USING (contact_id) " .
					"WHERE attribute_id IN (". $attribIdString .")";
			if($this->run_sql($sql)) {
				$contactAttribs = $this->db->farray_fieldnames('contact_attribute_link_id');
				foreach($contactAttribs as $id=>$data) {
					$logUid = 0;
					if(is_numeric($data['uid'])) {
						$logUid = $data['uid'];
					}
					$attribName = $this->attribId2Name[$data['attribute_id']];
					$details = "Removed attribute [". $attribName ."] from contact_id=[". $data['contact_id'] ."]";
					$this->logsObj->log_by_class($details, 'upgrade', $logUid);
				}
				
				//now delete them ALL.
				$this->run_sql("DELETE FROM contact_attribute_link_table WHERE attribute_id IN (". $attribIdString .")");
				$this->logsObj->log_by_class("Deleted [". $this->lastNumrows ."] attribute links", 'upgrade');
			}
			
			$this->run_sql("DELETE FROM attribute_table WHERE attribute_id IN (". $attribIdString .")");
			$this->logsObj->log_by_class("Deleted attributes [". $this->attribNameList ."] (". $this->lastNumrows .")", 'upgrade');
		}
		else {
			$this->logsObj->log_by_class("Offending attributes not present [". $this->attribNameList ."]", 'upgrade');
		}
		
		
		
	}//end destroy_offending_attributes()
	//=========================================================================

}
?>
