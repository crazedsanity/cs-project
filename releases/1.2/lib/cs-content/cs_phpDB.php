<?php

/*
 * A class for generic PostgreSQL database access.
 * 
 * SVN INFORMATION:::
 * SVN Signature:::::::: $Id: cs_phpDB.php 267 2008-04-29 15:27:28Z crazedsanity $
 * Last Committted Date: $Date: 2008-04-29 10:27:28 -0500 (Tue, 29 Apr 2008) $
 * Last Committed Path:: $HeadURL: https://cs-content.svn.sourceforge.net/svnroot/cs-content/trunk/0.10/cs_phpDB.php $
 * 
 */

///////////////////////
// ORIGINATION INFO:
// 		Author: Trevin Chow (with contributions from Lee Pang, wleepang@hotmail.com)
// 		Email: t1@mail.com
// 		Date: February 21, 2000
// 		Last Updated: August 14, 2001
//
// 		Description:
//  		Abstracts both the php function calls and the server information to POSTGRES
//  		databases.  Utilizes class variables to maintain connection information such
//  		as number of rows, result id of last operation, etc.
//
///////////////////////

require_once(dirname(__FILE__) ."/cs_versionAbstract.class.php");

class cs_phpDB extends cs_versionAbstract {
	
	private $dbLayerObj;
	private $dbType;
	
	//=========================================================================
	public function __construct($type='pgsql') {
		
		if(strlen($type)) {
			
			require_once(dirname(__FILE__) .'/db_types/'. __CLASS__ .'__'. $type .'.class.php');
			$className = __CLASS__ .'__'. $type;
			$this->dbLayerObj = new $className;
			$this->dbType = $type;
			
			$this->gfObj = new cs_globalFunctions;
			
			if(defined('DEBUGPRINTOPT')) {
				$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
			}
			
			$this->isInitialized = TRUE;
		}
		else {
			throw new exception(__METHOD__ .": failed to give a type (". $type .")");
		}
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Magic method to call methods within the database abstraction layer ($this->dbLayerObj).
	 */
	public function __call($methodName, $args) {
		if(method_exists($this->dbLayerObj, $methodName)) {
			$retval = call_user_func_array(array($this->dbLayerObj, $methodName), $args);
		}
		else {
			throw new exception(__METHOD__ .': unsupported method ('. $methodName .') for database of type ('. $this->dbType .')');
		}
		return($retval);
	}//end __call()	
	//=========================================================================
	
} // end class phpDB

?>
