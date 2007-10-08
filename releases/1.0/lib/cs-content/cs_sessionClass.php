<?php
/*
 * FILE INFORMATION:
 * $HeadURL: https://cs-content.svn.sourceforge.net/svnroot/cs-content/releases/0.9.0/cs_sessionClass.php $
 * $Id: cs_sessionClass.php 161 2007-09-19 02:49:28Z crazedsanity $
 * $LastChangedDate: 2007-09-18 21:49:28 -0500 (Tue, 18 Sep 2007) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 161 $
 */

require_once(dirname(__FILE__) ."/cs_versionAbstract.class.php");

class cs_session extends cs_versionAbstract {

	protected $db;
	public $uid;
	public $sid;
	public $sid_check = 1;
	
	//---------------------------------------------------------------------------------------------
	function __construct($createSession=1) {
		if($createSession) {
			//now actually create the session.
			session_start();
		}
		
		//check if there's a uid in the session already.
		//TODO: need a setting somewhere that says what the name of this var should be,
		//	instead of always forcing "uid".
		$this->uid = 0;
		if($_SESSION['uid']) {
			$this->uid = $_SESSION['uid'];
		}
		
		//grab what our session_id is...
		$this->sid = session_id();
		
	}//end __construct()
	//---------------------------------------------------------------------------------------------
	
	
	
	//---------------------------------------------------------------------------------------------
	public function is_authenticated() {
		return(FALSE);
	}//end is_authenticated()
	//---------------------------------------------------------------------------------------------


}//end cs_session{}
?>