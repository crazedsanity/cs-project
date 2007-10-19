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

require_once(dirname(__FILE__) .'/abstractClasses/dbAbstract.class.php');

class contactClass extends dbAbstract {
	
	private $contactId;
	
	private $gfObj;
	
	
	//=========================================================================
	public function __construct(cs_phpDB &$db) {
		
		if($db->is_connected()) {
			$this->db = $db;
		}
		else {
			throw new exception(__METHOD__ .": database object not connected");
		}
		
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = 1;
		
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
			throw new exception(__METHOD__ .": contactId isn't set (". $this->contactId .")");
		}
		
		return($retval);
	}//end get_contact_attributes()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_all_contacts(array $critArr=NULL, array $primaryOrder=NULL, array $filterArr=NULL) {
		$sql = "SELECT c.contact_id, c.company, c.fname, c.lname, ce.email " .
			"FROM contact_table AS c INNER JOIN contact_email_table AS ce " .
			"USING (contact_email_id) ". 
		$this->gfObj->string_from_array($primaryOrder, 'order');
			
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
			throw new exception(__METHOD__ .": failed to run SQL, numrows=(". $this->lastNumrows ."), dberror::: ". $this->lastError);
		}
		
		return($retval);			
		
	}//end get_all_contacts()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_contact() {
		if(is_numeric($this->contactId)) {
			$sql = "SELECT c.contact_id, c.company, c.fname, c.lname, ce.email " .
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
					throw new exception(__METHOD__ .": array is invalidly formatted::: ". $this->gfObj->debug_print($retval,0));
				}
			}
			else {
				throw new exception(__METHOD__ .": no contact found for contact_id=(". $this->contactId .")");
			}
		}
		else {
			throw new exception(__METHOD__ .": contactId is not valid (". $this->contactId .")");
		}
		
		return($retval);
	}//end get_contact()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Update the given attribute for the current user with the given value.
	 */
	public function update_contact_attribute($attribName, $value) {
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
			}
			else {
				$retval = FALSE;
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to update attribute...?");
		}
		
		return($retval);
	}//end update_contact_attribute()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_attribute_data($attribName) {
		if(strlen($attribName) && strlen($this->clean_attribute_name($attribName))) {
			$attribName = $this->clean_attribute_name($attribName);
			
			if(is_numeric($attribName)) {
				$sql = "SELECT * FROM attribute_table WHERE attribute_id=". $attribName;
			}
			else {
				$sql = "SELECT * FROM attribute_table WHERE name='". $attribName ."'";
			}
			if($this->run_sql($sql)) {
				$retval = $this->db->farray_fieldnames();
			}
			else {
				//okay, so try creating, then retrieving it.
				if($this->create_attribute($attribName)) {
					if($this->run_sql($sql)) {
						$retval = $this->db->farray_fieldnames();
					}
					else {
						throw new exception(__METHOD__ .": created attribute, but failed to retrieve it...??");
					}
				}
				else {
					throw new exception(__METHOD__ .": failed to create attribute...?");
				}
			}
		}
		else {
			throw new exception(__METHOD__ .": attribute name has no length (". $attribName .")");
		}
		
		return($retval);
	}//end get_attribute_data()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_attribute($attribName, $cleanAs='sql') {
		$attribName = $this->clean_attribute_name($attribName);
		
		$insertArr = array(
			'name'	=> $attribName,
			'clean_as'			=> $cleanAs
		);
		$sql = "INSERT INTO attribute_table ". 
			$this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
		
		if($this->run_sql($sql)) {
			//now retrieve data about this attribute...
			$retval = $this->get_attribute_data($attribName);
		}
		else {
			//didn't insert...?
			throw new exception(__METHOD__ .": failed to create attribute (". $attribName .")");
		}
		
		return($retval);
	}//end create_attribute()
	//=========================================================================
	
	
	
	//=========================================================================
	private function clean_attribute_name($name) {
		if(strlen($name)) {
			$retval = $this->gfObj->cleanString(strtolower($name), 'email_plus_spaces');
		}
		else {
			throw new exception(__METHOD__ .": invalid attribute name given (". $name .")");
		}
		
		return($retval);
	}//end clean_attribute_name()
	//=========================================================================
	
	
	
	//=========================================================================
	public function mass_update_contact_attributes(array $nameToValue) {
		$retval = 0;
		foreach($nameToValue as $name => $value) {
			$retval += $this->update_contact_attribute($name, $value);
		}
		
		return($retval);
	}//end mass_update_contact_attributes()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Get list of attribute_id => name
	 * 
	 * TYPES: 
	 * NULL = all
	 * 1	= attributes associated w/current contact
	 * 2	= attributes NOT associated w/current contact
	 */
	public function get_attribute_list($type=NULL) {
		if(!is_null($type)) {
			if(!is_numeric($this->contactId)) {
				throw new exception(__METHOD__ .": contactId required for type (". $type .")");
			}
			settype($type, 'int');
		}
		
		$retval = array();
		$sql = "SELECT attribute_id, name FROM attribute_table ";
		switch($type) {
			case 1: {
				$sql .= "WHERE attribute_id IN (SELECT distinct attribute_id FROM contact_attribute_link_table)";
			}
			break;
			
			case 2: {
				$sql .= "WHERE attribute_id NOT IN (SELECT distinct attribute_id FROM contact_attribute_link_table)";
			}
			break;
			
			default:
				$sql .= " ORDER BY name";
		}
		
		if($this->run_sql($sql)) {
			$retval = $this->db->farray_nvp('attribute_id', 'name');
		}
		
		return($retval);
	}//end get_attribute_list()
	//=========================================================================
	
	
	
	//=========================================================================
	public function create_contact_attribute($name, $value) {
		$retval = FALSE;
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
					throw new exception(__METHOD__ .': failed to create new attribute');
				}
			}
			else {
				throw new exception(__METHOD__ .': failed to retreive attribute data for ('. $name .')');
			}
		}
		else {
			cs_debug_backtrace();
			throw new exception(__METHOD__ .": insufficient information");
		}
		
		return($retval);
	}//end create_contact_attribute()
	//=========================================================================
	
	
	
	//=========================================================================
	public function delete_contact_attribute($name) {
		$retval = FALSE;
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
				throw new exception(__METHOD__ .': failed to run delete SQL...');
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to delete contact attribute (". $name .")");
		}
		
		return($retval);
	}//end delete_contact_attribute()
	//=========================================================================
	
	
	
	//=========================================================================
	public function update_contact_data(array $updates) {
		$retval = FALSE;
		if(is_numeric($this->contactId)) {
			$sql = "UPDATE contact_table SET ". $this->gfObj->string_from_array($updates, 'update', NULL, 'sql') .
				" WHERE contact_id=". $this->contactId;
			
			if($this->run_sql($sql)) {
				$retval = TRUE;
			}
			else {
				throw new exception(__METHOD__ .": failed to update contact");
			}
		}
		else {
			throw new exception(__METHOD__ .": invalid contact_id");
		}
		
		return($retval);
	}//end update_contact_data();
	//=========================================================================
	
	
}//end contactClass{}
?>	
