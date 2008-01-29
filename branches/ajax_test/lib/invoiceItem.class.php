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
	
	const itemTable = 'invoice_item_table';
	const itemTableSeq = 'invoice_item_table_invoice_item_id_seq';
	
	private $invoiceId;
	
	//=========================================================================
	public function __construct(cs_phpDB $db, $invoiceId=NULL) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
		$this->logsObj = new logsClass($this->db, 'Invoice Item');
		
		$this->set_invoice_id($invoiceId);
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Set value of internal invoiceId.
	 */
	protected function set_invoice_id($invoiceId) {
		if(is_numeric($invoiceId)) {
			$this->invoiceId = $invoiceId;
		}
		else {
			throw new exception(__METHOD__ .': invalid data ('. $invoiceId .')');
		}
	}//end set_invoice_id()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Insert item into the database.
	 */
	public function insert_item(array $data) {
		if(is_numeric($this->invoiceId)) {
			$fields = array(
				'description'	=> 'sql',
				'unit_price'	=> 'float',
				'quantity'		=> 'int'
			);
			
			$invoiceArr = array();
			foreach($fields as $name=>$cleanType) {
				if(isset($data[$name]) && strlen($this->gfObj->cleanString($data[$name], $cleanType))) {
					$insertArr[$name] = $this->gfObj->cleanString($data[$name], $cleanType);
				}
				else {
					throw new exception(__METHOD__ .': invalid data for '. $name .': ('. $data['name'] .')');
				}
			}
			
			$insertArr['invoice_id'] = $this->invoiceId;
			$sql = 'INSERT INTO '. $this->itemTable .' '. $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			if($this->run_sql($sql) && !strlen($this->lastError)) {
				$retval = $this->get_last_inserted_item();
			}
			else {
				throw new exception(__METHOD__ .': failed to insert item: '. $this->lastError);
			}
		}
		else {
			throw new exception(__METHOD__ .': no invoiceId set!');
		}
		
		return($retval);
	}//end insert_item()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_last_inserted_item() {
		$sql = "SELECT currval('". $this->itemTableSeq ."'::text)";
		if($this->run_sql($sql)) {
			$data = $this->db->farray();
			$retval = $data[0];
		}
		else {
			throw new exception(__METHOD__ .': failed to retrieve last invoice_item_id: '. $this->lastError);
		}
		
		return($retval);
	}//end get_last_inserted_item()
	//=========================================================================
}
?>
