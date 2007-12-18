<?php
/*
 * 
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 * 
 * TODO: test methods to make sure they work!
 */

//TODO: log everything!

class invoiceItem extends invoice {
	
	protected $gfObj;
	protected $logsObj;
	
	//=========================================================================
	public function __construct(cs_phpDB $db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
		$this->logsObj = new logsClass($this->db, 'Invoice Item');
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function create_item(array $data) {
	}//end create_item()
	//=========================================================================
}
?>
