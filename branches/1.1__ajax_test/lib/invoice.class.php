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
 */

//TODO: log everything!

class invoice extends dbAbstract {
	
	protected $gfObj;
	protected $logsObj;
	
	//=========================================================================
	public function __construct(cs_phpDB $db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
		$this->logsObj = new logsClass($this->db, 'Authentication Token');
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function add_item(invoiceItem $item) {
	}//end add_item()
	//=========================================================================
}
?>
