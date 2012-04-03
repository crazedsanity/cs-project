<?php

##
##	02-12-2002
/*
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */
##

require_once(dirname(__FILE__) ."/site_config.php");
require_once(dirname(__FILE__) ."/globalFunctions.php");
require_once(dirname(__FILE__) ."/upgradeClass.php");


class Session extends upgrade {

	var $db;
	var $uid;
	var $logUid;
	var $sid;
	var $ip;
	var $date_created;
	var $last_action;
	var $last_activity;
	var $sid_check = 0;
	private $logCategoryId = LOGCAT__SESSION;
	private $logsObj;
	private $dbTable = 'session_table';	//name of table used to store session information.
	private $sessionTimeLeft = 0;


	//##########################################################################
	function __construct(&$db, $create_session=1) 	{
		
		if(is_numeric(LOGCAT__AUTHENTICATION)) {
			$this->logCategoryId = LOGCAT__AUTHENTICATION;
		}
		else {
			throw new exception(__METHOD__ .": no valid log_category_id defined for authentication: did you complete setup?");
		}
		
		if(!defined("CONFIG_SESSION_NAME")) {
			$gf = new cs_globalFunctions;
			$gf->debug_print(get_defined_vars(),1);
			throw new exception("Session{}: the constant, CONFIG_SESSION_NAME, is not defined");
		}
		else {
			ini_set('session.name', CONFIG_SESSION_NAME);
		}
		//-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=||
		//	MUCHO IMPORTANTE!!!! DB MUST BE CONNECTED!!!	||
		//-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=||
		if(!is_object($db)) {
			//looks like we haven't got a database yet... create one.
			$this->db = new phpDB;
			$this->db->connect();
		}
		else {
			$this->db = $db;
		}
		
		//create our logging object.
		$this->logsObj = new logsClass($this->db, $this->logCategoryId);
		
		//create a session, if needs be.
		if($create_session) {
			$this->create_session();

			// grabs the user's IP.
			$this->ip = $_SERVER['REMOTE_ADDR'];
			$this->date_created = date("YMDhis");
			$this->last_action = date("YMDhis");
			$this->last_activity = "-1";
			$this->sid_check = $this->Auth_SID();
		}
	}//end __construct()
	//##########################################################################



	//##########################################################################
	function validate_session_id($sid) {
		//makes sure that the given sid is alphanumeric & length of 32.
		$sid=cleanString($sid, "all");
		if(strlen($sid) > 0) {
			$retval = 1;
		}
		else {
			$retval = 0;
		}
		
		return($retval);
	}//end validate_session_id()
	//##########################################################################


	//##########################################################################
	/**
	 * Creates a session.
	 * 
	 * @param (void)
	 * @return NULL
	 */
	function create_session() {
		ob_start();
		//initializes all session vars.
		session_start();

		// grabs the session id that PHP assigned.
		$this->sid = session_id();

		// the uid defined here MUST BE the default user (non-user account).
		//  In other words, $this->uid needs to be numeric, or bad things will happen;
		//  if we can't determine who the user is supposed to be, we'll assume they're
		//  anonymous.
	
		if($_SESSION['uid']) {
			//self-righting mechanism....
			// if the uid isn't an integer, send flying monkeys to burn the city!
			if(is_numeric($_SESSION['uid'])) {
				$this->uid = $_SESSION['uid'];
			}
			else {
				throw new exception("Session(): FATAL: uid was NOT an integer! " . $_SESSION['uid'] . "<BR>\n\n");
			}
		}
		elseif(strlen($this->sid) == 32) {
			//self-righting mechanism... something seems to have killed $_SESSION['uid'], but we've
			//	still got their sessionId... fix the session & log the issue...
			
			$this->uid=$this->uid_from_sid($this->sid);
			if ($this->uid > 0) {
				$_SESSION['uid'] = $this->uid;
			}
		}
		else {
			$debugInfo  = "_SESSION uid: ". $_SESSION['uid'] ." || this->uid: ". $this->uid ." || this->sid: ". $this->sid;
			$debugInfo .= " || Referer list: ". print_r($_SESSION['referrers']);
			$debugInfo .= " || LAST QUERY: ". $this->db->last_query;
			
			//log the problem.
			$this->logsObj->log_by_class("UID ZERO FOUND!!! DEBUG INFO: $debugInfo", 'error');
			$this->uid = 0;
		}
		ob_end_clean();

	}//end of create_session()
	//##########################################################################
	
	
	
