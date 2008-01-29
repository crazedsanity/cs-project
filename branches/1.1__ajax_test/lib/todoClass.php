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
 
class todoClass {
	
	var $db					= NULL;
	var $projectId			= NULL;
	var $todoId				= NULL;
	var $lastError			= NULL;
	private $recordTypeId;
	
	/** Object for logging stuff */
	private $logsObj;
	
	
	//================================================================================================
	/** 
	 * The constructor. Duh.
	 */
	 function todoClass(&$db,$projectId=NULL,$todoId=NULL) {
		
		if(!is_object($db)) {
			throw new exception(__METHOD__ .": invalid database handle!");
		}
		$this->db = $db;
		
		//not *REQUIRED*, but useful.
		if(is_numeric($projectId) && $projectId > 0) {
			$this->projectId = $projectId;
		}
		
		//create the logging object.
		$this->logsObj = new logsClass($this->db, 'Todo');
	 }//end todoClass()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Extensible method to grab a list of todo's based upon the given criteria.
	 * 
	 * @param $critArr		<array> criteria to feed to string_from_array().
	 * @param $primaryOrder	<array> field=>asc/desc sort options.
	 * 
	 * @return 0			FAIL: unable to retrieve the list.
	 * @return <array>		PASS: list of todo's, keyed off their id.
	 */
	function get_todos(array $critArr, $primaryOrder=NULL, array $contactCrit=NULL) {
		
		if(is_array($primaryOrder)) {
			$arrayKeysArr = array_keys($primaryOrder);
			$arrayValsArr = array_values($primaryOrder);
			
			$orderBy = "t.". $arrayKeysArr[0] ." ". $arrayValsArr[0];
		}
		
		if(!preg_match('/todo_id/', $orderBy) || (!strlen($orderBy))) {
			if(!preg_match('/status/', $orderBy)) {
				$orderBy = create_list($orderBy, 'status_id');
			}
			if(!preg_match('/priority/', $orderBy)) {
				$orderBy = create_list($orderBy, 'priority ASC');
			}
			if(!preg_match('/name/', $orderBy)) {
				$orderBy = create_list($orderBy, "name");
			}
		}
		
		//TODO: change the database to have "project" or "projekt" or "projekte" or "project_id" be the column that indicates the project it's attached to.
		//if we've got a projectId set, be sure to use it.
		if(!is_array($critArr)) {
			$details = __METHOD__ .": no criteria";
			$this->logsObj->log_dberror($details);
			throw new exception($details);
		}
		
		//now, fix the index names if the field exists in both tables...
		$duplicateColumnsArr = array("record_id", "name", "contact_id", "progress");
		
		foreach($critArr as $field=>$value) {
			if(in_array($field, $duplicateColumnsArr)) {
				//
				$newKey = "t.". $field;
				$critArr[$newKey] = $value;
				unset($critArr[$field]);
			}
		}
		
		
		$criteria = string_from_array($critArr, "select");	"where ". $criteria;
		if(is_array($contactCrit)) {
			 $criteria .= ' AND ('. string_from_array($contactCrit, 'url', ' OR ') .')';
		}
				
		$query = "SELECT " .
				"t.*, u.username AS creator, au.username AS assigned_user, estimate_original, " .
				"estimate_current, estimate_elapsed, (estimate_current - estimate_elapsed) as hours_remaining, " .
				"stat.name as status_text, rec.name AS record_name, t.record_id, rec.public_id " .
			"FROM todo_table AS t " .
				"INNER JOIN user_table AS u ON (u.contact_id=t.creator_contact_id) " .
				"INNER JOIN status_table AS stat ON (t.status_id=stat.status_id) " .
				"LEFT OUTER JOIN record_table AS rec ON (rec.record_id=t.record_id AND rec.is_helpdesk_issue IS FALSE) " .
				"LEFT OUTER JOIN user_table AS au ON (t.assigned_contact_id=au.contact_id) " .
			"WHERE ". $criteria ." ORDER BY ". $orderBy;
		
		$this->db->exec($query);
		$numrows = $this->db->numRows();
		$dberror = $this->db->errorMsg();
		
		if($dberror || $numrows < 1) {
			//something bad happened, or no rows.
			if($dberror) {
				//log the problem.
				$this->logsObj->log_dberror(__METHOD__ .": dberror encountered::: $dberror\n$query");
			}
			$retval = 0;
		}
		else {
			//good data.
			$retval = $this->db->farray_fieldnames("todo_id",NULL,0);
			
			//set a list of fields that need to be rounded.
			$precisionRound = array('hours_remaining', 'work_days_remaining', 'original_hours', 'estimate_current');
			
			//format some of the fields...
			foreach($retval as $id=>$subData) {
				//if, for some reason, the title got into the database with 
				// bad characters, this will ensure they're parse properly.
				$retval[$id]['name'] = cleanString($subData['name'], "htmlentity");
				
				$retval[$id]['submit_date'] = parse_date_string($subData['submit_date'],TRUE);
				
				//round-out some numbers.
				foreach($precisionRound as $roundThisField) {
					$number = $retval[$id][$roundThisField];
					$retval[$id][$roundThisField] = number_format($number,2);
				}
				
				//retrieve any comments.
				$retval[$id]['comments'] = $this->get_comments($id);
			}
		}
		
		return($retval);
		
	}//end get_todos()
	//================================================================================================
	
	
	//================================================================================================
	/**
	 * A wrapper for get_todos() to retrieve a single todo's info.
	 * 
	 * @param $todoId		<int> id of the todo to retrieve.
	 * 
	 * @return <SPECIAL: returns the requested record using get_todos()>
	 */
	function get_todo($todoId=NULL) {
		if(!is_numeric($todoId)) {
			$retval = 0;
		}
		else {
			
			$retval = $this->get_todos(array("todo_id"=>$todoId));
			$retval = $retval[$todoId];
		}
		
		return($retval);
	}//end get_todo()
	//================================================================================================
	
	
	//================================================================================================
	/**
	 * Updates a single todo record with the specified data.
	 * 
	 * @param $todoId		<int>
	 * @param $updatesArr	<array>
	 * 
	 * @return 0			FAIL: unable to update.
	 * @return 1			PASS: update successful.
	 */
	function update_todo($todoId, $updatesArr) {
		
		//define a list of updateable fields, and how they should be cleaned.
		$cleanFieldsArr = array(
			"name"					=> "sql",
			"body"					=> "sql",
			"assigned_contact_id"	=> "numeric",
			"record_id"				=> "numeric",
			"started"				=> "sql",
			"deadline"				=> "sql",
			"status_id"				=> "numeric",
			"priority"				=> "numeric",
			"progress"				=> "numeric",
			#"estimate_original"		=> "numeric",
			"estimate_current"		=> "numeric",
			"estimate_elapsed"		=> "sql"
		);
		
		
		//do some automatic updates.
		$updatesArr = $this->check_for_auto_updates($todoId, $updatesArr);
		
		if(is_numeric($updatesArr['add_elapsed'])) {
			//they're adding time to elapsed.
			$this->add_elapsed($todoId, $updatesArr['add_elapsed'], $this->logsObj->defaultUid);
		}
		
		//get the old data for logging & such.
		$oldData = $this->get_todo($todoId);
		
		$sqlArr = array();
		foreach($updatesArr as $field=>$value) {
			if(isset($cleanFieldsArr[$field]) && !is_numeric($value) && $value == '') {
				$sqlArr[$field] = "NULL";
			}
			elseif(isset($cleanFieldsArr[$field])) {
				$sqlArr[$field] = $value;
			}
		}
		
		//create the update statement.
		$updateStr = string_from_array($sqlArr, "update", NULL, $cleanFieldsArr);
		$sql = "UPDATE todo_table SET $updateStr WHERE todo_id=$todoId";

		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		if($this->lastError || $numrows != 1) {
			//bad things...
			if($this->lastError) {
				$this->logsObj->log_dberror(__METHOD__ .": database error::: ". $this->lastError);
			}
			$retval = 0;
		}
		else {
			$retval = $numrows;
			
			//log the changes.
			$doNotLog = array('ext', 'comments');
			$changesCount = 0;
			$details = "Updated fields::: ";
			
			//define an array of realFieldname => whatTheCodeCallsTheField
			$translationArr = array(
				'anfang'	=> 'begin_date',
				'datum'		=> 'submit_date'
			);
			foreach($updatesArr as $field=>$newValue) {
				if(isset($translationArr[$field])) {
					//translate it into the fields that get_todos() uses (see it's query)
					$field = $translationArr[$field];
				}
				if((!in_array($field, $doNotLog)) && ($newValue != $oldData[$field])) {
					//create the details.
					$thisDetail = $field .": [". $oldData[$field] ."] => [". $newValue ."]";
					$details = create_list($details, $thisDetail, "\n");
					$changesCount++;
				}
			}
			
			if($changesCount > 0) {
				//log it!
				$this->logsObj->log_by_class($details, 'update', NULL, $this->recordTypeId, $todoId);
			}
		}
		return($retval);
		
	}//end update_todo()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Adds a comment to the existing comments for a todo.  It's fun.
	 * 
	 * @param $todoId		<int> id of todo that will be updated.
	 * @param $comment		<str> comment to add.
	 * 
	 * @return -1			FAIL: comment too short.
	 * @return <special see output of update_todo()>
	 */
	function add_comment($todoId, $comment, $subject=NULL) {
		$retval = NULL;
		if(strlen($comment) >1 && is_numeric($todoId)) {
			$todoData = $this->get_todo($todoId);
			if(is_array($todoData)) {
				//we're still good: setup the array of inserted information.
				if(is_null($subject) || !strlen($subject)) {
					$subject = 'Comment';
				}
				$sqlArr = array(
					'todo_id'				=> $todoId,
					'creator_contact_id'	=> $_SESSION['contact_id'],
					'subject'				=> $subject,
					'body'					=> $comment,
				);
				$cleanStringArr = array(
					'creator_contact_id'	=> 'numeric',
					'subject'				=> 'sql',
					'body'					=> 'sql'
				);
				
				//now run the insert.
				$sql = 'INSERT INTO '. TABLE_TODOCOMMENT .' '. string_from_array($sqlArr, 'insert', NULL, $cleanStringArr);
				
				$numrows = $this->db->exec($sql);
				$dberror = $this->db->errorMsg();
				
				if(strlen($dberror) || $numrows != 1) {
					//log it.
					$this->logsObj->log_dberror("Unable to insert comment: numrows=($numrows), dberror::: ". $dberror);
				}
				else {
					$retval = 1;
				}
			}
			else {
				throw new exception("Invalid todo (couldn't find record)");
			}
		}
		else {
			throw new exception("Comment too short, or invalid todoId.");
		}
		
		return($retval);	
	}//end add_comment()
	//================================================================================================
	
	
	
