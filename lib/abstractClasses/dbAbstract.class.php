<?php

/*
 * Created on 10/15/2007
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


abstract class dbAbstract {
	
	public $lastError=NULL;
	public $lastNumrows = NULL;
	public $db;
	
	protected $fsObj;
	protected $lastSQLFile;
	
	
	
	//=========================================================================
	/**
	 * PHP5 Classes ONLY! ;) 
	 */
	abstract public function __construct(cs_phpDB &$db);
	//=========================================================================
	
	
	
	//=========================================================================
	public function run_sql($sql) {
		
		if(strlen($sql)) {
			$this->lastNumrows = $this->db->exec($sql);
			$this->lastError = $this->db->errorMsg();
			
			if(!strlen($this->lastError) && $this->lastNumrows > 0) {
				$retval = TRUE;
			}
			else {
				if(strlen($this->lastError)) {
					throw new exception(__METHOD__ .": ". $this->lastError ."<BR>\nSQL::: ". $sql);
				}
				$retval = FALSE;
			}
			
		}
		else {
			throw new exception(__METHOD__ .": no sql to run (". $sql .")");
		}
		
		return($retval);
	}//end run_sql()
	//=========================================================================
	
	
	
	//=========================================================================
	final public function run_sql_file($filename) {
		if(!is_object($this->fsObj)) {
			$this->fsObj = new cs_fileSystem;
		}
		
		$this->lastSQLFile = $filename;
		
		$fileContents = $this->fsObj->read($filename);
		$this->db->beginTrans(__METHOD__);
		try {
			$this->run_sql($fileContents);
			$this->db->commitTrans();
			$retval = TRUE;
		}
		catch(exception $e) {
			$this->db->rollbackTrans();
			$retval = FALSE;
		}
		
		return($retval);
	}//end run_sql_file()
	//=========================================================================
	
}
?>