	//##########################################################################
	/**
	 * Checks to see if this is a valid session.
	 * 
	 * @param (void)
	 * 
	 * @return 1		PASS
	 * @return 0		FAIL
	 */
	function Auth_SID() {
		if($this->validate_session_id($this->sid) && is_numeric($_SESSION['user_ID']) && $_SESSION['user_ID'] > 0) {
			//how many minutes do they have left?
			$secondsLeft = $this->check_session_idleness();
			if(($secondsLeft > 0)) {
				//set it.
				$this->sessionTimeLeft = ($secondsLeft / 60);
				$this->sid_check = 1;
			}
			else {
				//hrm... session is expired.
				$this->sessionTimeLeft = 0;
				$this->sid_check = 0;
			}
		}
		else {
			//nope.
			$this->sessionTimeLeft = 0;
			$this->sid_check = 0;
		}

		return($this->sid_check);
	}//end of Auth_SID
	//##########################################################################
	
	
	
	//##########################################################################
	/**
	* checks username & password.
	*
	* @param $username - Username in database.
	* @param $password - Password associated with $username.
	*
	* @return -2 - DISABLED USER
	* @return -1 - NULL VALUES (username/password blank!)
	* @return 0 - INVALID:: bad password.
	* @return 1 - Valid.
	* @return 2 - INVALID:: bad username (no record)
	* @return 3 - INVALID:: duplicate record
	*
	* NOTE: User should see generic error when either a "1" or a "2" is returned.
	*/
	function login($username, $password) {
		
		//precheck... 
		#if((STOP_LOGINS_ON_GLOBAL_ALERT) AND ($this->check_global_alerts(0))) {
		#	//right... no more logins... set the global alert for everyone.
		#	$this->check_global_alerts(1,0);
		#	return(-1);
		#}
		
		//the rest of pre-checking will be done in "authenticate_user()".
		$authResult = $this->authenticate_user($username,$password);

		//log based-upon the code returned from authenticate_user() 
		if($authResult === "null") {
			//username and/or password is null.
			$this->logsObj->log_by_class("Username/or password blank", 'error', $this->logUid);
			$this->set_error_message("login-blank");
			$this->sid_check = 0;
			$retval = -1;
		}
		elseif($authResult === "nouser") {
			//no record found!!
			$this->logsObj->log_by_class("Invalid username (". $username .")", 'error', $this->logUid);
			$this->set_error_message("login-error");
			$this->sid_check = 0;
			$retval = 2;
		}
		elseif($authResult === "toomany") {
			//duplicate records!!!
			$this->logsObj->log_by_class("duplicate records: $username", 'error', $this->logUid);
			$this->set_error_message("login-error");
			$this->sid_check = 0;
			$retval = 3;
		}
		else {
			if($authResult === 1) {
				//good to go!
				//CONCURRENT SESSIONS...
				if(ENFORCE_MAX_SESSIONS == 1) {
					$maxSessionsInfo = $this->check_max_sessions($this->logUid);
					$sessionsAvailable = $maxSessionsInfo['available'];
					if($sessionsAvailable < 1) {
						//no sluts... er... *slots* available.  Log it.
						$details = "Too many active sessions. [More info: Used: ". $maxSessionsInfo['used'] 
						."/". $maxSessionsInfo['max'] ."];";
						$this->logsObj->log_by_class($details, 'error', $this->logUid);
						$this->set_error_message("toomany");
						$this->sid_check = 0;
						return(0);
					}	
				}
				
				//set the internal uid properly.
				$this->uid = $this->logUid;
				
				//set stuff in the session.
				$_SESSION['user_ID'] = $this->uid;
				$_SESSION['uid'] = $this->uid;
				
				//
				foreach($this->authInfo as $tIndex=>$tval) {
					$_SESSION["login_". $tIndex] = $tval;
					$_SESSION[$tIndex] = $tval;
				}
					
				//everything lined up. Update session.
				//log it.
				$details = "SID: ". $this->sid ." -- IP: ". $this->ip;
				$this->logsObj->log_by_class($details, 'create', $this->logUid);
				
				$retval = 1;
				$this->sid_check = $retval;
	
				if($retval == 1) {
					//remove stale sessions.
					$this->auto_logout();
					
					//create a record in the session table.
					if(is_null($this->check_session_idleness())) {
						$insertRes = $this->insert_session_record();
					}
				}
			}
			elseif($authResult === 0) {
				//bad password
				//EXTRA LOG INFO (without logging the actual passsword) TO HELP TROUBLESHOOT.
				if(strtolower($password) == $password) {
					$xDet = create_list($xDet, "all lowercase");
				}
				elseif(strtoupper($password) == $password) {
					$xDet = create_list($xDet, "ALL UPPERCASE");
				}
				else {
					$xDet = create_list($xDet, "MiXeD CaSe");
				}
				$xDet = create_list($xDet, "pass_len=". strlen($password));
				if(ereg("[0-9]", $password)) {
					$xDet = create_list($xDet, "has_numbers=yes");
				}
				else {
					$xDet = create_list($xDet, "has_numbers=no");
				}
				if(eregi("[a-z]", $password)) {
					$xDet = create_list($xDet, "has_letters=yes");
				}
				else {
					$xDet = create_list($xDet, "has_letters=no");
				}
				$details = "USERNAME: $username -- IP: ". $this->ip ." || $xDet || $authResult";
				$this->logsObj->log_by_class($details, 'error', $this->logUid);
				$this->set_error_message("login-error");
				$this->sid_check = 0;
				$retval = 0;
			}
			elseif("awaiting_activation") {
				//hasn't activated yet.
				$details = "Waiting for activation.";
				$this->logsObj->log_by_class($details, 'error', $this->logUid);
				$this->set_error_message($authResult); 
				$this->sid_check = 0;
				$retval = -2;
			}
			else {
				//disabled or internal problem.
				$details = "DISABLED USER";
				$this->logsObj->log_by_class($details, 'error', $this->logUid);
				$this->set_error_message();
				$this->sid_check = 0;
				$retval = -2;
			}
	
		}
		
		return($retval);
	} // end of login()
	//##########################################################################




