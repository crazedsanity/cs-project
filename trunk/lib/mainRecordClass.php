<?php
/*
 * Created on May 11, 2007
 * 
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */


class mainRecord {
	
	public $db;
	
	protected $updateableFieldsArr = array();
	protected $restrictedFieldsArr = array();
	protected $cleanStringArr = array();
	protected $insertFieldsArr = array();
	
	private $lastRecordId;
	private $internalRecordId;
	private $cache = array();
	
	//=========================================================================
	function __construct() {
		
		//these are fairly generic, & can be updated easily.
		$this->updateableFieldsArr = array(
			'group_id'			=> 'numeric',
			'leader_contact_id'	=> 'numeric',
			'status_id'			=> 'numeric',
			'priority'			=> 'numeric',
			'progress'			=> 'numeric',
			'start_date'		=> 'datetime',
			'deadline'			=> 'datetime',
			'ancestry'			=> 'sql',
			'ancestry_level'	=> 'numeric',
			'name'				=> 'sql',
			'subject'			=> 'sql'
		);
		
		//these fields should only be included in an update with care.
		$this->restrictedFieldsArr = array(
			'record_id'			=> 'numeric',
			'public_id'			=> 'numeric',
			'is_helpdesk_issue'	=> 'boolean_strict',
			'creator_contact_id'=> 'numeric',
			'last_updated'		=> 'datetime'
		);
		
		//build an all-encompassing array of fields & how they're cleaned.
		$this->cleanStringArr = array_merge($this->updateableFieldsArr, $this->restrictedFieldsArr);
		
		//setup the fields for inserting... presently, not much different.
		$this->insertFieldsArr = $this->cleanStringArr;
		$this->insertFieldsArr['public_id'] = 'none';
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Retrieve records from the main record_table.
	 * 
	 * TODO: make better headers.
	 * TODO: sanity check (pretty much copied from projectClass::list_projects()).
	 * TODO: consider caching, so rapid-fire record lookups aren't so database-intensive.
	 */
	protected function get_records(array $critArr, array $primaryOrder=NULL) {
		
		if(!isset($critArr['record_id']) && !isset($critArr['is_helpdesk_issue'])) {
			throw new exception(__METHOD__ .": is_helpdesk_issue undefined: ". debug_print($critArr,0));
		}
		
		if(is_array($primaryOrder) && count($primaryOrder)) {
			$orderBy = string_from_array($primaryOrder, 'order');
		}
		else {
			$orderBy = "priority ASC";
		}
		
		$parent = $critArr['parent'];
		unset($critArr['parent']);
		if(is_numeric($parent)) {
			if($parent == 0) {
				//
				$critArr['ancestry'] = "=record_id";
			}
			else {
				//tricksie things to facilitate a 'like' search.
				unset($critArr['ancestry']);
				$myAncestry = $this->get_ancestry($parent);
				$critArr['ancestry like'] = $myAncestry .":%";
			}
		}
		
		if(!is_array($critArr)) {
			$critArr = array();
		}
		
		//TODO: remove this crappy check.
		if(!strlen($critArr['keyword'])) {
			if(strtolower($critArr['status_id']) == 'all') {
				//specifically don't want to limit by status_id.
				unset($critArr['status_id']);
			}
			elseif(!isset($critArr['status_id'])) {
				//didn't specify... give 'em the default.
				$critArr['status_id'] = $GLOBALS['STATUS_NOTENDED'];
			}
			if(isset($critArr['status_id'])) {
				$critArr['s.status_id'] = $critArr['status_id'];
				unset($critArr['status_id']);
			}
		}
		
		
		$isHelpdesk = interpret_bool($critArr['is_helpdesk_issue'], array('f'=>0, 't'=>1), TRUE);
		if((strtolower($critArr['group_id']) === 'all') || $isHelpdesk) {
			//specifically don't care about group_id.
			unset($critArr['group_id']);
		}
		elseif((!isset($critArr['group_id']) || !is_numeric($critArr['group_id'])) && is_numeric($_SESSION['group_id'])) {
			//set it.
			$critArr['group_id'] = $_SESSION['group_id'];
		}
		if(isset($critArr['group_id'])) {
			$critArr['r.group_id'] = $critArr['group_id'];
			unset($critArr['group_id']);
		}
		
		
		//check if there's a filter.
		//TODO: make a better filtering system!!!
		$filterText = "";
		if(is_array($critArr) && count($critArr)) {
			if(strlen($critArr['keyword'])) {
				$keyword = "'%". strtolower(cleanString($critArr['keyword'],'sql')) ."%'";
				if($critArr['field'] == "all") {
					$critArr['r.name like'] = $keyword;
					$critArr['r.leader_contact_id like'] = $keyword;
					$critArr['u.username like'] = $keyword;
					$critArr['r.subject like'] = $keyword;
				
					$query = "WHERE is_helpdesk_issue IS ". cleanString($isHelpdesk, 'bool_strict') ." AND (lower(r.name) like ". $keyword ." OR r.leader_contact_id LIKE ". $keyword 
						." OR lower(u.username) LIKE ". $keyword ." OR lower(r.subject) LIKE ". $keyword .")";
				
				}
				
				if(isset($critArr['status_id'])) {
					$useThis = array('s.status_id' => $critArr['status_id']);
					$query .= " AND ". string_from_array($useThis, 'select');
				}
				
			}
			unset($critArr['keyword'], $critArr['field']);
			if(!isset($query)) {
				$query = "WHERE ". string_from_array($critArr, 'select');
			}
			
			//set a sub-query.
			unset($critArr['ancestry']);
			$filterText = string_from_array($critArr, 'select');
			$filterText = create_list("ancestry_level > r.ancestry_level", " AND ");
		}
		
		if(!is_array($primaryOrder)) {
			$primaryOrder = array(
				'r.priority'	=> 'ASC',
				'r.status_id'	=> 'ASC',
				'r.name'		=> 'ASC'
			);
		}
		
		$orderStr = string_from_array($primaryOrder, 'order');
		
		//TODO: when retrieving the list of projects, "record_get_num_children()" is unaware of the group, and can't even guess what status it should filter on... leads to the "Project #<blah> disappeared" problem. 
		$query = "SELECT r.*, record_get_num_children(record_id) as num_children, s.name as status_text, " .
				"u.username as assigned, contact_get_attribute(r.creator_contact_id, 'email') as email, " .
				"tag_list(r.record_id) " .
				"FROM record_table AS r INNER JOIN status_table AS s ON (s.status_id=r.status_id) " .
				" LEFT OUTER JOIN user_table AS u ON (u.contact_id=r.leader_contact_id) ". $query;
		$query .= " ". $orderStr;
		$numrows = $this->db->exec($query);
		$dberror = $this->db->errorMsg();
		
		
		if($dberror || $numrows < 1) {
			//no data.
			$retval = 0;
			if($dberror)
			{
				//log the problem.
				cs_debug_backtrace();
				throw new exception(__METHOD__ .": no data ($numrows) or dberror::: $dberror\nSQL::$query\n\n");
				$this->logsObj->log_dberror(__METHOD__ .": no data ($numrows) or dberror::: $dberror");
			}
		} else {
			//get the data.
			$retval = $this->db->farray_fieldnames("public_id",NULL,0);
			
			//format the start_date
			foreach($retval as $index=>$data) {
				$tmp = explode('.', $data['start_date']);
				if(preg_match('/00:00:00$/', $tmp[0])) {
					$tmp[0] = preg_replace('/ 00:00:00$/', '', $tmp[0]);
				}
				$retval[$index]['start_date'] = $tmp[0];
			}
			
			//set it into cache.
			if($numrows == 1) {
				$mainRecord = array_keys($retval);
				$mainRecord = $mainRecord[0];
				$this->cache[$mainRecord] = $retval[$mainRecord];
				
				//oh... the pain... 
				$this->lastRecordId = $mainRecord;
				$this->internalRecordId = $retval[$mainRecord]['record_id'];
			}
		}
		
		return($retval);
		
	}//end get_records()
	//=========================================================================
	
	
	
	//=========================================================================
	protected function update_record($recordId, array $updateArr) {
		$retval = NULL;
		if(is_numeric($recordId)) {
			//assume they're actually looking for record_id (not public_id + is_helpdesk_issue).
			$recordId = array(
				'record_id'	=> $recordId
			);
		}
		
		if(isset($recordId['public_id']) && (!is_numeric($recordId['public_id']) || !isset($recordId['is_helpdesk_issue']))) {
			//not enough information.
			throw new exception(__METHOD__ .": public_id is invalid (". $recordId['public_id'] ."), " .
					"or required field is_helpdesk_issue is not set (". $recordId['is_helpdesk_issue'] .")");
		}
		elseif(isset($recordId['record_id']) && !is_numeric($recordId['record_id'])) {
			//invalid record: don't bother looking it up, just fail.
			throw new exception(__METHOD__ .": invalid record_id (". $recordId .")");
		}
		else {
			//get the old data.
			$dataBeforeUpdate = $this->get_records($recordId);
			if(!is_array($dataBeforeUpdate)) {
				//couldn't find that record... sorry.
				debug_print($recordId);
				throw new exception(__METHOD__ .": unable to retrieve record for criteria: ". debug_print($recordId,0));
			}
			
			//Good to go: define all fields that can be updated.
			$updateableFields = array(
			);
			
			//now build the update string.
			$updateStr = $this->build_sql_string($updateArr, 'update');
			if(is_null($updateStr)) {
				//something failed.
				$this->logsObj->log_dberror(__METHOD__ .": failed to build SQL string... (". $updateStr .")");
			}
			else {
				//run the update.
				$this->db->beginTrans();
				$sql = "UPDATE record_table SET ". $updateStr ." WHERE record_id=". $this->internalRecordId;
				$numrows = $this->db->exec($sql);
				$dberror = $this->db->errorMsg();
				
				if(strlen($dberror) || $numrows !== 1) {
					//abort the transaction & log it (if necessary).
					$this->db->rollbackTrans();
					if(strlen($dberror)) {
						//log the error.
						$this->logsObj->log_dberror(__METHOD__ .": ". $dberror);
					}
					$retval = $numrows;
				}
				else {
					//good to go!!
					$this->db->commitTrans();
					$retval = $numrows;
				}
			}
		}
		
		return($retval);
	}//end update_record()
	//=========================================================================
	
	
	
	//=========================================================================
	private function build_sql_string(array $updateArr, $type='select') {
		$type = strtolower($type);
		$validTypes = array('select', 'update', 'insert');
		
		$retval = NULL;
		if(in_array($type, $validTypes)) {
			//good to go: figure out what to do here.
			$sqlArr = array();
			switch($type) {
				//-------------------------------------------------------------
				case 'select': {
					//that's fine... now only allow fields we've specified.
					$sqlArr = array_intersect_key($updateArr, $this->cleanStringArr);
					$definitionArr = $this->cleanStringArr;
				}
				break;
				//-------------------------------------------------------------
				
				
				//-------------------------------------------------------------
				case 'update': {
					//this one's special.
					
					//do some automatic updates.
					$updateArr = $this->perform_auto_updates($updateArr);
					
					//now remove unwanted fields.
					//TODO: allow internal updates of public_id (i.e. from "set_public_id()") to work, or otherwise facilitate internal useage of the "restricted" fields here.
					$sqlArr = array_intersect_key($updateArr, $this->cleanStringArr);
					$definitionArr = $this->cleanStringArr;
				}
				break;
				//-------------------------------------------------------------
				
				
				//-------------------------------------------------------------
				case 'insert': {
					//build an insert string.
					$insertArr = $this->perform_auto_updates($updateArr, TRUE);
					$sequenceName = SEQ_PROJECT;
					if(cleanString($insertArr['is_helpdesk_issue'], 'boolean_strict') == 'true') {
						$sequenceName = SEQ_HELPDESK;
					}
					$insertArr['public_id'] = "nextval('". $sequenceName ."'::text)";
					
					//remove unwanted fields.
					$sqlArr = array_intersect_key($insertArr, $this->insertFieldsArr);
					$definitionArr = $this->insertFieldsArr;
				}
				break;
				//-------------------------------------------------------------
			}
			
			//now, let's check to ensure there's something to work with.
			if(!count($sqlArr) || !is_array($sqlArr)) {
				//something bad happened.
				debug_print($sqlArr);
				debug_print($definitionArr);
				throw new exception(__METHOD__ .": no data left for (". $type .")!");
			}
			
			//now, let's create the actual SQL string.
			$retval = string_from_array($sqlArr, $type, NULL, $definitionArr);
			if(($retval === 0) || (strlen($retval) < 3)) {
				//something went wrong (didn't even get "x=y")
				throw new exception(__METHOD__ .": failed to build SQL, or string too short (". $retval .")");
			}
		}
		else {
			//invalid type!!!
			throw new exception(__METHOD__ .": invalid type defined (". $type .")");
		}
		
		return($retval);
	}//end build_sql_string()
	//=========================================================================
	
	
	
	//=========================================================================
	private function perform_auto_updates(array $updatesArr, $isNewRecord=FALSE) {
		//pull cache of the record.
		$myRecordData = $this->cache[$this->lastRecordId];
		
		/*
		 * Things to check for: 
		 *   - new/pending records that have been assigned
		 *   - assigned records that have been un-assigned
		 */
		
		
		// +++++ MAGIC UPDATES +++++
		
		if($isNewRecord) {
			//set the group_id properly.
			//TODO: set defaults SOMEWHERE in the code, like in the main site config (for non-authenticated users).
			$updatesArr['group_id'] = 2;
			if(isset($_SESSION['group_id'])) {
				$updatesArr['group_id'] = $_SESSION['group_id'];
			}
			
			//TODO: deal with ancestry (associated parent record) here.
		}
		else {
			
			//TODO: define what fields can be changed to NULL, and do something more, automatic (possibly specific to projects vs. issues)...
			if(isset($updatesArr['deadline']) && !strlen($updatesArr['deadline'])) {
				$updatesArr['deadline'] = 'NULL';
			}
			if(isset($updatesArr['start_date']) && !strlen($updatesArr['start_date'])) {
				$updatesArr['start_date'] = 'NULL';
			}
			if(!is_numeric($updatesArr['status_id'])) {
				//Changes for new/pending records...
				//got a new status, and the old status is new/pending
				if(isset($updatesArr['leader_contact_id']) && is_numeric($updatesArr['leader_contact_id'])) {
					//change the status to "Running/Accepted".
					$updatesArr['status_id'] = 2;
				}
				elseif(strlen($myRecordData['leader_contact_id']) && !strlen($updatesArr['leader_contact_id'])) {
					//no longer has a leader (has been unassigned)... change to "pending".
					$updatesArr['status_id'] = 1;
				}
			}
			
			//check for un-assignment.
			if(isset($updatesArr['leader_contact_id']) && (!is_numeric($updatesArr['leader_contact_id']) || is_null($updatesArr['leader_contact_id']))) {
				//trying to un-assign.
				$updatesArr['leader_contact_id'] = NULL;
			}
			
			//check if the ancestry should be updated (use the cached record data).
			$currentParentRecord = $this->get_parent_from_ancestry($myRecordData['ancestry']);
			if(isset($updatesArr['parentRecordId']) && !is_numeric($updatesArr['parentRecordId'])) {
				//remove link to the parent.
				$updatesArr['ancestry'] = $this->internalRecordId;
				$updatesArr['ancestry_level'] = 1;
			}
			elseif(isset($updatesArr['parentRecordId']) && is_numeric($updatesArr['parentRecordId']) && $updatesArr['parentRecordId'] != $currentParentRecord) {
				//TODO: figure out how to handle updates that would create orphans...
				//It's an issue: presently, there's no way to link issues to other issues... 
				$newAncestry = create_list($this->get_ancestry($updatesArr['parentRecordId']), $this->internalRecordId, ':');
				$verify = $this->verify_ancestry($newAncestry);
				
				if($verify === TRUE) {
					//good to go.
					$updatesArr['ancestry'] = $newAncestry;
					$updatesArr['ancestry_level'] = count(explode(':', $newAncestry));
				}
				else {
					//failed.
					throw new exception(__METHOD__ .": unable to verify ancestry (". $newAncestry .")");
				}
			}
			unset($updatesArr['parentRecordId']);
			
		}
		
		return($updatesArr);
	}//end perform_auto_updates()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Pass a string like "502:504:421:222:225:9376" to determine if each item is 
	 * a valid record.  Fails if *ANY* of the records are invalid.
	 */
	private function verify_ancestry($ancestryString) {
		if(!is_numeric($ancestryString) && (!strlen($ancestryString) || !preg_match('/:/', $ancestryString))) {
			//failed.
			$retval = NULL;
		}
		else {
			//break apart the string.
			$idArr = explode(':', $ancestryString);
			$critArr = array(
				'record_id'		=> $idArr
			);
			
			//now run the query.
			$sql = "SELECT * FROM record_table WHERE ". string_from_array($critArr, 'select');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(strlen($dberror) || $numrows !== count($idArr)) {
				if(strlen($dberror)) {
					//log the problem.
					$this->logsObj->log_dberror(__METHOD__ .": database error::: ". $dberror);
				}
				$retval = FALSE;
			}
			else {
				//good to go.
				$retval = TRUE;
			} 
		}
		
		return($retval);
	}//end verify_ancestry()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Retrieves all parents of the given project.
	 */
	function get_ancestry($projectId, $isHelpdeskIssue=FALSE) {
		if(!is_numeric($projectId)) {
			$projectId = $this->projectId;
		}
		$isHelpdeskIssue = cleanString($isHelpdeskIssue,'boolean_strict');
		
		$sql = "SELECT ancestry FROM record_table WHERE is_helpdesk_issue=". $isHelpdeskIssue ." AND public_id=". $projectId;
		
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows !== 1) {
			//something went wrong.
			//TODO: add error-logging.
			if(strlen($dberror)) {
				throw new exception(__METHOD__ .": failed (". $numrows .") with error::: ". $dberror);
			}
		}
		else {
			$data = $this->db->farray();
			$retval = $data[0];
		}
		
		return($retval);
		
	}//end get_ancestry()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Use the ancestry string (from the current project) to retrieve it's parent 
	 * record data. Given the string "88:99:11", goBackLevels=1 would get "99", 
	 * while, 2 would get "88", and 0 would get 11.
	 */
	public function get_parent_from_ancestry($ancestryString, $goBackLevels=1) {
		$retval = NULL;
		if(preg_match('/:/', $ancestryString)) {
			//break it into pieces...
			$ancestryArr = explode(':', $ancestryString);
			
			//now get the item.
			if(!is_numeric($goBackLevels) && strtolower($goBackLevels) == 'root') {
				//get the ROOT project (in '88:99:11', we'd return 88).
				$retval = $ancestryArr[0];
			}
			else {
				for($i=0;$i<=$goBackLevels;$i++) {
					//rip the last part off... 
					if(count($ancestryArr)) {
						$retval = array_pop($ancestryArr);
					}
					else {
						//no data left!
						throw new exception(__METHOD__ .": while going back (". $goBackLevels .") in (". $ancestryString ."), ran out of data!");
					}
				}
			}
		}
		
		return($retval);
	}//end get_parent_from_ancestry()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Get data for the parent indicated in $ancestryString.  If '997794:55:88' 
	 * is given, data for '55' will be retrieved.
	 */
	public function get_parent_record($ancestryString) {
		$recordId = $this->get_parent_from_ancestry($ancestryString);
		
		//now retrieve that record (make sure not to go through the parent class, 
		//	as that might accidentally set the "is_helpdesk_issue" flag which would
		//	not help as much as one might think).
		$data = self::get_records(array('record_id' => $recordId));
		$retval = $data[$this->lastRecordId];
		
		return($retval);
	}//end get_parent_record()
	//=========================================================================
	
	
	
