<?php
/*
 * FILE INFORMATION:
 * $HeadURL: https://cs-content.svn.sourceforge.net/svnroot/cs-content/trunk/1.0/cs_session.class.php $
 * $Id: cs_session.class.php 455 2009-08-28 20:21:25Z crazedsanity $
 * $LastChangedDate: 2009-08-28 15:21:25 -0500 (Fri, 28 Aug 2009) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 455 $
 */

class cs_session extends cs_contentAbstract {

	protected $uid;
	protected $sid;
	protected $sid_check = 1;
	
	//-------------------------------------------------------------------------
	/**
	 * The constructor.
	 * 
	 * @param $createSession	(mixed,optional) determines if a session will be started or not; if
	 * 								this parameter is non-null and non-numeric, the value will be 
	 * 								used as the session name.
	 */
	function __construct($createSession=true) {
		parent::__construct(true);
		if($createSession) {
			if(is_string($createSession) && strlen($createSession) >2) {
				session_name($createSession);
			}
			
			//now actually create the session.
			session_start();
		}
		
		//check if there's a uid in the session already.
		//TODO: need a setting somewhere that says what the name of this var should be,
		//	instead of always forcing "uid".
		$this->uid = 0;
		if(isset($_SESSION['uid']) && $_SESSION['uid']) {
			$this->uid = $_SESSION['uid'];
		}
		
		//grab what our session_id is...
		$this->sid = session_id();
		
	}//end __construct()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
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
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
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
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
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
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
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
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	/**
	 * PHP5 magic method for retrieving the value of internal vars; this allows 
	 * code to find the value of these variables, but not modify them (modifying 
	 * requires the "__set($var,$val)" method).
	 */
	public function __get($var) {
		return($this->$var);
	}//end __get()
	//-------------------------------------------------------------------------


}//end cs_session{}
?>