	//##########################################################################
	function logout($sid=NULL, $uid=NULL, $autoLogout=NULL, $details=NULL) {
		//////////////////////////////////////////////////////////////////
		// eliminates the PHP session, along with removing session from //
		//	database.						//
		//								//
		// INPUTS:::							//
		//	$sid	Session id.					//
		//	$uid	User's unique id number.			//
		// OUTPUTS:::							//
		//	-1	FAILURE: an error occurred.			//
		//	0	FAILURE: no sessions removed.			//
		//	>1	OK: number of sessions removed.			//
		//////////////////////////////////////////////////////////////////
		//do some input checking...
		if(!$uid) {
			//if this->sid doesn't match $sid, don't try to logout based on uid.
			$uid = $this->uid;
		}

		
		//before we try to log or return values, let's get rid of the session.
		if($sid) {
			//don't destroy if it's not the current user.
			if($this->sid == $sid) {
				//check for correctness
				ob_start();
				session_unset();
				session_destroy();
				setcookie(CONFIG_SESSION_NAME,"","-1","/");
				$this->sid=0;
				$this->uid=0;
				$moreInfo = ob_get_contents();
				ob_end_clean();
				$xInfo = create_list($xInfo, "logout(): KILLING COOKIE OUTPUT: ['$moreInfo']", " || ");
			}
		}
		
		//actually destroy the session!
		session_destroy();
		
		//nothing to check.  It worked.
		$retVal = 1;
		
		//add the requested URI to the log.
		$tX = " -- ". $_SERVER['REQUEST_URI'];
		
		//add the extra info to the log, so we can figure-out what's going on with 
		// the issue of giving-out session_id's of "0".
		$tX = create_list($tX, $xInfo, " || ");
		if(!strlen($sid)) {
			$sid = $this->sid;
		}
		$details = create_list($details, "SID: ". $sid ." -- IP: ". $this->ip . $tX, ' -- ');
		$this->logsObj->log_by_class($details, 'delete', $this->logUid);
		
		//remove the requested session_id record.
		$this->delete_session_record($sid);
		
		//remove any other stale records.
		$this->auto_logout();

		return($retVal);

	} // end of logout()
	//##########################################################################

	
	//##########################################################################
	function uid_from_sid($sid) {
		//////////////////////////////////////////////////////////////////
		// Simple query to get a uid from the given sid.		//
		//								//
		// INPUTS:::							//
		//	$sid		(string) SessionId to query on.		//
		// OUTPUTS:::							//
		//	0		FAIL: unable to get uid (check logs?).	//
		//	<n>		PASS: <n> is the uid.			//
		//////////////////////////////////////////////////////////////////
		
		$retval = 0;
		if(isset($_SESSION['uid'])) {
			$retval = $_SESSION['uid'];
		}
			
		return($retval);
	}//end uid_from_sid()
	//##########################################################################
	
	

