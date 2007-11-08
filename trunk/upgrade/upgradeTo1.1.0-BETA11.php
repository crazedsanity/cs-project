<?php
/*
 * Created on Nov 07, 2007
 */


class upgrade_to_1_1_0_BETA11 extends dbAbstract {
	
	//=========================================================================
	public function __construct(cs_phpDB $db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function run_upgrade() {
		$this->db->begin(__METHOD__);
		
		$this->convert_issues();
		
		
	}//end run_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_contacts_from_issue_notes($issueId) {
	}//end get_contacts_from_issue_notes()
	//=========================================================================
	
	
	//=========================================================================
	private function convert_issues() {
		//retrieve all the issues.
		$sql = "SELECT public_id, record_id, leader_contact_id, creator_contact_id " .
				"FROM record_table WHERE is_helpdesk_issue IS TRUE ORDER BY public_id";
		
		if($this->run_sql($sql) && $this->lastNumrows >= 1) {
			$allIssues = $this->db->farray_fieldnames('public_id', NULL, 0);
			
			foreach($allIssues as $issueId=>$data) {
				$contactIds = array();
				if(is_numeric($data['creator_contact_id']) && $data['creator_contact_id'] > 0) {
					$contactIds[] = $data['creator_contact_id'];
				}
				if(is_numeric($data['leader_contact_id']) && $data['leader_contact_id'] > 0) {
					$contactIds[] = $data['leader_contact_id'];
				}
				
				$moreIds = $this->get_contacts_from_issue_notes($issueId);
				if(is_array($moreIds)) {
					$contactIds = array_merge($contactIds, $moreIds);
				}
			}
		}
	}//end convert_issues()
	//=========================================================================
	
}//end upgrade_to_1_1_0_BETA11{}

?>