<?php
/*
 * Created on Oct 24, 2007
 * 
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */


class attributeClass extends dbAbstract {
	
	private $logsObj;
	
	protected $gfObj;
	
	//=========================================================================
	public function __construct(cs_phpDB &$db) {
		
		$this->logsObj = new logsClass($this->db, "Attributes");
		
		$this->gfObj = new cs_globalFunctions;
		
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	private function check_attribute_name($methodName, &$name) {
		$retval = FALSE;
		$name = strtolower($name);
		if(is_string($name) && strlen($name) > 2) {
			$retval = TRUE;
		}
		else {
			$details = $methodName .": attribute name is invalid (". $name .")";
			$this->logsObj->log_by_class($details, 'error');
			throw new exception($details);
		}
		
		return($retval);
	}//end check_attribute_name()
	//=========================================================================
	
	
	
	//=========================================================================
	public function attribute_get_create($name) {
		$this->check_attribute_name(__METHOD__, $name);
		
		$retval = $this->get_attribute($name);
		if($retval === FALSE) {
			$retval = $this->create_attribute($name);
		}
		
		return($retval);
	}//end attribute_get_create($name)
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_attribute($name) {
		
		$this->check_attribute_name(__METHOD__, $name);
		if($this->run_sql("SELECT attribute_id FROM attribute_table WHERE lower(name) = '". $name ."'")) {
			$data = $this->db->farray();
			$retval = $data[0];
		}
		else {
			$this->logsObj->log_dberror(_METHOD__ .": ". $this->lastError);
			$retval = FALSE;
		}
		
		return($retval);
	}//end get_attribute()
	//=========================================================================
	
	
	
	//=========================================================================
	public function create_attribute($name, $displayName=NULL) {
		
		if(is_null($displayName)) {
			if(preg_match('/:/', $name)) {
				$tmp = explode(':', $name);
				$displayName = strtoupper($tmp[0]) .":". $tmp[1];
			}
			else {
				$displayName = ucwords($name);
			}
		}
		$this->check_attribute_name(__METHOD__, $name);
		$sql = "INSERT INTO attribute_table (name, display_name) VALUES ('". $name ."', '". $displayName ."');";
		if($this->run_sql($sql)) {
			//it worked: get the new id.
			if($this->run_sql("SELECT currval('attribute_table_attribute_id_seq'::text)")) {
				$data = $this->db->farray();
				$retval = $data[0];
			}
			else {
				$details = __METHOD__ .": failed to retrieve attribute_id of newly inserted record";
				$this->logsObj->log_by_class($details, 'error');
				throw new exception($details);
			}
		}
		else {
			$details = __METHOD__ .": failed to create new attribute";
			$this->logsObj->log_by_class($details, 'error');
			throw new exception($details);
		}
		
		return($retval);
	}//end create_attribute()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_attribute_data($attribName) {
		$myError = NULL;
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
						$myError = __METHOD__ .": created attribute, but failed to retrieve it...??";
					}
				}
				else {
					$myError = __METHOD__ .": failed to create attribute...?";
				}
			}
		}
		else {
			$myError = __METHOD__ .": attribute name has no length (". $attribName .")";
		}
		
		if(!is_null($myError)) {
			$this->logsObj->log_by_class($myError, 'error');
			throw new exception($myError);
		}
		
		return($retval);
	}//end get_attribute_data()
	//=========================================================================
	
	
	
	//=========================================================================
	private function clean_attribute_name($name) {
		if(strlen($name)) {
			$retval = $this->gfObj->cleanString(strtolower($name), 'sql');
		}
		else {
			$details = __METHOD__ .": invalid attribute name given (". $name .")";
			$this->logsObj->log_by_class($details, 'error');
			throw new exception($details);
		}
		
		return($retval);
	}//end clean_attribute_name()
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
				$details = __METHOD__ .": contactId required for type (". $type .")";
				throw new exception($details);
			}
			settype($type, 'int');
		}
		
		$retval = array();
		$sql = "SELECT attribute_id, display_name FROM attribute_table ";
		switch($type) {
			case 1: {
				$sql .= "WHERE attribute_id IN (SELECT distinct attribute_id FROM contact_attribute_link_table " .
					"WHERE contact_id=". $this->contactId .")";
			}
			break;
			
			case 2: {
				$sql .= "WHERE attribute_id NOT IN (SELECT distinct attribute_id FROM contact_attribute_link_table " .
					"WHERE contact_id=". $this->contactId .")";
			}
			break;
		}
		$sql .= " ORDER BY name";
		
		if($this->run_sql($sql)) {
			$retval = $this->db->farray_nvp('attribute_id', 'display_name');
		}
		elseif(strlen($this->lastError)) {
			$details = __METHOD__ .": failed to retrieve attribute list: ". $this->lastError;
			$this->logsObj->log_by_class($details, 'error');
			throw new exception($details);
		}
		
		return($retval);
	}//end get_attribute_list()
	//=========================================================================
	
}//end attributeClass

?>