	//##########################################################################
	function update_last_action() {
		//////////////////////////////////////////////////////////////////
		//Updates the last_action field in the session... just a quick 
		//	wrapper for $this->update_session_table()" with a specific
		//	set of arguments.
		//
		// INPUTS:::
		//	<void>	<none>
		// OUTPUTS:::
		//	<see the outputs for $this->update_session_table()>
		//////////////////////////////////////////////////////////////////
		
		//build the SQL statement.
		$sql = 'UPDATE '. $this->dbTable .' SET last_action=NOW() WHERE session_id=\'' .
				cleanString($this->sid, 'sql') .'\'';
		
		//run it, check for errors.
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		//set default return (failure), then do some checking.
		$retval = 0;
		if(!strlen($dberror) && $numrows == 1) {
			//worked.
			$retval = 1;
			
			//check how close it is to death.
			$this->check_session_idleness();
		}
		
		return($retval);
	}//end of update_last_action()
	//##########################################################################
	
	
	
	//##########################################################################
	/**
	 * Updates the last page the user viewed (and their last_action) to the 
	 * specified value, which must be longer than 5 characters.
	 */
	public function set_last_page_viewed($url) {
		//
		$retval = 0;
		if(strlen($url) > 5) {
			//create the SQL statement.
			$sql = 'UPDATE '. $this->dbTable .' SET last_page_viewed=\''. cleanString($url, 'sql')
				.'\', last_action=NOW() WHERE session_id=\''. cleanString($this->sid, 'sql') .'\'';
			
			//run it, capture results.
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			//see if it worked.
			if(!strlen($dberror) && $numrows == 1) {
				//good to go.
				$retval = 1;
			}
		}
		
		return($retval);
	}//end set_last_page_viewed()
	//##########################################################################