	//================================================================================================
	/** The work to insert a todo into the todo table.
	 * 
	 * @param $dataArr		<array> field=>value data to insert.
	 * 
	 * return -1			FAIL: insert seemed to work, but couldn't get note_id.
	 * @return 0			FAIL: unable to insert data.
	 * @return <n>			(hopefully) PASS: <n> is the note_id we just inserted.
	 */
	function create_todo($dataArr) {
		//define the fields that can actually be SPECIFIED by $dataArr:
		$reqFieldsArr = array(
			"name"				=> "sql",
			"body"				=> "sql", 
			"record_id"			=> "numeric",
			"started"			=> "sql",
			"estimate_original"	=> "decimal"
		);
		
		//make sure we've got a valid array.
		$matchingFieldsCount = count(array_intersect(array_keys($dataArr), array_keys($reqFieldsArr)));
		$requiredFieldsCount = count($reqFieldsArr);
		if(!is_array($dataArr) || (is_array($dataArr) && count($dataArr) < 1) || $matchingFieldsCount  != $requiredFieldsCount) {
			$this->lastError = "Precheck failed.  Check what was entered and try again. [fields: $matchingFieldsCount/$requiredFieldsCount]";
			$this->logsObj->log_by_class(__METHOD__ .": ". $this->lastError);
			return(0);
		}
		
		//make the body non-null.
		if(!strlen($dataArr['body'])) {
			$dataArr['body'] = '(N/A)';
		}
		
		$addFieldsArr = array(
			"creator_contact_id"	=> $_SESSION['contact_id'],
			"created"				=> "NOW()",
			"estimate_current"		=> $dataArr['estimate_original']
		);
		
		//okay, clean-up our given data...
		$cleanStringArr = array_merge($addFieldsArr, $reqFieldsArr);
		foreach($dataArr as $field=>$value) {
			$cleanStringArg = $reqFieldsArr[$field];
			if(!$cleanStringArg) {
				$cleanStringArr[$field] = "sql";
			}
			
			$insertArr[$field] = $value;
		}
		
		//merge it with the fields we manually set..
		$insertArr = array_merge($insertArr, $addFieldsArr);
		
		//make it into an insert statement...
		$insertStr = string_from_array($insertArr, "insert", NULL, $cleanStringArr, FALSE, TRUE);
		$sql = "INSERT INTO todo_table $insertStr";
		
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		//determine what to do next...
		if($this->lastError || $numrows != 1) {
			$this->logsObj->log_dberror(__METHOD__ .": invalid numrows ($numrows) or dberror::: ". $this->lastError);
			$retval = 0;
		}
		else {
			//got good data... get the note_id.
			$numrows = $this->db->exec("SELECT currval('todo_table_todo_id_seq')");
			$this->lastError = $this->db->errorMsg();
			
			//make sure we're still okay.
			if($this->lastError || $numrows != 1) {
				$this->logsObj->log_dberror(__METHOD__ .": invalid numrows($numrows) or dberror::: ". $this->lastError);
				$retval = -1;
			}
			else {
				$tmp = $this->db->farray();
				$retval = $tmp[0];
				$this->logsObj->log_by_class("Created new todo [todo_id=". $retval ."]", 'create');
			}
		}
		
		return($retval);
		
	}//end create_todo()
	//================================================================================================
	
	
	
