<?php
/*
 * FILE INFORMATION:
 * $HeadURL: https://cs-content.svn.sourceforge.net/svnroot/cs-content/releases/0.10/cs_sessionClass.php $
 * $Id: cs_sessionClass.php 221 2007-11-21 17:39:01Z crazedsanity $
 * $LastChangedDate: 2007-11-21 11:39:01 -0600 (Wed, 21 Nov 2007) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 221 $
 */

require_once(dirname(__FILE__) ."/cs_versionAbstract.class.php");

class cs_session extends cs_versionAbstract {

	protected $db;
	public $uid;
	public $sid;
	public $sid_check = 1;
	
	//---------------------------------------------------------------------------------------------
	/**
	 * The constructor.
	 * 
	 * @param $createSession	(boolean,optional) determines if a session will be started or not.
	 */
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
	/**
	 * Required method, so passing the object to contentSystem::handle_session() 
	 * will work properly.
	 * 
	 * @param (none)
	 * 
	 * @return FALSE		FAIL: user is not authenticated (hard-coded this way).
	 */
	public function is_authenticated() {
		return(FALSE);
	}//end is_authenticated()
	//---------------------------------------------------------------------------------------------
	
	
	
	//---------------------------------------------------------------------------------------------
	/**
	 * Retrieve data for an existing cookie.
	 * 
	 * @param $name		(string) Name of cookie to retrieve value for.
	 * 
	 * @return NULL		FAIL (?): cookie doesn't exist or has NULL value.
	 * @return (string)	PASS: value of cookie.
	 */
	public function get_cookie($name) {
		$retval = NULL;
		if(isset($_COOKIE) && $_COOKIE[$name]) {
			$retval = $_COOKIE[$name];
		}
		return($retval);
	}//end get_cookie()
	//---------------------------------------------------------------------------------------------
	
	
	
	//---------------------------------------------------------------------------------------------
	/**
	 * Create a new cookie.
	 * 
	 * @param $name			(string) Name of cookie
	 * @param $value		(string) value of cookie
	 * @param $expiration	(string/number) unix timestamp or value for strtotime().
	 */
	public function create_cookie($name, $value, $expiration=NULL) {
		
		$expTime = NULL;
		if(!is_null($expiration)) {
			if(is_numeric($expiration)) {
				$expTime = $expiration;
			}
			elseif(preg_match('/ /', $expiration)) {
				$expTime = strtotime($expiration);
			}
			else {
				throw new exception(__METHOD__ .": invalid timestamp given (". $expiration .")");
			}
		}
		
		$retval = setcookie($name, $value, $expTime, '/');
		return($retval);
		
	}//end create_cookie()
	//---------------------------------------------------------------------------------------------
	
	
	
	//---------------------------------------------------------------------------------------------
	/**
	 * Destroy (expire) an existing cookie.
	 * 
	 * @param $name		(string) Name of cookie to destroy
	 * 
	 * @return FALSE	FAIL: no cookie by that name.
	 * @return TRUE		PASS: cookie destroyed.
	 */
	public function drop_cookie($name) {
		$retval = FALSE;
		if(isset($_COOKIE[$name])) {
			setcookie($name, $_COOKIE[$name], time() -10000, '/');
			unset($_COOKIE[$name]);
			$retval = TRUE;
		}
		return($retval);
	}//end drop_cookie()
	//---------------------------------------------------------------------------------------------


}//end cs_session{}
?>