	//##########################################################################
	function update_session_internals($varArr) {
		//
		//Updates internal variables just in case they're called externally.
		//allowedVarArr holds the values that CAN be replaced.  The foreach
		//	loop executes and conditionally updates the internal vars
		//	(i.e. if the passed key is in our allowedVarArr, we'll
		//	update, but not otherwise; eliminates possible problems
		//	later on).
		//		
		$allowedVarArr = array(
			"date_created", "last_action", "sid_check", 
			"last_activity", "uid"
		);
		
		foreach($allowedVarArr as $varName) {
			if(isset($varArr[$varName])) {
				$this->$varName=$varArr[$varName];
			}
		}


		$this->update_session_vars($varArr);
		$this->update_last_action();
	} //end of update_session_internals()
	//##########################################################################




	//##########################################################################
	function update_session_vars($varArr) {
		//////////////////////////////////////////////////////////////////////////
		//NOTE::: this will UNCONDITIONALLY add/update variables in the 	//
		//	session.  NO CHECKS are made on them. You've been warned.	//
		//	Updates or *ADDS* PHP session variables.			//
		//////////////////////////////////////////////////////////////////////////
		foreach($varArr as $key=>$value) {
			//
			$_SESSION["$key"] = $value;
		}
	}//end of update_session_vars()
	//##########################################################################




	//##########################################################################
	function set_error_message($status=NULL) {
		//////////////////////////////////////////////////////////////////
		//this makes a call to set_message(), located			//
		//	in globalFunctions.php.					//
		//								//
		// INPUTS:::							//
		//	<string>	should match one of the cases below.	//
		// OUTPUTS:::							//
		//	<none>		<void>					//
		//////////////////////////////////////////////////////////////////

		$dRedir = "";
		$dTxt   = "";

		$status = strtolower($status);
		switch($status) {
		case "login-error":
			$dTitle = "Incorrect Username/Password";
			$dMsg	.= "The username/password you entered is invalid. Please try again.<br>";
			$dType	= "error";
			break;
		case "login-blank":
			$dTitle = "Invalid Input";
			$dMsg	= "The username and/or password your entered are invalid. Please try again.<br>";
			$dType	= "error";
			break;
		case "logouterror":
			$dTitle = "Unable to Logout";
			$dMsg	= "An unknown error occurred while attempting to log you out.";
			$dType	= "fatal";
			break;
		case "restricted":
			$dTitle = "Access  Forbidden";
			$dMsg	= "<img src=\"/images/puis-clean.png\"><br>Outside access to this section is forbidden.<br> Your attempt has been logged.";
			$dType	= "fatal";
			break;
		case "expired":
			$dTitle	= "Session Expired";
			$dMsg	= "For your protection, your session has been expired.<BR>\nPlease re-login.<BR>\n";
			$dType	= "fatal";
			$dRedir	= "/login.php";
			$dTxt	= "Login Again.";
			break;
		default:
			$dTitle	= "Account Disabled";
			$dMsg	 = "Your account has been disabled by an administrator.<br>";
			$dType	= "fatal";
			break;
		}

		set_message($dTitle, $dMsg, $dRedir, $dType, $dTxt);

	} // end of set_error_message()
	//##########################################################################




	//##########################################################################
	function sudden_death($message=NULL) {
		//////////////////////////////////////////////////////////////////
		// USED TO SET A MESSAGE IF THE SESSION SUDDENLY DIED, BUT A 	//
		//	MESSAGE MUST BE DISPLAYED (the old session should be	//
		//	restarted)						//
		// INPUTS:::							//
		//	$message	Message to be set (refer to the local	//
		//			 function set_error_message)		//
		// OUTPUTS:::							//
		//	<none>		(ALWAYS EXITS)				//
		//////////////////////////////////////////////////////////////////
		
		if(!is_null($message)) {
			$this->create_session();
			$this->set_error_message($message);
		}
		
		$tLoc = "/login.php?". CONFIG_SESSION_NAME ."=". $this->sid ."&destination=". $_SERVER['REQUEST_URI'];
		conditional_header("$tLoc");
		exit;
		
	}//end of sudden_death()
	//##########################################################################
	
	
	
