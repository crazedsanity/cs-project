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

class invoice extends dbAbstract {
	
	protected $gfObj;
	protected $logsObj;
	
	private $invoiceId = NULL;
	private $creatorContactId = NULL;
	private $billingContactId = NULL;
	
	//set some internal things that should NEVER be changed after initialization.
	const mainTable = 'invoice_table';
	const mainTableSeq = 'invoice_table_invoice_id_seq';
	
	const itemTable = 'invoice_item_table';
	const itemTableSeq = 'invoice_item_table_invoice_item_id_seq';
	
	const transTable = 'invoice_transaction_table';
	const transTableSeq = 'invoice_transaction_table_invoice_transaction_id_seq';
	
	//=========================================================================
	public function __construct(cs_phpDB $db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
		$this->logsObj = new logsClass($this->db, 'Invoice');
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Method to handle creating the main invoice record.
	 */
	public function create_invoice(array $invoiceData, $isProforma=FALSE) {
		$cleanFields = array(
			'poc','company', 'address1', 'address2', 'phone', 'fax', 'city',
			'state', 'zip'
		);
		
		$insertArr = array();
		foreach($invoiceData as $name=>$value) {
			$insertArr[$name] = $this->gfObj->cleanString($value, 'sql');
		}
		
		if($isProforma === TRUE) {
			$insertArr['is_proforma'] = 't';
		}
		
		$insertString = $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
		
		$sql = "INSERT INTO ". $this->mainTable .' '. $insertString;
		
		if($this->run_sql($sql)) {
			//pull the new invoice id.
			$retval = $this->get_inserted_invoice_id();
		}
		else {
			throw new exception(__METHOD__ .': failed to insert new invoice: '. $this->lastError ."<BR>\nSQL::: ". $sql);
		}
		
		return($retval);
		
	}//end create_invoice()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Add a line item to the invoice; should be an instance of invoiceItem{}.
	 */
	public function add_item(invoiceItem $item) {
	}//end add_item()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Retrieve the invoice_id that was last inserted.
	 */
	private function get_inserted_invoice_id() {
		$sql = "SELECT currval('". $this->mainTableSeq ."'::text)";
		if($this->run_sql($sql)) {
			$data = $this->db->farray();
			$retval = $data[0];
		}
		else {
			throw new exception(__METHOD__ .": failed to retrieve last invoice_id: ". $this->lastError);
		}
		
		return($retval);
	}//end get_inserted_invoice_id()
	//=========================================================================
}
?>
