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

//TODO: convert all methods to use $this->helpdeskId...?

class helpdeskClass {
	
	var $db;				//database handle.
	var $helpdeskId	= NULL;		//bug/helpdesk
	public $recordTypeId;
	private $logCategoryId;
	private $allowedFields;
	
	protected $logsObj;
	
	
	protected $updateableFieldsArr = array();
	protected $restrictedFieldsArr = array();
	protected $cleanStringArr = array();
	protected $insertFieldsArr = array();
	
	private $lastRecordId;
	private $internalRecordId;
	private $cache = array();
	
	//================================================================================================
	/**
	 * CONSTRUCTOR.
	 */
	function helpdeskClass(cs_phpDB $db) {
		
		if(is_numeric(LOGCAT__HELPDESK)) {
			$this->logCategoryId = LOGCAT__HELPDESK;
		}
		else {
			throw new exception(__METHOD__ .": no valid log_category_id defined for helpdesk: did you complete setup?");
		}
		
		if(is_numeric(RECTYPE__HELPDESK)) {
			$this->recordTypeId = RECTYPE__HELPDESK;
		}
		else {
			throw new exception(__METHOD__ .": no valid record_type_id defined for helpdesk: did you complete setup?");
		}
		
		
		//check to see if the database object is valid.
		if(is_object($db) && $db->is_connected()) {
			$this->db = $db;
		} else {
			exit("no database!!!");
		}
		
		//create the logging object.
		$this->logsObj = new logsClass($this->db, $this->logCategoryId);
		
		$this->allowedFields = array(
			"name"				=> "sql",
			"subject"			=> "sql",
			"leader_contact_id"	=> "numeric",
			"ancestry"			=> "sql",
			"ancestry_level"	=> "numeric",
			"start_date"		=> "datetime",
			"deadline"			=> "datetime",
			"status_id"			=> "numeric",
			"priority"			=> "numeric",
			"group_id"			=> "numeric",
			"progress"			=> "numeric"
		);
		
		
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
	}//end helpdeskClass()
	//================================================================================================
	
	
	
