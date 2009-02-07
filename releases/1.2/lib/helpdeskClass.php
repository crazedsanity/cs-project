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

class helpdeskClass extends mainRecord {
	
	public $db;						//database handle.
	public $helpdeskId=null;
	private $allowedFields;
	
	protected $logsObj;
	
	public $lastContactId;
	
	//================================================================================================
	/**
	 * CONSTRUCTOR.
	 */
	function __construct(cs_phpDB $db) {
		
		//check to see if the database object is valid.
		if(is_object($db) && $db->is_connected()) {
			$this->db = $db;
		}
		else {
			throw new exception(__METHOD__ .": no database!!!");
		}
		
		//create the logging object.
		$this->logsObj = new logsClass($this->db, "Helpdesk");
		
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
		$this->isHelpdeskIssue=TRUE;
		parent::__construct();
	}//end __construct()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * The generic, (hopefully) extensible method to retrieve helpdesk records.  Couldn't think of
	 * a better name to use.
	 * 
	 * @param $critArr			<array> Main criteria to use... 
	 * @param $primaryOrder		<array>
	 * @param $filterArr		<array>
	 */
	function get_records($critArr=NULL, $primaryOrder=NULL, $filterArr=NULL) {
		//set some criteria, & use the parent class's method.
		$critArr['is_helpdesk_issue'] = 't';
		if(is_array($filterArr)) {
			$critArr = array_merge($filterArr, $critArr);
		}
		return(parent::get_records($critArr, $primaryOrder));
		
	}//end get_records()
	//================================================================================================
	
	
	
