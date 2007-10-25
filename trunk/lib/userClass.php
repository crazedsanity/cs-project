<?php
/*
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */

require_once(dirname(__FILE__) .'/abstractClasses/dbAbstract.class.php');
require_once(dirname(__FILE__) .'/contactClass.php');


class userClass extends dbAbstract {
	
	public $db;
	
	protected $uid;
	protected $isAdmin=NULL;
	
	protected $logsObj;
	protected $gfObj;
	
	public $bypassAuthCheck = FALSE;
	
	//================================================================================================
	function __construct(cs_phpDB &$db, $uid=NULL) {
		
		$this->db = $db;
		if(is_numeric($uid)) {
			$this->uid = $uid;
		} else {
			$this->uid = $_SESSION['uid'];
		}
		
		//create the object that can handle logging.
		$this->logsObj = new logsClass($this->db, "Users");
		
		$this->gfObj = new cs_globalFunctions;
	}//end __construct{}
	//================================================================================================
	
	
	
	//=========================================================================
	public function get_groups($forOptionList=TRUE) {
		$sql = "SELECT * FROM group_table ORDER BY lower(name);";
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		$retval = array();
		if(strlen($dberror) || $numrows < 1) {
			//something bad happened...
			//TODO: log something.
		}
		else {
			//get the data.
			if($forOptionList) {
				//get just two fields, so it can be easily used as an option list.
				$retval = $this->db->farray_nvp('group_id', 'name');
			}
			else {
				//get ALL the data.
				$retval = $this->db->farray_fieldnames('group_id', NULL, 0);
			}
		}
		
		return($retval);
	}//end get_groups()
	//=========================================================================
	
	
	//================================================================================================
	function get_group_list() {
		$myList = $this->get_groups(TRUE);
		$selectThis    = $_SESSION['group_id'];
		if(is_array($myList)) {
			//use a global function to build or drop-down list.
			$optionList = array_as_option_list($myList, $selectThis);
		}
		
		return($optionList);
	}//end get_group_list()
	//================================================================================================
	
	
	//================================================================================================
	function get_settings() {
		$query = "select " .
			"up.uid, po.name as option_name, po.effective_value, pt.name AS pref_type, " .
			"pt.default_value, pt.display_name, pt.description " .
		"FROM " .
			"user_pref_table AS up " .
			"INNER JOIN pref_option_table AS po ON (up.pref_option_id=po.pref_option_id) " .
			"INNER JOIN pref_type_table AS pt ON (po.pref_type_id=pt.pref_type_id) " .
		"WHERE " .
			"up.uid=". $this->uid;
		$numrows = $this->db->exec($query);
		$this->lastError = $this->db->errorMsg();
		
		if($this->lastError || $numrows != 1) {
			if(strlen($this->lastError)) {
				$this->logsObj->log_dberror("get_settings(): ". $this->lastError);
			}
			$retval = 0;
			
			//okay, so, they don't have anything: create it automatically.
		}
		else {
			$tmp = $this->db->farray();
			$retval = unserialize($tmp[0]);
		}
		
		return($retval);
	}//end get_settings()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Returns an array of settings (name=>default_value) that can be used: any settings not in the
	 * returned array are considered unused/defunct.
	 */
	function get_default_settings() {
		$sql = "SELECT * FROM pref_type_table ORDER BY name";
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		$retval = array();
		if(strlen($this->lastError) || $numrows < 0) {
			$this->logsObj->log_dberror("get_default_settings(): invalid rows (". $numrows .") or database error::: ". $this->lastError);
		}
		elseif($numrows == 0) {
			//no data.
			throw new exception("get_default_settings(): it appears there are no settings to retrieve!  Please create some.");
		}
		else {
			//got it.
			$retval = $this->db->farray_fieldnames('pref_type_id', NULL, 0);
		}
		
		return($retval);
	}//end get_default_settings()
	//================================================================================================
	
	
	