	//=========================================================================
	protected function create_record(array $data, $isHelpdeskIssue=FALSE) {
		//alright, let's make sure we've got some data.
		if(!strlen($data['name']) || !strlen($data['subject'])) {
			//not enough information.
			throw new exception(__METHOD__ .": not enough information!");
		}
		else {
			#$this->db->beginTrans();
			
			//if it's a helpdesk issue, get (or create) a contact id!
			$data['creator_contact_id'] = contact_id_from_email($this->db, $data['email'], TRUE);
			
			//set it as a helpdesk issue (or not)
			$data['is_helpdesk_issue'] = $isHelpdeskIssue;
			
			//now build the SQL string. 
			$sql = "INSERT INTO record_table ". $this->build_sql_string($data, 'insert');
			debug_print($sql);
			
			//now run it.
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(strlen($dberror) || $numrows != 1) {
				//log the problem as needed.
				if(strlen($dberror)) {
					$this->logsObj->log_dberror(__METHOD__ .": ". $dberror);
				}
				$retval = 0;
			}
			else {
				//got it... get the record we created!!!
				$sql = "SELECT currval('". SEQ_MAIN ."'::text)";
				$numrows = $this->db->exec($sql);
				$dberror = $this->db->errorMsg();
				
				if(strlen($dberror) || $numrows != 1) {
					if(strlen($dberror)) {
						$this->logsObj->log_dberror(__METHOD__ .": failed to retrieve new sequence... ". $dberror);
					}
					$retval = 0;
				}
				else {
					//okay so far.
					$data = $this->db->farray();
					$retval = $data[0];
				}
			}
		}
		
		return($retval);
	}//end create_record()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Get details for all records for the given record_id (NOT PUBLIC_ID).
	 */
	protected function get_child_records($parentRecordId, $isHelpdeskIssue=FALSE, array $extraCrit=NULL) {
		if(is_null($parentRecordId) || !is_numeric($parentRecordId)) {
			//failure.
			throw new exception(__METHOD__ .": invalid parentRecordId ($parentRecordId)!");
		}
		else {
			//first, get the ancestry string for the given record.
			$myAncestry = $this->get_ancestry($parentRecordId);
			
			$recordCrit = array(
				#'record_id'			=> explode(':', $myAncestry),
				'ancestry like'		=> $myAncestry .":%",
				'ancestry_level'	=> count(explode(':', $myAncestry)) +1, 
				'is_helpdesk_issue'	=> cleanString($isHelpdeskIssue, 'boolean_strict'),
				'group_id'			=> 'all',
				'status_id'			=> 'all'
			);
			if(is_array($extraCrit)) {
				$recordCrit = array_merge($recordCrit, $extraCrit);
			}
			$retval = $this->get_records($recordCrit);
		}
		
		return($retval);
	}//end get_child_records()
	//=========================================================================
	
	
	
}
?>
