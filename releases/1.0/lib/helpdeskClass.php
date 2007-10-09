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
	
	var $db;				//database handle.
	var $helpdeskId	= NULL;		//bug/helpdesk
	public $recordTypeId;
	private $logCategoryId;
	private $allowedFields;
	
	protected $logsObj;
	
	//================================================================================================
	/**
	 * CONSTRUCTOR.
	 */
	function __construct(cs_phpDB $db) {
		
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
		}
		else {
			throw new exception(__METHOD__ .": invalid helpdeskId (". $helpdeskId .")");
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
			$subject = "Update to Helpdesk Issue #". $helpdeskId ." -- ". $tmp['name'];
			$sendEmailRes = send_email($recipientsArr, $subject, $emailTemplate, $parseArr);
			
			//log who we sent the emails to.
			$details = 'Sent notification(s) of remark to: '. $sendEmailRes;
			$this->logsObj->log_by_class($details, 'information', NULL, $this->recordTypeId, $helpdeskId);
			
			if($isSolution) {
				$subject = '[ALERT] Helpdesk Issue #'. $helpdeskId .' was SOLVED';
				if(strlen($_SESSION['login_username'])) {
					$subject .= ' by '. $_SESSION['login_username'];
				}
				$subject .= " -- ". $tmp['name'];
				$sendEmailRes = send_email(HELPDESK_ISSUE_ANNOUNCE_EMAIL, $subject, $emailTemplate, $parseArr);
				$details = 'Sent notifications of SOLUTION to: '. $sendEmailRes;
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
					//send the submitter a notification.
					$this->logsObj->log_by_class("Solved issue #". $helpdeskId, 'report', NULL, $this->recordTypeId, $helpdeskId);
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
		$newRecord = parent::create_record($dataArr, TRUE);
		
		
		//TODO: deal with ancestry (associated parent record) here.
		if(is_numeric($dataArr['parentRecordId']) && $dataArr['parentRecordId'] > 0) {
			$updateRes = parent::update_record(array('record_id'=>$newRecord), array('parentRecordId' => $dataArr['parentRecordId']));
		}
		
		//retrieve the record, so we can get the public_id.
		$myNewRecordArr = parent::get_records(array('record_id' => $newRecord), NULL, FALSE);
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
	
	
}//end helpdeskClass{}
?>