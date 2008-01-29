<?php
/*
 * This class was built to handle creation, searching, and updates of invoices. 
 * For searches involving items on the invoice, use invoiceItem{}.
 * invoices is handled by 
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


class invoiceViewer extends invoice {
	
	protected $gfObj;
	protected $logsObj;
	
	
	
	//=========================================================================
	public function __construct(cs_phpDB $db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
		$this->logsObj = new logsClass($this->db, 'Invoice Viewer');
	}//end __construct()
	//=========================================================================
}
?>