	//================================================================================================
	private function check_for_auto_updates($todoId, $updatesArr) {
		//get info about the todo, so we know what to do...
		$todoData = $this->get_todo($todoId);
		
		//set variable for elapsed for later checking.
		$myElapsed = $todoData['estimate_elapsed'];
		if(is_numeric($updatesArr['add_elapsed'])) {
			//update the internal variable (the updates array will be changed later).
			$myElapsed = $this->get_sum_of_elapsed($todoId, $updatesArr['add_elapsed']);
			
			//check if the "estimate_elapsed" field contains more hours than were logged...
			if(($todoData['estimate_elapsed'] + $updatesArr['add_elapsed']) != $myElapsed) {
				//automatically update it.
				$offBy = (($todoData['estimate_elapsed'] + $updatesArr['add_elapsed']) - $myElapsed);
				$this->add_elapsed($todoId, $offBy, $this->logsObj->defaultUid, 'Automatic adjustment');
				$myElapsed = $this->get_sum_of_elapsed($todoId, $updatesArr['add_elapsed']);
			}
			
			$updatesArr['estimate_elapsed'] = $myElapsed;
		}
		elseif(!is_numeric($todoData['estimate_elapsed'])) {
			$myElapsed = 0;
			$updatesArr['estimate_elapsed'] = 0;
		}
		
		//set local variable for current estimate for later checking.
		$myCurrEstimate = $todoData['estimate_current'];
		if(is_numeric($updatesArr['estimate_current']) && $updatesArr['estimate_current'] > 0) {
			$myCurrEstimate = $updatesArr['estimate_current'];
		}
		else {
			unset($updatesArr['estimate_current']);
		}
		
		
		//if they're updating elapsed time, make sure estimate_current is... sane.
		if(($myElapsed) && ($myCurrEstimate < $myElapsed)) {
			//more time has elapsed than in the estimate?  update the estimate to match.
			$updatesArr['estimate_current'] = $myElapsed;
			$myCurrEstimate = $myElapsed;
		}
		
		//are they ENDING the todo?
		if($updatesArr['status_id'] == 4 || $updatesArr['progress'] >= 100) {
			//set it to 100% complete.
			$updatesArr['progress'] = 100;
			
			//set it as ended.
			$updatesArr['status_id'] = 4;
			
			//make "elapsed" match "estimate_current"
			$updatesArr['elapsed'] = $myCurrEstimate;
		}
		//is it set to "accepted"?
		elseif($updatesArr['status_id'] == 2) {
			//set it as 0% complete.
			$updatesArr['progress'] = 0;
		}
		else {
			//not ended, not re-opened: set the progress properly.
			$updatesArr['progress'] = (($myElapsed / $myCurrEstimate) * 100);
		}
		
		if(isset($updatesArr['assigned_contact_id']) && !is_numeric($updatesArr['assigned_contact_id'])) {
			$updatesArr['assigned_contact_id'] = "NULL";
		}
		
		return($updatesArr);
	}//end check_for_auto_updates()
	//================================================================================================
	
	
	