 	//##########################################################################
	/**
	* Does all of the authentication stuff: only logs database errors.  
	* Returns a special code so calling code can log problems if needs-be.
	*
	* NOTE::: this function is large simply so the calling code knows *why* it failed, so they can log appropriately.
	*
	* @param $username - <string> username to search for.
	* @param $password - <string> password to check against.
	*
	* @return 0 - INVALID:: bad password.
	* @return 1 - Valid.
	* @return <string> - ERROR: string denotes error.
	*/
	function authenticate_user($username,$password) {
		//pre-check and cleaning.
		$username = strtolower(cleanString($username, "email"));

		if(!$username || !$password) {
			//something was null.
			return("null");
		}
		
		//pre-check was okay; run a query...
		$query = "SELECT uid AS ID, username, password, group_id, contact_get_attribute(contact_id,'email') AS email, " .
				"contact_id FROM user_table WHERE username='". $username ."'";
		
		$this->db->exec($query);
		$numrows = $this->db->numRows();
		$dberror = $this->db->errorMsg(0,1,0,"authenticate_user(): ", " QUERY: $query");
		
		//see what happened...
		if($dberror) {
			//database error...
			throw new exception("authenticate_user(): DATABASE ERROR!!!\n$dberror\nSQL::: $query");
		}
		elseif($numrows == 0) {
			//no user...
			$retval = "nouser";
		}
		elseif($numrows > 1) {
			//too many users...
			$retval = "toomany";
		}
		else {
			//okay, no dberror, and numrows is 1...
			// options left: -4,-3,-2,0,1
			$resultSet = $this->db->farray_fieldnames();
			$this->logUid = $resultSet['id'];
			
			//check if we should use an old version of the authentication...
			if(strlen($resultSet['password']) == 32) {
				if(md5($password .'_'. $resultSet['contact_id']) == $resultSet['password']) {
					//good password.  Good.
					$retval = 1;
					$this->authInfo = $resultSet;
					unset($this->authInfo['password']);
				}
				else {
					//bad password...
					$retval = 0;
				}
			}
			else {
				//crap.  Use the old method...
				if(encrypt($password, $password) == $resultSet['password']) {
					//sweet.  Convert their password!
					$userObj = new userClass($this->db, $this->logUid);
					$userObj->bypassAuthCheck = TRUE;
					$retval = $userObj->change_password($password, $password, $password);
					$this->authInfo = $resultSet;
				}
				else {
					$retval = 0;
				}
			}
		}
		
		return($retval);
	}//end authenticate_user()
 	//##########################################################################
 	
 	
 	
 	//##########################################################################
 	/**
 	 * Insert a record into the table defined by $this->dbTable.
 	 */
 	private function insert_session_record() {
 		//create the SQL array.
 		$insertData = array(
 			'session_id'	=> "'". cleanString($this->sid, 'sql') ."'",
 			'uid'			=> cleanString($this->uid, 'numeric'),
 			'ip'			=> "'". cleanString($this->ip, 'sql') ."'"
 		);
 		
		//create the statement.
		$sql = 'INSERT INTO '. $this->dbTable .' '. string_from_array($insertData, 'insert');
		
		//run it & capture info.
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		//set a default return value, then check to see if it worked (default to failed).
		if(strlen($dberror) || $numrows !== 1) {
			//failed.
			$this->logsObj->log_dberror(__METHOD__ .": Failed to insert record, numrows=($numrows), " .
					"dberror:::\n$dberror");
			$retval = FALSE;
		}
		else {
			//it worked.
			$retval = TRUE;
		}
		
		return($retval);
 	}//end insert_session_record()
 	//##########################################################################
 	
 	
 	
