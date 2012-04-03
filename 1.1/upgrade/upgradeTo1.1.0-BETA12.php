<?php
/*
 * Created on Nov 07, 2007
 */


class upgrade_to_1_1_0_BETA12 extends dbAbstract {
	
	//=========================================================================
	public function __construct(cs_phpDB $db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
		$this->logsObj = new logsClass($this->db, 'Upgrade');
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function run_upgrade() {
		$this->db->beginTrans(__METHOD__);
		
		$this->convert_issues();
		
		$this->db->commitTrans(__METHOD__);
		
		
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_contacts_from_issue_notes($recordId) {
		$sql = "SELECT note_id, creator_contact_id FROM note_table WHERE record_id=". $recordId;
		$retval = NULL;
		if($this->run_sql($sql) && $this->lastNumrows > 0) {
			$retval = $this->db->farray_nvp('note_id', 'creator_contact_id');
		}
		
		return($retval);
	}//end get_contacts_from_issue_notes()
	//=========================================================================
	
	
	//=========================================================================
	private function convert_issues() {
		$linkObj = new recordContactLink($this->db);
		//retrieve all the issues.
		$sql = "SELECT public_id, record_id, leader_contact_id, creator_contact_id " .
				"FROM record_table WHERE is_helpdesk_issue IS TRUE ORDER BY public_id";
		
		if($this->run_sql($sql) && $this->lastNumrows >= 1) {
			$allIssues = $this->db->farray_fieldnames('record_id', NULL, 0);
			
			$totalRes = 0;
			foreach($allIssues as $recordId=>$data) {
				$contactIds = array();
				if(is_numeric($data['creator_contact_id']) && $data['creator_contact_id'] > 0) {
					$contactIds[] = $data['creator_contact_id'];
				}
				if(is_numeric($data['leader_contact_id']) && $data['leader_contact_id'] > 0) {
					$contactIds[] = $data['leader_contact_id'];
				}
				
				$moreIds = $this->get_contacts_from_issue_notes($recordId);
				if(is_array($moreIds)) {
					$contactIds = array_merge($contactIds, $moreIds);
				}
				
				//now try adding each of the given contacts to the issue.
				if(count($contactIds)) {
					$contactids = array_unique($contactIds);
					foreach($contactIds as $cid) {
						$addRes = $linkObj->add_link($data['record_id'], $cid);
						if($addRes === TRUE) {
							$totalRes++;
							$this->logsObj->log_by_class("Linked [contact_id=". $cid ."] " .
									"to [helpdesk_id=". $data['public_id'] ."] ([record_id=" .
									$data['record_id'] ."])", 'create');
						}
					}
				}
			}
			
			$this->logsObj->log_by_class(__METHOD__ .": created (". $totalRes .") links", 'upgrade');
		}
	}//end convert_issues()
	//=========================================================================
	
}//end upgrade_to_1_1_0_BETA11{}

?>