<?
/*
 * SVN INFORMATION:::
 * SVN Signature: $Id$
 * Last Committted Date: $Date$
 * Last Committed Path: $HeadURL$
 */


switch ($DataBaseReference){

	default:
		$this->hostName		= DB_HOST;  
		$this->port			= DB_PORT;
		$this->userName		= DB_USER;
		$this->password		= DB_PASS;	
		$this->databaseName	= DB_NAME;
}

?>
