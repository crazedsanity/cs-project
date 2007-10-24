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
	
	//=========================================================================
	public function __construct(cs_phpDB &$db) {
		
		$this->db = $db;
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
			throw new exception($methodName .": attribute name is invalid (". $name .")");
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
			$retval = FALSE;
		}
		
		return($retval);
	}//end get_attribute()
	//=========================================================================
	
	
	
	//=========================================================================
	public function create_attribute($name) {
		
		$this->check_attribute_name(__METHOD__, $name);
		$sql = "INSERT INTO attribute_table (name) VALUES ('". $name ."');";
		if($this->run_sql($sql)) {
			//it worked: get the new id.
			if($this->run_sql("SELECT currval('attribute_table_attribute_id_seq'::text)")) {
				$data = $this->db->farray();
				$retval = $data[0];
			}
			else {
				throw new exception(__METHOD__ .": failed to retrieve attribute_id of newly inserted record");
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to create new attribute");
		}
		
		return($retval);
	}//end create_attribute()
	//=========================================================================
	
}//end attributeClass

?>
