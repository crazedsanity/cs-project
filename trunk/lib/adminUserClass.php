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

class adminUserClass extends userClass {
	
	/** The database object. */
	public $db;
	
	/** Current uid. */
	protected $uid;
	
	/** Defines if the current user can do anything. */
	protected $isAdmin = NULL;
	
	/** Logging object */
	private $logsObj;
	
	//=========================================================================
	public function __construct(cs_phpDb &$db) {
		$this->db = $db;
		$this->uid = $_SESSION['user_ID'];
		$this->logsObj = new logsClass($this->db, "Admin User");
		
		//check that the user is an admin...
		$this->is_admin();
		if(!$this->isAdmin) {
			//not an admin!!!
			$this->logsObj->log_by_class("Current user (uid=". $this->uid .") is not an admin (isAdmin=". $this->isAdmin .")");
			throw new exception("User attempted to access administrative library without proper permissions!!!");
		}
		
		//call our parent's constructor.
		parent::__construct($this->db);
	}//end __construct();
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Create a new user!
	 */
	public function create_user(array $data) {
		
		//check that we've got everything.
		$requiredFields = array('fname', 'lname', 'username', 'password', 'email');
		
		$missingFields = array();
		foreach($requiredFields as $field) {
			if(!isset($data[$field])) {
				$missingFields[] = $field;
			}
		}
		
		//check if we've got missing fields.
		if(count($missingFields)) {
			//nothin' doin'.  Fail 'em.
			$details = __METHOD__ .": there were fields missing::: ". $this->gfObj->string_from_array($missingFields);
			$this->logObj->log_dberror($details);
			$retval = NULL;
		}
		else {
			//create the contact.
			$contactId = $this->create_contact($data['fname'], $data['lname'], $data['email'], $data['company']);
			$data['contact_id'] = $contactId;
			unset($data['fname'], $data['lname']);
			
			
			//now, define how everything gets cleaned.
			$cleanStringArr = array(
				'username'		=> 'sql',
				'password'		=> 'sql',
				'group_id'		=> 'numeric',
				'contact_id'	=> 'numeric'
			);
			
			//good to go: encrypt the password.
			$data['password'] = $this->encrypt_pass($data['password']);
			$sql = "INSERT INTO user_table ". string_from_array($data, 'insert', NULL, $cleanStringArr, TRUE, TRUE);
			
			if(!$this->run_sql($sql)) {
				//something bad happened... LOG IT!
				$this->logsObj->log_dberror(__METHOD__ .": failed to insert user... ". $this->lastError); 
			}
			else {
				//got something: get the user's ID.
				$sql = "SELECT currval('user_table_uid_seq'::text)";
				if($this->run_sql($sql)) {
					//got it.
					$tempData = $this->db->farray();
					$retval = $tempData[0];
					
					//LOG IT!!!
					$details = "Created new user #". $retval ." (". $data['username'] .")";
					$uid = $retval;
					$this->logsObj->log_by_class($details, 'create', $uid);
					
					//now add the user to the specified group.
					$this->add_user_to_group($uid, $data['group_id']);
				}
				else {
					$details = "Created new user (". $data['username'] .") [NEW ID QUERY FAILED, numrows=(". $this->lastNumrows ."), DBERROR::: ". $this->lastError ."]";
					$uid = NULL;
					$this->logsObj->log_dberror($details, $uid);
				}
			}
		}
		
		return($retval);
		
	}//end create_user()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_group_user($groupId=NULL) {
		//TODO: FORCE $groupId to be numeric & non-null.
		$sql = "SELECT " .
				"ug.user_group_id, ug.group_id, ug.uid, u.username " .
			"FROM user_group_table AS ug " .
			"INNER JOIN user_table AS u ON (ug.uid=u.uid)";
		
