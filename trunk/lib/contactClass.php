<?php
/*
 * Created on Oct 15, 2007
 * 
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 * 
 */

require_once(dirname(__FILE__) .'/attributeClass.php');

class contactClass extends attributeClass {
	
	protected $contactId;
	
	private $logsObj;
	
	//=========================================================================
	public function __construct(cs_phpDB &$db) {
		
		if($db->is_connected()) {
			$this->db = $db;
		}
		else {
			$details = __METHOD__ .": database object not connected";
		}
		
		$this->logsObj = new logsClass($this->db, 'Contact');
		
		parent::__construct($db);
		
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function set_contact_id($contactId) {
		if(is_numeric($contactId)) {
			$this->contactId = $contactId;
		}
		else {
			$this->contactId = NULL;
		}
		
		return($this->contactId);
	}//end set_contact_id()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_contact_attributes() {
		$retval = array();
		if(is_numeric($this->contactId)) {
			$sql = "select a.name, cal.attribute_value FROM contact_attribute_link_table " .
				"AS cal INNER JOIN attribute_table AS a USING (attribute_id) WHERE  " .
				"cal.contact_id=". $this->contactId ." ORDER BY name";
			
			if($this->run_sql($sql)) {
				$retval = $this->db->farray_nvp('name', 'attribute_value');
			}
		}
		else {
			$details = __METHOD__ .": contactId isn't set (". $this->contactId .")";
			$this->logsObj->log_by_class($details, 'error');
			throw new exception($details);
		}
		
		return($retval);
	}//end get_contact_attributes()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_all_contacts(array $critArr=NULL, array $primaryOrder=NULL, array $filterArr=NULL) {
		$sql = "SELECT c.contact_id, c.company, c.fname, c.lname, ce.email " .
			"FROM contact_table AS c INNER JOIN contact_email_table AS ce " .
			"USING (contact_email_id) ";
		
		if(is_null($primaryOrder)) {
			$primaryOrder = array("company", "fname");
		}
		$sql .= $this->gfObj->string_from_array($primaryOrder, 'order');
			
		if($this->run_sql($sql)) {
			$retval = array();
			$data = $this->db->farray_fieldnames('contact_id', NULL, 0);
			
			foreach($data as $conId=>$subData) {
				$this->set_contact_id($conId);
				$attribArr = $this->get_contact_attributes($conId);
				if(is_array($attribArr)) {
					$value = array_merge($attribArr, $subData);
				}
				else {
					$value = $subData;
				}
				ksort($subData);
				
				$retval[$conId] = $value;
			}
		}
		else {
			$details = __METHOD__ .": failed to run SQL, numrows=(". $this->lastNumrows ."), dberror::: ". $this->lastError;
			$this->logsObj->log_by_class($details, 'error');
			throw new exception($details);
		}
		