 	//##########################################################################
 	private function delete_session_record($details=NULL, $sid=NULL) {
 		if(is_null($sid)) {
 			//use internal session id.
 			$sid = $this->sid;
 		}
 		//
 		$sql = 'DELETE FROM '. $this->dbTable .' WHERE session_id=\''. cleanString($sid, 'sql') .'\'';
 		$numrows = $this->db->exec($sql);
 		$dberror = $this->db->errorMsg();
 		
 		if(strlen($dberror)) {
 			//log the problem & set return val.
 			$this->logsObj->log_dberror("delete_session_record(): failed to delete ($numrows), or dberror::: $dberror");
 			$retval = 0;
 		}
 		else {
 			//we're fine.
 			$retval = 1;
 		}
 		
 		return($retval);
 	}//end delete_session_record()
 	//##########################################################################
 	
 	
 	
 	//##########################################################################
 	/**
 	 * Remove all stale session records.
 	 * 
 	 * @return (n)		PASS: removed (n) records.
 	 * @return TRUE		PASS: no records were removed.
 	 * @return FALSE	FAIL: database error.
 	 */
 	public function auto_logout() {
 		//build the SQL.
 		$sql = "SELECT *, (NOW() - last_action) as idle, (NOW() - creation) as total_time " .
 			"FROM ". $this->dbTable ." WHERE ((NOW() - last_action) > ". 
 			"'" . MAX_IDLE ."'::interval) OR ((NOW() - creation) > '". 
 			MAX_TIME ."'::interval)";
 		
 		$numrows = $this->db->exec($sql);
 		$dberror = $this->db->errorMsg();
 		
 		if(strlen($dberror) || $numrows < 0) {
 			//failed.
 			$this->logsObj->log_dberror("auto_logout(): query failed with error::: $dberror");
 			$retval = FALSE;
 		}
 		elseif($numrows == 0) {
 			//no rows.
 			$retval = TRUE;
 		}
 		else {
 			//retrieve the data, & logout each in turn.
 			$data = $this->db->farray_fieldnames('session_id');
 			
 			$retval = 0;
 			foreach($data as $mySid=>$subData) {
 				//build the details.
 				$details = "Automatic logout: session_id=(". $mySid ."), ip=(". $subData['ip'] ."), " .
 					"idle for ". $subData['idle'] .", total session length was ". $subData['total_time'];
 				
 				//log 'em out.
 				$retval += $this->delete_session_record($details, $mySid);
 			}
 		}
 		
 		return($retval);
 	}//end auto_logout()
 	//##########################################################################
 	
 	
 	
 	//##########################################################################
 	/**
 	 * Return the number of seconds available for this session.
 	 */
 	private function check_session_idleness() {
 		//build the SQL to see what the current sessions idleness/length is, compared to
 		//	the maximum idleness/length settings.
 		$sql = "SELECT extract(epoch from (NOW() - creation))::int as total_time, " .
 				"extract(epoch from ('". MAX_TIME ."'::interval))::int AS max_time " .
 				"FROM session_table " .
 				"WHERE session_id='". $this->sid ."';";
 		
 		//run it & capture data.
 		$numrows = $this->db->exec($sql);
 		$dberror = $this->db->errorMsg();
 		
 		if(strlen($dberror) || $numrows !== 1) {
			//failed.
			if(strlen($dberror)) {
				//Ooops, looks like there was a database error.
				$details = __METHOD__ .": database error:::\n$dberror";
 				$this->logsObj->log_dberror($details);
 				throw new exception($details);
			}
 			$retval = NULL;
 		}
 		else {
 			//retrieve data.
 			$data = $this->db->farray_fieldnames();
 			
 			//check if we've gone over the idle period.
 			$retval = ($data['max_time'] - $data['total_time']);
 		}
 		
 		return($retval);
 		
 	}//end check_session_idleness()
 	//##########################################################################
	
	
	
 	//##########################################################################
	public function is_authenticated() {
		$retval = FALSE;
		if($this->Auth_SID() === 1) {
			$retval = TRUE;
		}
		
		return($retval);
	}//end is_authenticated()
 	//##########################################################################
 	
}
?>