		if(is_numeric($groupId)) {
			//build some criteria.
			$critArr = array(
				'ug.group_id'	=> $groupId
			);
			$sql .= " WHERE ". string_from_array($critArr, 'select', NULL, 'number');
		}
		$sql .= " ORDER BY u.username";
		
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows < 1) {
			//something went wrong.
			if(strlen($dberror)) {
				//TODO: log something here.
				$this->logsObj->log_dberror(__METHOD__ .": ENCOUNTERED A DB ERROR::: ". $dberror);
				throw new exception(__METHOD__ .": ". $dberror);
			}
			$retval = array();
		}
		else {
			//got it.
			$retval = $this->db->farray_fieldnames("uid", NULL, 0);
		}
		
		return($retval);
		
	}//end get_group_user()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_users(array $criteria=NULL) {
		
		//build some SQL.
		$sql = "SELECT uid, username,contact_get_attribute(contact_id, 'email') AS email from user_table ";
		
		if(!is_null($criteria) && count($criteria)) {
			//add some criteria.
			$sql .= string_from_array($criteria, 'select', NULL, 'sql');
		}
		
		//make it ORDERED.
		$sql .= " ORDER BY username";
		
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows < 1) {
			//something went wrong.
			if(strlen($dberror)) {
				$this->logsObj->log_dberror(__METHOD__ .": could not get user list: ". $dberror);
			}
			$retval = array();
		}
		else {
			//got it!
			$retval = $this->db->farray_fieldnames("uid", NULL, 0);
		}
		
		return($retval);
		
	}//end get_users()
	//=========================================================================
	
	
	
	//=========================================================================
	public function add_user_to_group($uid,$groupId) {
		//add the user to a group.
		$sqlArr = array(
			'uid'		=> $uid,
			'group_id'	=> $groupId
		);
		
		$sql = "INSERT INTO user_group_table ". string_from_array($sqlArr, 'insert', NULL, 'number');
		if(!$this->run_sql($sql)) {
			//indications are it failed.
			if(strlen($this->lastError)) {
				$this->logsObj->log_dberror(__METHOD__ .": failed to add user to group (". $groupId ."): ". $this->lastError);
			}
			$retval = FALSE;
		}
		else {
			//it worked!
			$retval = TRUE;
			
			//log it!
			$groupData = $this->get_groups(FALSE);
			$details = "Added user to group #". $groupId ." (". $groupData[$groupId]['name'] .")";
			$this->logsObj->log_by_class($details, 'update', $uid);
		}
		
		return($retval);
	}//end add_user_to_group()
	//=========================================================================
	
	
	
	//=========================================================================
	public function remove_user_from_group($uid,$groupId) {
		$sqlArr = array(
			'uid'		=> $uid,
			'group_id'	=> $groupId
		);
		
		$sql = "DELETE FROM user_group_table WHERE ". string_from_array($sqlArr, 'select', NULL, 'number');
		
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows !== 1) {
			//indications are it failed.
			if(strlen($dberror)) {
				$this->logsObj->log_dberror(__METHOD__ .": unable to delete user from group (". $groupId ."): ". $dberror);
			}
			$retval = FALSE;
		}
		else {
			//it worked!
			$retval = TRUE;
			
			//log it!
			$groupData = $this->get_groups(FALSE);
			$details = "Removed user from group #". $groupId ." (". $groupData[$groupId]['name'] .")";
			$this->logsObj->log_by_class($details, 'update', $uid);
		}
		
		return($retval);
	}//end remove_user_from_group()
	//=========================================================================
	
	
	
	//=========================================================================
	public function update_group($groupId, array $updates) {
		//TODO: create an array of fields that can be updated & use array_intersect() to remove unwanted field updates.
		$groupId = cleanString($groupId, 'number');
		$sql = "UPDATE group_table SET ". string_from_array($updates, 'update', NULL, 'sql') ." WHERE group_id=". $groupId;
		
		//before running the update, capture what the group's settings.
		$oldGroupData = $this->get_groups(FALSE);
		$oldGroupData = $oldGroupData[$groupId];
		
		//start a transaction & attempt the update.
		$this->db->beginTrans();
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows !== 1) {
			//nothin' doin'.
			$this->db->rollbackTrans();
			
			if(strlen($dberror)) {
				$details = __METHOD__ .": invalid rows updated ($numrows) or DBERROR:::\n$dberror\nSQL:::$sql";
				$this->logsObj->log_dberror($details);
			}
			
			//let 'em know.
			$retval = 0;
		}
		else {
			//worked.
			$this->db->commitTrans();
			
			//log it.
			$details = "Updated group #". $groupId  .": ";
			foreach($updates as $field=>$newValue) {
				//
				$details .= "\nfield=(". $field ."): oldvalue=(". $oldGroupData[$field] . 
					"), newValue=(". $newValue .")";
			}
			$this->logsObj->log_by_class($details, 'update');
			
			$retval = $numrows;
		}
		
		return($retval);
	}//end update_group()
	//=========================================================================
	
	
	
	//=========================================================================
	public function create_contact($fname,$lname, $email, $company=NULL) {
		if(strlen($fname) && strlen($lname) && strlen($email)) {
			//create the insert SQL.
			$sqlArr = array(
				'fname'	=> $fname,
				'lname'	=> $lname
			);
			$cleanStringArr = array(
				'fname'	=> 'sql',
				'lname'	=> 'sql'
			);
			
			//start a transaction...
			$this->db->beginTrans(__METHOD__);
			
			$sql = 'INSERT INTO contact_table '. string_from_array($sqlArr, 'insert', NULL, $cleanStringArr);
			if(!$this->run_sql($sql)) {
				//failure.
				$this->db->rollbackTrans();
				$details = __METHOD__ .": failed to insert data (". $this->lastNumrows ."::: ". $this->lastError;
				$this->log_dberror($details);
				throw new exception($details);
			}
			else {
				//success: get the new contact_id.
				$sql = "SELECT currval('contact_table_contact_id_seq'::text)";
				if(!$this->run_sql($sql)) {
					//failure!
					$this->db->rollbackTrans();
					$details = __METHOD__ .": insert was successful, but could not retrieve new contact_id (". $this->lastNumrows .")::: ". $this->lastError;
					$this->log_dberror($details);
					throw new exception($details);
				}
				else {
					//retrieve the data.
					$data = $this->db->farray();
					$retval = $data[0];
					
					//before completing, let's create the primary email address.
					$contactObj = new contactClass($this->db);
					$contactObj->set_contact_id($retval);
					$contactObj->create_contact_email($email, TRUE);
					$this->db->commitTrans();
					
					$this->logsObj->log_by_class("Created new contact (". $retval .")");
				}
			}
		}
		else {
			$details = __METHOD__ .": not enough information, be sure to include fname, lname, and email";
			$this->logsObj->log_by_class($details, 'error');
			throw new exception($details);
		}
		
		return($retval);
	}//end create_contact()
	//=========================================================================
	
	
	
	//=========================================================================
	private function add_attribute($contactId, $name, $value) {
		$sql = "SELECT contact_update_attribute($contactId, '". $name ."','". cleanString($value,'sql') ."')";
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows !== 1) {
			//failed.
			$details = __METHOD__ .": failed (". $numrows .")::: ". $dberror;
			$this->log_dberror($details);
			throw new exception($details);
		}
		else {
			//okay.
			$data = $this->db->farray();
			$retval = $data[0];
		}
		
		return($retval);
	}//end add_attribute()
	//=========================================================================
	
}
?>