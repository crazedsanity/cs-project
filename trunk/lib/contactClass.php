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
	private $db;
	
	
	//=========================================================================
	public function __construct(cs_phpDB &$db) {
		
		if($db->isConnected()) {
			$this->db = $db;
		}
		else {
			throw new exception(__METHOD__ .": database object not connected");
		}
		
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_all_contacts() {
		
	}//end get_all_contacts()
	//=========================================================================
	
	
}//end contactClass{}
?>