	//================================================================================================
	function get_record($helpdeskId) {
		$criteria = array(
			'public_id'			=> $helpdeskId,
			'is_helpdesk_issue'	=> 't',
			'status_id'			=> 'all'
		);
		$tmp = $this->get_records($criteria);
		$retval = $tmp[$helpdeskId];
		
		//before continuing, get notes for this issue.
		$noteObj = new noteClass($this->db);
		$retval['notes'] = $noteObj->get_notes(array('record_id' => $retval['record_id']));
		
		return($retval);
	}//end get_record()
	//================================================================================================
	
	
	//================================================================================================
	/**
	 * A simple wrapper for update_record()... this is where the appendage to the remark should
	 * occur...
	 * 
	 * @param $helpdeskId			(int) ID to remark on.
	 * @param $remark			(str) Remark to add...
	 * @param $isSolution		(bool,optional) mark the item as a solution.
	 * @param $useRespondLink	(bool,optional) instead of saying "view" in the email sent, it will 
	 * 								say "respond" instead.
	 * 
	 * @return <SPECIAL: see returns for update_record()>
	 */
	function remark($helpdeskId, $remark, $isSolution=FALSE, $useRespondLink=FALSE) {
		//PRE-CHECK!!!
		if(strlen($remark) < 10) {
			$this->logsObj->log_by_class("remark(): not enough content::: $remark", 'error', NULL, $this->recordTypeId, $helpdeskId);
			return(-1);
		}
		
		#$retval = $this->update_record($helpdeskId, $updateArr, $appendRemark);
		$tmp = $this->get_record($helpdeskId);
		$noteObj = new noteClass($this->db);
		$noteData = array(
			'record_id'	=> $tmp['record_id'],
			
			//TODO: allow user to specify subject!
			'subject'		=> 'Comment',
			'body'			=> $remark,
			'is_solution'	=> cleanString($isSolution, 'boolean_strict')
		);
		$retval = $noteObj->create_note($noteData);
		
		if($retval > 0) {
			//send the submitter an email		
			$newRemarks = $remark;
			$emailTemplate = html_file_to_string("email/helpdesk-remark.tmpl");
			$linkAction = "view";
			if(!$isSolution) {
				if($useRespondLink) {
					$linkAction = "respond";
				}
				$parseArr = array(
					"newRemark"		=> $newRemarks,
					"linkAction"	=> $linkAction,
					"linkExtra"		=> "&check=". $this->create_md5($helpdeskId)
				);
				$parseArr = array_merge($tmp, $parseArr);
				
				//set the list of recipients.
				$recipientsArr = array();
				$myUserClass = new userClass($this->db,NULL);
				$assignedUserData = $myUserClass->get_user_info($tmp['assigned']);
				$recipientsArr[] = $assignedUserData['email'];
				if(strlen($_SESSION['login_email']) && $_SESSION['login_email'] != $tmp['email']) {
					$recipientsArr[] = $_SESSION['login_email'];
				}
				$recipientsArr[] = $tmp['email'];
			
				//okay, now send the email.  The function "send_email()" should be ensuring that all values in
				//	the recipients array are valid, and there's no dups.
				$sendEmailRes = send_email($recipientsArr, "Update to Helpdesk Issue #$helpdeskId", $emailTemplate, $parseArr);
				
				//log who we sent the emails to.
				$details = 'Sent notification(s) of remark to: '. $sendEmailRes;
				$this->logsObj->log_by_class($details, 'information', NULL, $this->recordTypeId, $helpdeskId);
			}
		}
		else {
			//something went wrong.
			$this->logsObj->log_by_class("remark(): failed to update record ($retval)", 'error', NULL, $this->recordTypeId, $helpdeskId);
		}
		
		return($retval);
		
	}//end remark()
	//================================================================================================
	
	
	//================================================================================================
	/**
	 * Updates the given record with the "solved" status, and updates the "solution" field.
	 * 
	 * @param <$helpdeskId>		<int> helpdesk issue to update.
	 * @param <$solution>	<str> solution for the problem.
	 * 
	 * @return 0			FAIL: unable to solve... not sure why.
	 * @return 1			PASS: solved successfully.
	 */
	function solve($helpdeskId, $solution) {
		//PRE-CHECK!!!
		if(!is_numeric($helpdeskId) || !is_string($solution) || strlen($solution) < 10) {
			$retval = 0;
			if(strlen($solution) < 10) {
				$this->logsObj->log_by_class("solve(): not enough information to solve::: $solution", 'error', NULL, $this->recordTypeId, $helpdeskId);
				$retval = -1;
			}
		} else {
			//okay, everything checked out.  Do your thing.
			//NOTE::: projects using the original helpdesk code had the "minute" part of the time as "daylight savings time"...
			$updatesArr = array(
				"solution"		=> $solution,
				"solve_time"	=> date("Y-m-d H:m:s"),
				"solved"		=> $_SESSION['uid'],
				"status_id"		=> 4
			);
			
			//now, let's run the update method & tell 'em what happened.
			$createSolution = $this->remark($helpdeskId, $solution, TRUE);
			if($createSolution > 0) {
				$retval = $this->update_record($helpdeskId, $updatesArr);
				
				//only send an email if the update succeeded.
				if($retval == 1) {
					//send the submitter an email		
					$parseArr = $this->get_record($helpdeskId);
					$emailTemplate = html_file_to_string("email/helpdesk-solve.tmpl");
					
					//Parse-in the previous comments....
					//TODO: make this part of a different system, or something...
					$previousCommentsRow = "\n\n<!-- BEGIN issueNotes -->\n<div style=\"border-top:dotted #000 1px;\">" .
							"\n%%solutionIndicator%% <u>" .
							"[#%%note_id%%] <b>%%subject%%</b> (%%fname%% @ %%created%%)\n</u></div> " .
							"<pre>%%body%%</pre><!-- END  -->\n\n";
					$allComments = $parseArr['notes'];
					unset($parseArr['notes']);
					$myRow = "";
					if(is_array($allComments) && count($allComments)) {
						foreach($allComments as $noteId=>$data) {
							$data['solutionIndicator'] = "";
							if($data['is_solution'] == 't') {
								$data['solutionIndicator'] = '[X]';
							}
							$data['body'] = cleanString($data['body'], 'htmlentity_plus_brackets');
							$myRow .= mini_parser($previousCommentsRow, $data, '%%', '%%');
						}
					}
					$parseArr['remark'] = $myRow;
					
					$recipientsArr = array();
					if($_SESSION['login_email'] != $parseArr['email']) {
						$recipientsArr[] = $_SESSION['login_email'];
					}
					$recipientsArr[] = $parseArr['email'];
					
					//send the submitter a notification.
					$sendEmailRes = send_email($recipientsArr, "Helpdesk Issue #$helpdeskId was Solved", $emailTemplate, $parseArr);
					
					$notifySubject = "[ALERT] Helpdesk Issue #$helpdeskId was Solved by ". $_SESSION['login_loginname'];
					$sendEmailRes .= ", ". send_email(HELPDESK_ISSUE_ANNOUNCE_EMAIL, $notifySubject, $emailTemplate, $parseArr);
					
					$this->logsObj->log_by_class("Solve notice sent to: ". $sendEmailRes, 'information', NULL, $this->recordTypeId, $helpdeskId);
					$this->logsObj->log_by_class("Solved issue #". $helpdeskId .": ". $parseArr['name'], 'report', NULL, $this->recordTypeId, $helpdeskId);
				}
				else {
					//log the problem.
					$this->logsObj->log_by_class("solve(): failed to update record ($retval)", 'report', NULL, $this->recordTypeId, $helpdeskId);
				}
			}
			else {
				//failed to create the solution remark.
				$this->logsObj->log_dberror("Unable to create solution note: (". $createSolution .")");
			}
		}
		return($retval);
	}//end solve()
	//================================================================================================
	
	
	