	//================================================================================================
	private function get_comments($todoId) {
		$sql = "SELECT tc.todo_comment_id, tc.subject, tc.body, (c.fname || ' ' || c.lname) as creator, " .
			"tc.created, tc.updated FROM todo_comment_table AS tc INNER JOIN contact_table AS c ON " .
			"(c.contact_id=tc.creator_contact_id) WHERE todo_id=". $todoId ." ORDER BY todo_comment_id ASC";
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		$retval = array();
		if(strlen($this->lastError) || $numrows < 1) {
			if(strlen($this->dberror)) {
				$this->logsObj->log_dberror(__METHOD__ .": ". $this->lastError);
			}
		}
		else {
			$retval = $this->db->farray_fieldnames('todo_comment_id', NULL, 0);
		}
		
		return($retval);
	}//end get_comments()
	//================================================================================================
	
	
	
	//=========================================================================
	/** 
	 * Logs the added "elapsed" info for the given todo_id.
	 */
	public function add_elapsed($todoId, $addElapsed, $uid, $systemNote=NULL)
	{
		$retval = 0;
		if(is_numeric($addElapsed)) {
			//log the item into our log_estimate_table.
			$sqlArr = array(
				'uid'				=> $uid,
				'todo_id'			=> $todoId,
				'add_elapsed'		=> $addElapsed
			);
			
			if(!is_null($systemNote) && strlen($systemNote)) {
				$sqlArr['system_note'] = $systemNote;
			}
			
			//create the SQL.
			$sql = "INSERT INTO log_estimate_table ". string_from_array($sqlArr, 'insert');
			
			//now run it.
			$numrows = $this->db->exec($sql);
			$this->lastError = $this->db->errorMsg();
			
			
			if(strlen($this->lastError) || $numrows !== 1) {
				//got a database error, or nothing was inserted...
				$details = __METHOD__ .": unable to insert (". $numrows .") or database error:::\n". $this->lastError;
				$this->logsObj->log_dberror($details);
			}
			else {
				//we're doing great!
				$retval = $numrows;
				
				//now update the todo's estimate_elapsed field.
				#$newElapsed = $this->get_sum_of_elapsed($todoId);
			}
		}
		
		return($retval);
	}//end add_elapsed()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_sum_of_elapsed($todoId, $addThis=NULL) {
		$retval = 0;
		
