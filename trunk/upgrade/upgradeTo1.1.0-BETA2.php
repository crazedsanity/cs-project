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
					$updatedContacts[$conId]['setCompany']++;
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
				if($this->run_sql($sql)) {
					$updatedContacts[$conId]['createNewContactEmailId']++;
					
					//get the contact_email_id just inserted, if needs be.
					if(!isset($con2emailId[$conId])) {
						$sql = "SELECT currval('contact_email_table_contact_email_id_seq'::text)";
						
						if($this->run_sql($sql)) {
							$seqData = $this->db->farray();
							$con2emailId[$conId] = $seqData[0];
							$updatedContacts[$conId]['getNewContactEmailId']++;
						}
						else {
							throw new exception(__METHOD__ .": failed to retrieve newly inserted contact_email_id");
						}
					}
				}
				else {
					throw new exception(__METHOD__ .": failed to insert data...??");
				}
			}
			
			foreach($con2emailId as $conId => $emailId) {
				$sql = "UPDATE contact_table SET contact_email_id=". $emailId ." WHERE contact_id=". $conId;
				if($this->run_sql($sql)) {
					$updatedContacts[$conId]['setContactEmailId']++;
				}
				else {
					throw new exception(__METHOD__ .": failed to update main email address for contact_id=(". $conId .")");
				}
			}
			
			$this->run_sql("DELETE FROM contact_attribute_link_table WHERE attribute_id=" .
				"(SELECT attribute_id FROM attribute_table WHERE name='email')");
		}
		
		//check to ensure even ANONYMOUS has an email address...
		if($this->run_sql("SELECT * FROM contact_table WHERE contact_email_id IS NULL")) {
			$data = $this->db->farray_fieldnames('contact_id');
			foreach($data as $conId => $data) {
				$myEmailAddr = "fix_contact_id_". $conId ."@null.com";
				$sql = "INSERT INTO contact_email_table (contact_id, email) VALUES (". $conId .", " .
					"'". $myEmailAddr ."')";
				
				if($this->run_sql($sql)) {
					//now update the contact.
					$updatedContacts[$conId]['fixNullContactEmail__inserts']++;
					
					if($this->run_sql("SELECT currval('contact_email_table_contact_email_id_seq'::text)")) {
						$seqData = $this->db->farray();
						$sql = "UPDATE contact_table SET contact_email_id=". $seqData[0] ." WHERE contact_id=". $conId;
						if($this->run_sql($sql)) {
							$updatedContacts[$conId]['fixNullContactEmail__updates']++;
						}
						else {
							throw new exception(__METHOD__ .": failed to update contact");
						}
					}
					else {
						throw new exception(__METHOD__ .": failed to retrieve id of inserted email address");
					}
				}
				else {
					throw new exception(__METHOD__ .": failed to insert email address");
				}
			}
		}
		
		$this->run_sql("ALTER TABLE contact_table ALTER COLUMN contact_email_id SET NOT NULL;");
		
		$this->gfObj->debug_print(__METHOD__ .": updatedContacts array::: ". $this->gfObj->debug_print($updatedContacts,0));
		$this->gfObj->debug_print(__METHOD__ .": final transaction level=(". $this->db->get_transaction_level() .")");
		
	}//end run_upgrade()
	//=========================================================================
}

?>