	//================================================================================================
	function update_settings($settingsArr, $testFilter=FALSE) {
		//TODO: make it more than just a stub.
	}//end update_settings()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Retrieves information from the "users" table about the given user.
	 * 
	 * @param $findThis		(mixed) Value to search id, loginname, email, or "kurz" for.
	 * 
	 * @return (0)			FAIL: unable to find user.
	 * @return (array)		PASS: array contains information about the user found.
	 */
	function get_user_info($findThis) {
		
		$retval = 0;
		if(isset($findThis) && strlen($findThis)) {
			$findThis = strtolower($findThis);
			
			if(is_numeric($findThis)) {
				$criteria = "uid = $findThis";
			}
			else {
				$criteria = "lower(username) = '$findThis'";
			}
			
			$query = "SELECT * from user_table WHERE $criteria";
			
			$numrows = $this->db->exec($query);
			$dberror = $this->db->errorMsg();
			
			if($dberror || $numrows != 1) {
				//no dice, dude.
				if($dberror) {
					$this->logsObj->log_dberror("get_user_info(): error::: ". $dberror);
					throw new exception(__METHOD__ .": failed to get user data... ");
				}
				$retval = 0;
			}
			else {
				//retrieve the data.
				$retval = $this->db->farray_fieldnames();
			}
		}
		
		return($retval);
	}//end get_user_info()
	//================================================================================================
	
	
	
	
	//================================================================================================
	/**
	 * Changes password, and has a built-in checking mechanism.
	 * 
	 * @param $oldPass			(str) Old password...
	 * @param $newPass			(str) New password.
	 * @param $newPassCheck		(str) Check against $newPass: must be *EXACT*.
	 */
	function change_password($oldPass,$newPass,$newPassCheck) {
		//pre-checks, before doing actual work.
		if($newPass != $newPassCheck) {
			$this->lastError = "Passwords do not match";
			$retval = 0;
		}
		else {
			$retval = 0;
			//now check to see authentication.
			$tSessClass = new Session($this->db,0);
			$userName = $_SESSION['login_username'];
			if($this->bypassAuthCheck) {
				$authCheck = 1;
			}
			else {
				$authCheck = $tSessClass->authenticate_user($userName, $oldPass);
			}
			
			//only proceed if the old password was correct.
			if($authCheck) {
				//encrypt the new password...
				$newPass = $this->encrypt_pass($newPass);
				
				$newPass = cleanString($newPass, 'sql');
				$query = "UPDATE user_table SET password='$newPass' WHERE uid=". $this->uid;
				$numrows = $this->db->exec($query);
				$this->lastError = $this->db->errorMsg();
				
				if($numrows != 1 || $this->lastError) {
					//FAILED!
					if($this->lastError) {
						$this->logsObj->log_dberror("change_password(): ". $this->lastError);
						$this->lastError = "Database error";
					}
					elseif($numrows == 0) {
						//no rows affected... invalid user!
						$this->lastError = "Invalid user!";
					}
					$retval = 0;
					throw new exception(__METHOD__ .": failed to update password... ");
				} else {
					//got it!
					$retval = $numrows;
				}
			}
			else {
				//more explanation of what happened.
				$this->lastError = "Old password was invalid";
			}
		}
		
		return($retval);
		
	}//end change_password()
	//================================================================================================
	
	
	
	//=========================================================================
	public function is_admin() {
		if(!is_null($this->isAdmin)) {
			//use the internal value.
			$retval = $this->isAdmin;
		}
		else {
			$sql = "SELECT * FROM user_table WHERE is_admin AND uid=". $this->uid;
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			$retval = 0;
			if(strlen($dberror) || $numrows !== 1) {
				//something went wrong.
				//TODO: log something.
			}
			else {
				$retval = 1;
			}
			
			//set the internal value.
			$this->isAdmin = $retval;
		}
		
		return($retval);
	}//end is_admin()
	//=========================================================================
	
	
	
	//=========================================================================
	public function encrypt_pass($pass) {
		//encrypt it... 
		$myInfo = $this->get_user_info($this->uid);
		
		if(is_numeric($myInfo['contact_id'])) {
			$retval = md5($pass .'_'. $myInfo['contact_id']);
		}
		else {
			throw new exception(__METHOD__ .": failed to get a useable contact_id for uid=(". $this->uid .")");
		}
		return($retval);
	}//end encrypt_pass()
	//=========================================================================
	
}//end userClass{}
?>
