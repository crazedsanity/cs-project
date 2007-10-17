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
		$retval = NULL;
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
		$sql = "SELECT contact_id, fname, lname FROM contact_table ". 
			$this->gfObj->string_from_array($primaryOrder, 'order');
			
		if($this->run_sql($sql)) {
			$retval = array();
			$data = $this->db->farray_fieldnames('contact_id', NULL, 0);
			
			foreach($data as $conId=>$subData) {
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
			$sql = "SELECT contact_id, fname, lname FROM contact_table WHERE contact_id=". $this->contactId;
			
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
		
		debug_print($retval,1);
		exit;
		
		return($retval);
	}//end get_contact()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Update the given attribute for the current user with the given value.
	 */
	public function update_attribute($attribName, $value) {
		if(strlen($attribName)) {
			$attribData = $this->get_attribute_data($attribName);
			
			$criteria = array(
				'contact_id'	=> $this->contactId,
				'attribute_id'	=> $attribData['attribute_id']
			);
			$update = array(
				'attribute_value'	=> $this->cleanString($value, $attribData['clean_as'])
			);
			$sql = "UPDATE contact_attribute_link_table SET ". 
				$this->gfObj->string_from_array($update, 'update') ." WHERE " .
				$this->gfObj->string_from_array($criteria, 'select');
				
debug_print($sql);
exit;
		}
		else {
			throw new exception(__METHOD__ .": failed to update attribute...?");
		}
		
		return($retval);
	}//end update_attribute()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_attribute_data($attribName) {
		$attribName = $this->clean_attribute_name($attribName);
		
		$sql = "SELECT * FROM attribute_table WHERE attribute_name='". $attribName ."'";
		if($this->run_sql($sql)) {
			$retval = $this->db->farray_fieldnames('attribute_id', NULL, 0);
		}
		else {
			//okay, so try creating, then retrieving it.
			if($this->create_attribute($attribName)) {
				if($this->run_sql($sql)) {
					$retval = $this->db->farray_fieldnames('attribute_id', NULL, 0);
				}
				else {
					throw new exception(__METHOD__ .": created attribute, but failed to retrieve it...??");
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to create attribute...?");
			}
		}
		
		return($retval);
	}//end get_attribute_data()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_attribute($attribName, $cleanAs='sql') {
		$attribName = $this->clean_attribute_name($attribName);
		
		$insertArr = array(
			'attribute_name'	=> $attribName,
			'clean_as'			=> $cleanAs
		);
		$sql = "INSERT INTO attribute_table ". 
			$this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
		
		if($this->run_sql($sql)) {
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
	
	
}//end contactClass{}
?>	
