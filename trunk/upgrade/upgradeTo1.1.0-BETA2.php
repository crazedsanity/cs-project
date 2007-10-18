<?php
/*
 * Created on Oct 17, 2007
 */

require_once(dirname(__FILE__) .'/../lib/abstractClasses/dbAbstract.class.php');

class upgrade_to_1_1_0_BETA2 extends dbAbstract {
	
	private $gfObj;
	
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
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function run_upgrade() {
		$this->gfObj->debug_print(__METHOD__ .": running SQL file...");
		$this->run_sql_file(dirname(__FILE__) .'/../docs/sql/upgrades/upgradeTo1.1.0-BETA2.sql');
		
		
		//convert company data.
		$sql = "SELECT * FROM contact_attribute_link_table WHERE attribute_id=" .
			"(SELECT attribute_id FROM attribute_table WHERE name='company');";
		
		$updatedContacts = array();
		if($this->run_sql($sql)) {
			$data = $this->db->farray_nvp('contact_id', 'attribute_value');
			foreach($data as $conId=>$value) {
				$sql = "UPDATE contact_table SET company='". $this->gfObj->cleanString($value, 'sql') . 
					"' WHERE contact_id=". $conId;
				
				if($this->run_sql($sql)) {
					$updatedContacts[$conId]++;
				}
				else {
					throw new exception(__METHOD__ .": failed to updated contact!");
				}
			}
		}
		
		$this->run_sql("DELETE FROM contact_attribute_link_table WHERE attribute_id=(SELECT " .
			"attribute_id FROM attribute_table WHERE name='company')");
		$this->run_sql("DELETE FROM attribute_table WHERE name='company'");
		
		
		//convert email data.
		$sql = "SELECT * FROM contact_attribute_link_table WHERE attribute_id=(SELECT " .
			"attribute_id FROM attribute_table WHERE name='email')";
		if($this->run_sql($sql)) {
			$data = $this->db->farray_nvp('contact_id', 'attribute_value');
			$con2emailId = array();
			foreach($data as $conId => $value) {
				$sql = "INSERT INTO contact_email_table (contact_id, email) VALUES (". $conId .", '" .
					$this->gfObj->cleanString($value, 'email') ."');";
				$this->run_sql($sql);
				$updatedContacts[$conId]++;
				
				//get the contact_email_id just inserted, if needs be.
				if(isset($con2emailId[$conId])) {
					$sql = "SELECT currval('contact_email_table_contact_email_id_seq'::text)";
					$seqData = $this->db->farray();
					$con2emailId[$conId] = $seqData[0];
					$updatedContacts[$conId] ++;
				}
			}
			
			foreach($con2emailId as $conId => $emailId) {
				$sql = "UPDATE contact_table SET contact_email_id=". $emailId ." WHERE contact_id=". $conId;
				if($this->run_sql($sql)) {
					$updatedContacts[$conId]++;
				}
				else {
					throw new exception(__METHOD__ .": failed to update main email address for contact_id=(". $conId .")");
				}
			}
			
			$this->run_sql("DELETE FROM contact_attribute_link_table WHERE attribute_id=" .
				"(SELECT attribute_id FROM attribute_table WHERE name='email')");
		}
		
		$this->gfObj->debug_print(__METHOD__ .": updatedContacts array::: ". $this->gfObj->debug_print($updatedContacts,0));
		$this->gfObj->debug_print(__METHOD__ .": final transaction level=(". $this->db->get_transaction_level() .")");
		
	}//end run_upgrade()
	//=========================================================================
}

?>
