<?php
/*
 * Created on Jun 27, 2007
 * 
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */


class pref {
	
	public $db;
	private $uid;
	private $lastError = NULL;
	
	private $logsObj;
	
	//=========================================================================
	public function __construct(cs_phpDB &$db, $uid) {
		$this->db = $db;
		$this->uid = $uid;
		
		$this->logsObj = new logsClass($this->db, "Preferences");
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Return all preferences, optionally matching the given criteria, and optionally
	 * with a list of all their available options.
	 * 
	 * WARNING: if no criteria is passed and 0 rows are returned, this will throw 
	 * an exception.
	 */
	public function list_all_prefs(array $criteria=NULL, $getOptions=TRUE) {
		
		$critString = "";
		if(is_array($criteria) && count($criteria)) {
			$critString = "WHERE ". string_from_array($criteria, 'select');
		}
		
		$sql = "select * from pref_type_table ". $critString ." ORDER BY pref_type_id";
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		if(strlen($this->lastError) || $numrows < 1) {
			if(strlen($this->lastError)) {
				$details = "";
				$this->logsObj->log_dberror($details);
			}
			elseif($numrows == 0 && is_null($criteria)) {
				//that's bad.
				$details = "list_all_prefs(): no preferences";
				$this->logsObj->log_dberror($details);
				throw new exception($details);
			}
			$retval = NULL;
		}
		else {
			if(is_array($criteria) && count($criteria)) {
				$retval = $this->db->farray_fieldnames();
				if($getOptions === TRUE) {
					$retval['optionList'] = $this->get_pref_options($retval['pref_type_id']);
				}
			}
			else {
				$retval = $this->db->farray_fieldnames('pref_type_id', NULL, 0);
				if($getOptions === TRUE) {
					foreach($retval as $id=>$array) {
						$retval[$id]['optionList'] = $this->get_pref_options($array['pref_type_id']);
					}
				}
			}
			
		}
		
		return($retval);
	}//end list_all_prefs()
	//=========================================================================
	
	
	
	
	//=========================================================================
	/**
	 * Retrieves the list of available options for a given pref_type_id.
	 */
	private function get_pref_options($prefTypeId) {
		$sql = "SELECT * FROM pref_option_table WHERE " .
				"pref_type_id=". $prefTypeId ." ORDER BY lower(name)";
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		if(strlen($this->lastError) || $numrows < 1) {
			if(strlen($this->lastError)) {
				$details = "get_pref_options($prefTypeId): ". $this->lastError;
				$this->logsObj->log_dberror($details);
			}
			$retval = NULL;
		}
		else {
			$retval = $this->db->farray_fieldnames('pref_option_id', NULL, 0);
		}
		
		return($retval);
	}//end get_pref_options();
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Retrieves all of a user's preferences.  If $showAll is TRUE, it will return 
	 * more than just pref_type_id=>pref_option_id.
	 */
	public function get_user_prefs($showAll=FALSE) {
		$sql = "SELECT * FROM user_pref_table AS up INNER " .
			"JOIN pref_option_table AS po USING (pref_option_id) WHERE uid=". $this->uid;
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		if(strlen($this->lastError) || $numrows < 1) {
			if(strlen($this->lastError)) {
				$details = "get_user_prefs(): ". $this->lastError;
				$this->logsObj->log_dberror($details);
			}
			$retval = NULL;
		}
		else {
			if($showAll === TRUE) {
				$retval = $this->db->farray_fieldnames('pref_type_id', NULL, 0);
			}
			else {
				$retval = $this->db->farray_nvp('pref_type_id', 'pref_option_id');
			}
		}
		
		return($retval);
	}//end get_user_prefs()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Updates/inserts a user's preference.
	 */
	public function update_user_pref($prefTypeId, $prefOption) {
		$retval = NULL;
		if(!is_numeric($prefTypeId) || !is_numeric($prefOption)) {
			throw new exception("update_user_pref(): can't set preference without any data");
		}
		else {
			$userPrefs = $this->get_user_prefs(TRUE);
			if(isset($userPrefs[$prefTypeId])) {
				$sql = "UPDATE user_pref_table SET pref_option_id=". $prefOption ." WHERE " .
					"uid=". $this->uid ." AND user_pref_id=". $userPrefs[$prefTypeId]['user_pref_id'];
			}
			else {
				$sql = "INSERT INTO user_pref_table (uid, pref_option_id) VALUES (". $this->uid .", ". $prefOption .")";
			}
			
			$numrows = $this->db->exec($sql);
			$this->lastError = $this->db->errorMsg();
			
			if(strlen($this->lastError) || $numrows !== 1) {
				if(strlen($this->lastError)) {
					$details = "update_user_pref(): ". $this->lastError;
					$this->logsObj->log_dberror($details);
					throw new exception($details);
				}
				$retval = $numrows;
			}
			else {
				$retval = $numrows;
			}
		}
		
		return($retval);
	}//end update_user_pref()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Retrieves the value for a given pref_type_id (if no user-defined preference 
	 * is available, it returns the default).
	 */
	public function get_pref_value($prefTypeId) {
		$retval = NULL;
		if(!is_numeric($prefTypeId)) {
			throw new exception('get_pref_value(): invalid prefTypeId passed');
		}
		else {
			//get the user's preferences first.
			$userPrefs = $this->get_user_prefs();
			
			if(isset($userPrefs[$prefTypeId])) {
				$prefOptionId = $userPrefs[$prefTypeId];
				$optionList = $this->get_pref_options($prefTypeId);
				$retval = $optionList[$prefOptionId]['effective_value'];
			}
			else {
				$allPrefs = $this->list_all_prefs(NULL, FALSE);
				$retval = $allPrefs[$prefTypeId]['default_value'];
			}
		}
		
		return($retval);
	}//end get_pref_value()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Retrieve preference info for a user by it's name.
	 * EXAMPLE: get_pref_value_by_name('notifyIssueCreated')
	 */
	public function get_pref_value_by_name($name) {
		$myPref = $this->list_all_prefs(array('name' => $name));
		$retval = NULL;
		if(is_array($myPref)) {
			$retval = $this->get_pref_value($myPref['pref_type_id']);
		}
		
		return($retval);
	}//end get_pref_value_by_name()
	//=========================================================================
	
	
}//end pref{}

?>