	//================================================================================================
	function create_record($dataArr) {
		
		//create the basic record first.
		if(!is_numeric($dataArr['priority'])) {
			$dataArr['priority'] = 9;
		}
		$dataArr['is_helpdesk_issue'] = 't';
		if(is_numeric($dataArr['parentRecordId'])) {
			$dataAr['project_id'] = $dataArr['parentRecordId'];
		}
		$newRecord = $this->internal_create_record($dataArr, TRUE);
		
		//retrieve the record, so we can get the public_id.
		$myNewRecordArr = $this->get_records(array('helpdesk_id' => $newRecord), NULL, FALSE);
		$tempKeysArray = array_keys($myNewRecordArr);
		$retval = $tempKeysArray[0];
		
		//now, let's tag it.
		if(isset($dataArr['initialTag']) && is_numeric($dataArr['initialTag'])) {
			$tagObj = new tagClass($this->db);
			$tagObj->add_tag($newRecord, $dataArr['initialTag']);
		}
		
		//determine what to do next...
		if(is_numeric($retval) && $retval > 0) {
			//got good data... get the note_id.
			
			//now send 'em an email about it.
			$emailTemplate = html_file_to_string("email/helpdesk-new.tmpl");
			$parseArr = $this->get_record($retval);
			
			$normalEmailExtra = NULL;
			if((strlen($_SESSION['login_email'])) && ($_SESSION['login_email'] != $parseArr['email'])) {
				send_email($_SESSION['login_email'], "Helpdesk Issue #$retval Created [for ".$parseArr['email']  ."]", $emailTemplate, $parseArr);
				$normalEmailExtra = " [registered by ". $_SESSION['login_loginname'] .": uid=". $_SESSION['login_id'] ."]";
			}
			send_email($parseArr['email'], "Helpdesk Issue #$retval Created". $normalEmailExtra, $emailTemplate, $parseArr);
			
			//now send the alert...
			$alehelpdeskubject = "[ALERT] Helpdesk Issue #$retval Created";
			send_email(HELPDESK_ISSUE_ANNOUNCE_EMAIL, $alehelpdeskubject, $emailTemplate, $parseArr);
			
			//log that it was created.
			$details = "Helpdesk Issue #$retval Created by (". $dataArr['email'] ."): ". $dataArr['name'];
			$this->logsObj->log_by_class($details, 'create', NULL, $this->recordTypeId, $retval);
			$this->logsObj->log_by_class($details, 'report', NULL, $this->recordTypeId, $retval);
		}
		else {
			//log the internal failure.
			$details = "Failed to create new record...";
			$this->logsObj->log_dberror($details);
		}
		
		return($retval);
	}//end create_record()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Returns an md5 sum to use for anonymous remarks: helps ensure the user has legitimate access to
	 * remark on the issue.  Not completely secure... but the only other way to allow them to remark on
	 * issues is to give the sales people logins to the project site.
	 * 
	 * @param $helpdeskId		<int> ID to lookup, so as to create the md5 from.
	 * 
	 * @return <string>		PASS: string is 32 characters & is the md5 sum requested.
	 * @return 0			FAIL: unable to create md5.
	 */
	function create_md5($helpdeskId) {
		$retval = 0;
		if(is_numeric($helpdeskId)) {
			$dataArr = $this->get_record($helpdeskId);
			if(is_array($dataArr) && strlen($dataArr['div2']) > 8) {
				//still okay.
				//TODO: make it more secure: it's md5'd to avoid them figuring out the date string...
				$retval = md5($dataArr['div2']);
			}
		}
		
		return($retval);
	}//end create_md5
	//================================================================================================
	
	
	//================================================================================================
	/**
	 * This returns a list of available TAGS (the "helpdesk_cat" table is deprecated)
	 */
	function get_category_list($selectThis=NULL) {
		//create a list of tags.
		$object = new tagClass($this->db);
		$tagList = $object->get_tag_list();
		
		//now create the list.
		$retval = array_as_option_list($tagList);
		return($retval);
	}//end get_category_list()
	//================================================================================================
	
	
	
	//=========================================================================
	/**
	 * Retrieve records from the main record_table.
	 * 
	 * TODO: make better headers.
	 * TODO: sanity check (pretty much copied from projectClass::list_projects()).
	 * TODO: consider caching, so rapid-fire record lookups aren't so database-intensive.
	 */
	public function get_records(array $critArr, array $primaryOrder=NULL, $filterArr=NULL) {
		
		if(is_array($filterArr)) {
			$critArr = array_merge($filterArr, $critArr);
		}
		
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
	protected function internal_create_record(array $data, $isHelpdeskIssue=FALSE) {
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
	}//end internal_create_record()
	//=========================================================================
	
	
}//end helpdeskClass{}
?>