		$sql = "SELECT sum(add_elapsed) FROM log_estimate_table WHERE todo_id=". $todoId;
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		if(strlen($this->lastError) || $numrows != 1) {
			if(strlen($this->lastError)) {
				$details = __METHOD__ .": encountered error while trying to retrieve new sum of estimate_elapsed::: ". $this->lastError;
				$this->logsObj->log_dberror($details);
			}
			$retval = 0;
		}
		else {
			//retrieve our new sum of elapsed.
			$tmp = $this->db->farray();
			$retval = 0;
			if(is_numeric($tmp[0])) {
				$retval = $tmp[0];
			}
			
			if(!is_null($addThis) && is_numeric($addThis)) {
				$orig = $retval;
				$retval = ($retval + $addThis);
			}
		}
		
		return($retval);
	}//end get_sum_of_elapsed()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_hours_logged($todoId, $uid=NULL, $limit=NULL) {
		//retrieve data from log_estimate_table WHERE record_id=$todoId... sort by creation... limit?
		$criteria = array(
			'todo_id'	=> $todoId
		);
		if(!is_null($uid) && is_numeric($uid)) {
			$criteria['uid'] = $uid;
		}
		$sql = "SELECT le.*, u.username FROM log_estimate_table AS le INNER JOIN " .
			"user_table AS u USING (uid) WHERE ". string_from_array($criteria, 'select');
		if(is_numeric($limit) && $limit > 0) {
			$sql .= " LIMIT ". $limit;
		}
		
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		
		if($numrows < 1 || strlen($this->lastError)) {
			if(strlen($this->lastError)) {
				$details = __METHOD__ .": ". $this->lastError;
				$this->logsObj->log_dberror($details);
			}
			$retval = NULL;
		}
		else {
			$retval = $this->db->farray_fieldnames('log_estimate_id', NULL, 0);
			foreach($retval as $id=>$array) {
				$tmp = explode(".", $array['creation']);
				$retval[$id]['creation'] = $tmp[0];
			}
		}
		
		return($retval);
		
	}//end get_hours_logged()
	//=========================================================================
	
}//end todoClass{}
?>
