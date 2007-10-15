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
	
	abstract public function __construct(cs_phpDB &$db);
	
	
	//=========================================================================
	final public function run_sql($sql) {
		
		if(strlen($sql)) {
			$this->lastError = $this->db->exec($sql);
			$this->lastNumrows = $this->db->errorMsg();
			
			if(!strlen($this->lastError) && $this->lastNumrows > 0) {
				$retval = TRUE;
			}
			else {
				$retval = FALSE;
			}
			
		}
		else {
			throw new exception(__METHOD__ .": no sql to run (". $sql .")");
		}
		
		return($retval);
	}//end run_sql()
	//=========================================================================
	
}
?>