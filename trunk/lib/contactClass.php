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
	public function get_contact_attributes($contactId) {
		$retval = NULL;
		if(is_numeric($contactId)) {
			$sql = "select a.name, cal.attribute_value FROM contact_attribute_link_table " .
				"AS cal INNER JOIN attribute_table AS a USING (attribute_id) WHERE  " .
				"cal.contact_id=". $contactId ." ORDER BY name";
			
			if($this->run_sql($sql)) {
				$retval = $this->db->farray_nvp('name', 'attribute_value');
			}
		}
		
		return($retval);
	}//end get_contact_attributes()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_all_contacts(array $critArr=NULL, array $primaryOrder=NULL, array $filterArr=NULL) {
		$sqlArr = array(
			'contact_id'	=> 'int',
			'fname'			=> 'sql',
			'lname'			=> 'sql'
		);
		
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
	
	
}//end contactClass{}
?>	