		return($retval);			
		
	}//end get_all_contacts()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_contact() {
		$myError = NULL;
		if(is_numeric($this->contactId)) {
			$sql = "SELECT c.contact_id, c.company, c.fname, c.lname, c.contact_email_id, ce.email " .
				"FROM contact_table AS c INNER JOIN contact_email_table AS ce USING (contact_email_id) " .
				"WHERE c.contact_id=". $this->contactId;
			
			if($this->run_sql($sql)) {
				$retval = $this->db->farray_fieldnames();
				
				if(isset($retval['fname'])) {
					$attribs = $this->get_contact_attributes($this->contactId);
					if(is_array($attribs)) {
						$retval = array_merge($attribs, $retval);
					}
					ksort($retval);
				}
				else {
					$myError = __METHOD__ .": array is invalidly formatted::: ". $this->gfObj->debug_print($retval,0);
				}
			}
			else {
				$myError = __METHOD__ .": no contact found for contact_id=(". $this->contactId .")";
			}
		}
		else {
			$myError = __METHOD__ .": contactId is not valid (". $this->contactId .")";
		}
		
		if(!is_null($myError)) {
			$this->logsObj->log_by_class($myError, 'error');
		}
		
		return($retval);
	}//end get_contact()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Update the given attribute for the current user with the given value.
	 */
	public function update_contact_attribute($attribName, $value, $logIt=TRUE) {
		if(strlen($attribName)) {
			$attribData = $this->get_attribute_data($attribName);
			
			$criteria = array(
				'contact_id'	=> $this->contactId,
				'attribute_id'	=> $attribData['attribute_id']
			);
			$update = array(
				'attribute_value'	=> $this->gfObj->cleanString($value, 'sql',1)
			);
			$sql = "UPDATE contact_attribute_link_table SET ". 
				$this->gfObj->string_from_array($update, 'update') ." WHERE " .
				$this->gfObj->string_from_array($criteria, 'select');
				
			if($this->run_sql($sql)) {
				$retval = TRUE;
				if($logIt) {
					$this->logsObj->log_by_class("Updated attribute ". $attribData['name'] ." with new value (". $value .")", 'update');
				}
			}
			else {
				$this->logsObj->log_dberror(__METHOD__ .": run SQL...");
				$retval = FALSE;
			}
		}
		else {
			$details = __METHOD__ .": failed to update attribute...?";
			$this->logsObj->log_by_class($details, 'error');
			throw new exception($details);
		}
		
		return($retval);
	}//end update_contact_attribute()
	//=========================================================================
	
	
	
	//=========================================================================
	public function mass_update_contact_attributes(array $nameToValue) {
		$retval = 0;
		$details = "Updated contact attributes: ";
		foreach($nameToValue as $name => $value) {
			$retval += $this->update_contact_attribute($name, $value, FALSE);
			$details .= "\nSet ". $name ." with value (". $value .")";
		}
		$details .= "\n\nFinal result: (". $retval .")";
		
		$this->logsObj->log_by_class($details, 'update');
		
		return($retval);
	}//end mass_update_contact_attributes()
	//=========================================================================
	
	
	
	//=========================================================================
	public function create_contact_attribute($name, $value) {
		$retval = FALSE;
		$myError = NULL;
		if(is_numeric($this->contactId) && strlen($name)) {
			$attributeData = $this->get_attribute_data($name);
			if(is_array($attributeData) && count($attributeData) > 0) {
				$insertArr = array(
					'contact_id'		=> $this->contactId,
					'attribute_id'		=> $attributeData['attribute_id'],
					'attribute_value'	=> $this->gfObj->cleanString($value, 'sql')
				);
				
				$sql = "INSERT INTO contact_attribute_link_table ". 
					$this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
				if($this->run_sql($sql)) {
					$retval = TRUE;
				}
				else {
					$myError = __METHOD__ .': failed to create new attribute';
				}
			}
			else {
				$myError = __METHOD__ .': failed to retreive attribute data for ('. $name .')';
			}
		}
		else {
			cs_debug_backtrace();
			$myError = __METHOD__ .": insufficient information";
		}
		
		if(!is_null($myError)) {
			$this->logsObj->log_by_class($myError, 'error');
			throw new exception($myError);
		}
		
		return($retval);
	}//end create_contact_attribute()
	//=========================================================================
	
	
	
	//=========================================================================
	public function delete_contact_attribute($name) {
		$retval = FALSE;
		$myError = NULL;
		if(strlen($name)) {
			$attribData = $this->get_attribute_data($name);
			$crit = array(
				'contact_id'	=> $this->contactId,
				'attribute_id'	=> $attribData['attribute_id']
			);
			$sql = "DELETE FROM contact_attribute_link_table WHERE ". 
				$this->gfObj->string_from_array($crit, 'select', NULL, 'int');
				
			if($this->run_sql($sql)) {
				$retval = TRUE;
			}
			else {
				$myError = __METHOD__ .': failed to run delete SQL...';
			}
		}
		else {
			$myError = __METHOD__ .": failed to delete contact attribute (". $name .")";
		}
		
		if(!is_null($myError)) {
			$this->logsObj->log_by_class($myError, 'error');
			throw new exception($myError);
		}
		
		return($retval);
	}//end delete_contact_attribute()
	//=========================================================================
	
	
	
	//=========================================================================
	public function update_contact_data(array $updates) {
		$retval = FALSE;
		$myError = NULL;
		if(is_numeric($this->contactId)) {
			$sql = "UPDATE contact_table SET ". $this->gfObj->string_from_array($updates, 'update', NULL, 'sql') .
				" WHERE contact_id=". $this->contactId;
			
			if($this->run_sql($sql)) {
				$retval = TRUE;
			}
			else {
				$myError = __METHOD__ .": failed to update contact";
			}
		}
		else {
			$myError = __METHOD__ .": invalid contact_id";
		}
		
		if(!is_null($myError)) {
			$this->logsObj->log_by_class($myError, 'error');
			throw new exception($myError);
		}
		
		return($retval);
	}//end update_contact_data();
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_contact_email_list() {
		$retval = array();
		$myError = NULL;
		if(is_numeric($this->contactId)) {
			$sql = "SELECT contact_email_id, email FROM contact_email_table " .
				"WHERE contact_id=". $this->contactId;
			if($this->run_sql($sql) && $this->lastNumrows > 0) {
				$retval = $this->db->farray_nvp('contact_email_id', 'email');
			}
			else {
				$myError = __METHOD__ .": failed to retrieve list of contacts email addresses";
			}
		}
		else {
			$myError = __METHOD__ .": invalid contact_id";
		}
		
		return($retval);
	}//end get_contact_email_list()
	//=========================================================================
	
	
	
	//=========================================================================
	public function create_contact_email($newEmail, $isPrimary=FALSE) {
		$myError = NULL;
		if(is_numeric($this->contactId)) {
			if(strlen($newEmail) > 5 && preg_match('/@/', $newEmail)) {
				$sql = "INSERT INTO contact_email_table (contact_id, email) VALUES (". $this->contactId ."," .
					" '". $this->gfObj->cleanString($newEmail, 'email') ."');";
				
				if($this->run_sql($sql)) {
					$logDetails = "Successfully created new email address (". $newEmail .")";
					//sweet: get the newly inserted id.
					$sql = "SELECT currval('contact_email_table_contact_email_id_seq'::text)";
					if($this->run_sql($sql)) {
						$data = $this->db->farray();
						$retval = $data[0];
						if($isPrimary) {
							$this->update_contact_data(array('contact_email_id' => $retval));
							$logDetails .= " and set as primary";
						}
						$this->logsObj->log_by_class($logDetails, 'update');
					}
					else {
						$myError = __METHOD__ .": failed to retrieve newly inserted contact_email_id";
					}
				}
				else {
					$myError = __METHOD__ .": failed to create new contact email address";
				}
			}
			else {
				//don't set $myError (no need to throw an exception), but log it.
				$this->logsObj->log_by_class(__METHOD__ .": zero-length or invalid email (". $newEmail .")", 'error');
				$retval = FALSE;
			}
		}
		else {
			$myError = __METHOD__ .": invalid contact_id";
		}
		
		if(!is_null($myError)) {
			$this->logsObj->log_by_class($myError, 'error');
			throw new exception($myError);
		}
		
		return($retval);
	}//end create_contact_email()
	//=========================================================================
	
	
}//end contactClass{}
?>	