	//================================================================================================
	function get_record($helpdeskId) {
		if(is_numeric($helpdeskId) && $helpdeskId > 0) {
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
			
			//get users associated with this record...
			$retval['associatedUsers'] = $this->get_record_contact_associations($retval['record_id']);
		}
		else {
			$details = __METHOD__ .": invalid helpdeskId (". $helpdeskId .")";
			$this->logsObj->log_by_class($details, 'error');
			throw new exception($details);
		}
		
		return($retval);
	}//end get_record()
	//================================================================================================
	
	
	//================================================================================================
	/**
	 * Method to update a helpdesk issue.
	 * 
	 * @param $helpdeskId		<int> ID to update.
	 * @param $updatesArr	<array> field=>value list to update from.
	 * 
	 * @return 0			FAIL: unable to update.
	 * @return <n>			PASS: <n> indicates # of records updated...
	 */
	function update_record($helpdeskId, $updatesArr=NULL, $appendRemark=TRUE) {
		
		$retval = parent::update_record(array('public_id' => $helpdeskId, 'is_helpdesk_issue' => 't', 'status_id' => 'all'), $updatesArr);
		if($retval && is_numeric($updatesArr['leader_contact_id']) && $updatesArr['leader_contact_id'] > 0) {
			$recData = $this->get_record($helpdeskId);
			$linkObj = new recordContactLink($this->db);
			$linkObj->add_link($recData['record_id'], $updatesArr['leader_contact_id']);
		}
		
		return($retval);
	}//end update_record()
	//================================================================================================
	
	
	//================================================================================================
	/**
	 * Create a remark on the given issue.
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
			$this->logsObj->log_by_class(__METHOD__ .": not enough content to remark on [helpdesk_id=". $helpdeskId."] ::: $remark", 'error');
			#return(-1);
			$retval = -1;
		}
		else {
		//start a transaction so if one part fails, they all fail.
		$this->db->beginTrans();
		
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
		
		if(is_numeric($noteObj->lastContactId) && $noteObj->lastContactId > 0) {
			$this->lastContactId = $noteObj->lastContactId;
			$recordContactLink = new recordContactLink($this->db);
			$recordContactLink->add_link($tmp['record_id'], $noteObj->lastContactId);
		}
		
		if($retval > 0) {
			//send the submitter an email		
			$newRemarks = $remark;
			$emailTemplate = html_file_to_string("email/helpdesk.tmpl");
			$linkAction = "view";
			
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
			$subject = "Helpdesk Issue #". $helpdeskId ." -- ". $tmp['name'];
			$sendEmailRes = send_email($recipientsArr, $subject, $emailTemplate, $parseArr);
			
			//log who we sent the emails to.
			$details = 'Sent notification(s) of for [helpdesk_id='. $helpdeskId .'] remark to: '. $sendEmailRes;
			$this->logsObj->log_by_class($details, 'information', NULL, $this->recordTypeId, $helpdeskId);
			
			if($isSolution && strlen(constant('HELPDESK_ISSUE_ANNOUNCE_EMAIL'))) {
				$subject = '[ALERT] Helpdesk Issue #'. $helpdeskId .' was SOLVED';
				if(strlen($_SESSION['login_username'])) {
					$subject .= ' by '. $_SESSION['login_username'];
				}
				$subject .= " -- ". $tmp['name'];
				$sendEmailRes = send_email(HELPDESK_ISSUE_ANNOUNCE_EMAIL, $subject, $emailTemplate, $parseArr);
				$details = 'Sent notifications of SOLUTION for [helpdesk_id='. $helpdeskId .'] to: '. $sendEmailRes;
				$this->logsObj->log_by_class($details, 'information');
				
				$this->solve();
			}
			$this->db->commitTrans();
		}
		else {
			$this->rollbackTrans();
			//something went wrong.
			$this->logsObj->log_by_class(__METHOD__ .": failed to remark on [helpdesk_id=". $helpdeskId ."] (". $retval .")", 'error');
		}
		}
		
		$this->gfObj->debug_print(__METHOD__ .": result=(". $retval .")",1);
		
		return($retval);
		
	}//end remark()
	//================================================================================================
	
	
	//================================================================================================
	/**
	 * Marks the issue as solved (does NOT handle marking any notes as solutions).
	 * 
	 * @param <$helpdeskId>		<int> helpdesk issue to update.
	 * @param <$solution>	<str> solution for the problem.
	 * 
	 * @return 0			FAIL: unable to solve... not sure why.
	 * @return 1			PASS: solved successfully.
	 */
	protected function solve() {
		//PRE-CHECK!!!
		if(!is_numeric($this->helpdeskId)) {
			$details = __METHOD__ .": not enough information to solve [helpdesk_id=" .
					$this->helpdeskId ."]::: $solution";
			$this->logsObj->log_by_class($details, 'error');
			throw new exception($details);
		}
		else {
			//okay, everything checked out.  Do your thing.
			$updatesArr = array(
				"progress"		=> 100,
				"status_id"		=> 4
			);
			
			$retval = $this->update_record($this->helpdeskId, $updatesArr);
			
			//log the action.
			if($retval == 1) {
				//send the submitter a notification.
				$this->logsObj->log_by_class("Solved issue #". $this->helpdeskId .": [helpdesk_id=". $this->helpdeskId ."]", 'report');
			}
			else {
				//log the problem.
				$this->logsObj->log_by_class(__METHOD__ .": failed to update [helpdesk_id=". 
					$this->helpdeskId ."]: (". $retval .")", 'error');
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
		
		$tagObj = new tagClass($this->db);
		if(is_array($dataArr['initialTag']) && count($dataArr['initialTag'])) {
			
			//get the list of tags, so we know what the total modifier is.
			$allTags = $tagObj->get_tag_list(TRUE);
			
			foreach($dataArr['initialTag'] as $id) {
				$dataArr['priority'] += $allTags[$id]['modifier'];
			}
			
			if($dataArr['priority'] > 9) {
				$dataArr['priority'] = 9;
			}
			elseif($dataArr['priority'] < 0) {
				$dataArr['priority'] = 0;
			}
			
		}
		$dataArr['is_helpdesk_issue'] = 't';
		$newRecord = parent::create_record($dataArr, TRUE);
		
		
		//TODO: deal with ancestry (associated parent record) here.
		if(is_numeric($dataArr['parentRecordId']) && $dataArr['parentRecordId'] > 0) {
			$updateRes = parent::update_record(array('record_id'=>$newRecord), array('parentRecordId' => $dataArr['parentRecordId']));
		}
		
		//retrieve the record, so we can get the public_id.
		$myNewRecordArr = parent::get_records(array('record_id' => $newRecord), NULL, FALSE);
		$tempKeysArray = array_keys($myNewRecordArr);
		$retval = $tempKeysArray[0];
		
		//associate the user that created it to the record, so they get notified. :) 
		$linkObj = new recordContactLink($this->db);
		$linkObj->add_link($newRecord, $myNewRecordArr[$retval]['creator_contact_id']);
		
		//now, let's tag it.
		if(is_array($dataArr['initialTag']) && count($dataArr['initialTag'])) {
			foreach($dataArr['initialTag'] as $id) {
				$tagObj->add_tag($newRecord, $id);
			}
		}
		
		//determine what to do next...
		if(is_numeric($retval) && $retval > 0) {
			//got good data... get the note_id.
			
			//now send 'em an email about it.
			$emailTemplate = html_file_to_string("email/helpdesk.tmpl");
			$parseArr = $this->get_record($retval);
			
			$normalEmailExtra = NULL;
			$emailAddressList = $linkObj->get_record_email_list($newRecord);
			
			## If there's an associated project, get the records for that, too...
			if(is_numeric($dataArr['parentRecordId'])) {
				$list = $this->get_record_contact_associations($dataArr['parentRecordId']);
				
				if(is_array($list)) {
					foreach($list as $myContactId=>$data) {
						if(!isset($emailAddressList[$myContactId])) {
							$emailAddressList[$myContactId] = $data['email'];
						}
					}
				}
			}
			
			if((strlen($_SESSION['login_email'])) && ($_SESSION['login_email'] != $parseArr['email'])) {
				$subject = "Helpdesk Issue #$retval [for ".$parseArr['email']  ."] -- ". $parseArr['name'];
				send_email($emailAddressList, $subject, $emailTemplate, $parseArr);
				$normalEmailExtra = " [registered by ". $_SESSION['login_loginname'] .": uid=". $_SESSION['login_id'] ."]";
			}
			else {
				$subject = "Helpdesk Issue #$retval -- ". $parseArr['name'];
				send_email($emailAddressList, $subject, $emailTemplate, $parseArr);
			}
			
			if(strlen(constant('HELPDESK_ISSUE_ANNOUNCE_EMAIL'))) {
				//send the alert!!!
				send_email(HELPDESK_ISSUE_ANNOUNCE_EMAIL, '[ALERT] '. $subject, $emailTemplate, $parseArr);
			}
			
			//log that it was created.
			$details = "Helpdesk Issue #". $retval ." ([helpdesk_id=". $retval ."]) Created by (". $dataArr['email'] ."): ". $dataArr['name'];
			$this->logsObj->log_by_class($details, 'create');
			$this->logsObj->log_by_class($details, 'report');
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
	function get_category_list($selectThis=NULL, $orderByMod=FALSE) {
		//create a list of tags.
		$object = new tagClass($this->db);
		$mainTagList = $object->get_tag_list(TRUE, $orderByMod);
		
		//create the "replacement array" and such.
		$tagList = array();
		foreach($mainTagList as $tagNameId => $subData) {
			$tagList[$tagNameId] = $subData['name'];
			$mod = $subData['modifier'];
			if($mod > 0) {
				if($mod == 1) {
					$mainTagList[$tagNameId]['bgcolor'] = '#CCC';
				}
				elseif($mod == 2) {
					$mainTagList[$tagNameId]['bgcolor'] = '#BBB';
				}
				else {
					$mainTagList[$tagNameId]['bgcolor'] = '#AAA';
				}
			}
			elseif($mod < 0) {
				if($mod == -1) {
					$mainTagList[$tagNameId]['bgcolor'] = 'yellow';
				}
				elseif($mod == -2) {
					$mainTagList[$tagNameId]['bgcolor'] = 'orange';
				}
				else {
					$mainTagList[$tagNameId]['bgcolor'] = 'red';
				}
			}
			else {
				$mainTagList[$tagNameId]['bgcolor'] = 'white';
			}
		}
		
		//now create the list.
		$templateString = "\t\t<option value='%%value%%' %%selectedString%% style=\"background-color:%%bgcolor%%\">%%display%% (%%modifier%%)</option>";
		$retval = array_as_option_list($tagList, $selectThis, 'select', $templateString, $mainTagList);
		return($retval);
	}//end get_category_list()
	//================================================================================================
	
	
	
	//=========================================================================
	function get_tasks($helpdeskId) {
		$retval = 0;
		$prefObj = new pref($this->db, $_SESSION['uid']);
		$taskDisplayPref = $prefObj->get_pref_value_by_name('projectDetails_taskDisplayOnlyMine');
		
		$taskObj = new taskClass($this->db);
		
		//attempt to get a list of the tasks...
		//TODO: change this reference to "publicId" instead of "projectId"
		$taskObj->projectId = $helpdeskId;
		
		
		$critArr = array("record_id"=>$this->get_parent_from_ancestry($this->get_ancestry($helpdeskId,TRUE),0));
		$contactCrit = NULL;
		if($taskDisplayPref != 'all') {
			if($taskDisplayPref == 'mine') {
				$contactCrit = array(
					't.creator_contact_id'	=> $_SESSION['contact_id'],
					't.assigned_contact_id'	=> $_SESSION['contact_id']
				);
			}
			elseif($taskDisplayPref == 'assigned') {
				$contactCrit = array(
					't.assigned_contact_id'	=> $_SESSION['contact_id']
				);
			}
		}
		$retval = $taskObj->get_tasks($critArr, NULL, $contactCrit);
			
		return($retval);
		
	}//end get_tasks()
	//=========================================================================
	
	
}//end helpdeskClass{}
?>