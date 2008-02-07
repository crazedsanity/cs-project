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

class invoiceTransaction extends invoice {
	
	protected $gfObj;
	protected $logsObj;
	
	private $invoiceId;
	
	const dbTable = 'invoice_transaction_table';
	const dbSeqName = 'invoice_transaction_table_invoice_transaction_id_seq';
	
	//=========================================================================
	public function __construct(cs_phpDB $db, $invoiceId) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
		$this->logsObj = new logsClass($this->db, 'Invoice Transaction');
		$this->set_invoice_id($invoiceId);
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Method to set internal invoiceId value.
	 */
	private function set_invoice_id($invoiceId) {
		if(is_numeric($invoiceId)) {
			$this->invoiceId = $invoiceId;
		}
		else {
			throw new exception(_METHOD__ .': invalid data for invoice!');
		}
	}//end set_invoice_id()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Insert invoice transaction record into the database.
	 */
	public function create_invoice_transaction(array $data) {
		if(is_numeric($this->invoiceId)) {
			$fields = array(
				'auth_string'		=> 'sql',
				'name'				=> 'sql',
				'number'			=> 'sql',
				'trans_date'		=> 'date',
				'amount'			=> 'float'
			);
			
			$insertArr = array();
			foreach($fields as $name=>$cleanType) {
				if(isset($data[$name]) && strlen($this->gfObj->cleanString($data[$name], $cleanType))) {
					$insertArr[$name] = $this->gfObj->cleanString($data[$name], $cleanType);
				}
				else {
					throw new exception(__METHOD__ .': invalid data for '. $name .': ('. $data['name'] .')');
				}
			}
			
			$insertArr['invoice_id'] = $this->invoiceId;
			$sql = 'INSERT INTO '. $this->dbTable .' '. $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			if($this->run_sql($sql) && !strlen($this->lastError)) {
				$retval = $this->get_last_inserted_item();
			}
			else {
				throw new exception(__METHOD__ .': failed to insert item: '. $this->lastError);
			}
		}
		else {
			throw new exception(__METHOD__ .': no invoice_id set!');
		}
	}//end create_invoice_transaction()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Retrieve last invoice_transaction_id inserted.
	 */
	private function get_last_inserted_transaction_id() {
		$sql = "SELECT currval('". $this->dbSeqName ."'::text)";
		if($this->run_sql($sql)) {
			$data = $this->db->farray();
			$retval = $data[0];
		}
		else {
			throw new exception(__METHOD__ .': failed to retrieve last invoice_transaction_id: '. $this->lastError);
		}
		
		return($retval);
	}//end get_last_inserted_transaction_id()
	//=========================================================================
}
?>
