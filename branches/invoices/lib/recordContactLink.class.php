<?php
/*
 * Created on November 1st, 2007
 * 
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */


class recordContactLink extends dbAbstract {
	
	public $db;
	protected $gfObj;
	
	//=========================================================================
	function __construct(cs_phpDB $db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function add_link($recordId, $contactId) {
		
		$retval = FALSE;
		
		if(!$this->check_link_exists($recordId, $contactId)) {
			$sql = "INSERT INTO record_contact_link_table (record_id, contact_id) " .
					"VALUES (". $recordId .", ". $contactId .")";
			
			if($this->run_sql($sql)) {
				$retval = TRUE;
			}
		}
		
		return($retval);
	}//end add_link()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_record_links($recordId) {
		$sql = "SELECT record_id, contact_id FROM record_contact_link_table " .
				"WHERE record_id=". $recordId ." ORDER BY record_id";
		$retval = array();
		
		if($this->run_sql($sql)) {
			$data = $this->db->farray_fieldnames(NULL, TRUE);
			
			foreach($data as $index=>$subData) {
				$retval[$subData['record_id']][] = $subData['contact_id'];
			}
			
			//now make sure no extra records exist...
			foreach($retval as $index=>$data) {
				if(is_array($data)) {
					$retval[$index] = array_unique($data);
				}
			}
		}
		
		return($retval);
	}//end get_record_links()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_contact_links($contactId) {
		$sql = "SELECT record_id, contact_id FROM record_contact_link_table " .
				"WHERE contact_id=". $contactId ." ORDER BY record_id";
		$retval = array();
		
		if($this->run_sql($sql)) {
			$data = $this->db->farray_fieldnames(NULL, TRUE);
			
			foreach($data as $index=>$subData) {
				$retval[$subData['contact_id']][] = $subData['record_id'];
			}
			
			$this->gfObj->debug_print($retval);
			#exit;
			
			//now make sure no extra records exist...
			foreach($retval as $index=>$data) {
				if(is_array($data)) {
					$retval[$index] = array_unique($data);
				}
			}
		}
		
		return($retval);
	}//end get_contact_links()
	//=========================================================================
	
	
	
	//=========================================================================
	public function remove_link($recordId, $contactId) {
		$sql = "DELETE FROM record_contact_link_table WHERE record_id=". $recordId .
				" AND contact_id=". $contactId;
		$retval = FALSE;
		
		if($this->run_sql($sql) && $this->lastNumrows == 1) {
			$retval = TRUE;
		}
		else {
			$retval = FALSE;
		}
		
		return($retval);
	}//end remove_link()
	//=========================================================================
	
	
	
	//=========================================================================
	public function check_link_exists($recordId, $contactId) {
		$sql = "SELECT * FROM record_contact_link_table WHERE record_id=". 
				$recordId ." AND contact_id=". $contactId;
		
		$retval = FALSE;		
		if($this->run_sql($sql) && $this->lastNumrows > 0) {
			$retval = TRUE;
		}
		
		return($retval);
	}//end check_link_exists()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_record_email_list($recordId) {
		$sql = "SELECT rcl.contact_id, ce.email FROM record_contact_link_table AS rcl " .
				"INNER JOIN contact_table AS c ON (c.contact_id=rcl.contact_id) " .
				"INNER JOIN contact_email_table AS ce ON (c.contact_email_id=ce.contact_email_id) " .
				"WHERE rcl.record_id=". $recordId;
		
		$retval = array();
		if($this->run_sql($sql) && $this->lastNumrows > 0) {
			$retval = $this->db->farray_nvp('contact_id', 'email');
		}
		
		return($retval);
	}//end get_record_email_list()
	//=========================================================================
	
	
}
?>
