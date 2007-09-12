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
 

class noteClass {
	
	var $db			= NULL;	//database handle.
	var $projectId	= NULL; //ProjectID we're currently associated with.
	var $noteId		= NULL; //ID of the note we're currently editing.
	var $lastError	= NULL; //Indicates the last error encountered.


	//================================================================================================
	/** 
	 * The constructor. Duh.
	 */
	function noteClass(&$db, $projectId=NULL, $noteId=NULL) {
		if(!is_object($db)) {
			exit("noteClass(): invalid database handle!");
		}
		$this->db = $db;
		
		//not *REQUIRED*, but useful.
		if(is_numeric($projectId) && $projectId > 0) {
			$this->projectId = $projectId;
		}
		
	}//end noteClass()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Retrieves notes based upon the given criteria.
	 * 
	 * @param $critArr		<array> field=>value list of criteria to feed to string_from_array().
	 * @param $primaryOrder	<array> field=>asc/desc sorting parameters.
	 * 
	 * @return 0			FAIL: unable to retrieve notes.
	 * @return <array>		PASS: array contains records, indexed by id.
	 */
	function get_notes($critArr=NULL, $primaryOrder=NULL) {
		
		if(is_array($primaryOrder)) {
			$arrayKeysArr = array_keys($primaryOrder);
			$arrayValsArr = array_values($primaryOrder);
			
			$orderBy = $arrayKeysArr[0] ." ". $arrayValsArr[0];
		}
		else {
			//set an arbitrary orderBy.
			$orderBy = "note_id ASC";
		}
		
		//now, fix the index names if the field exists in both tables...
		$duplicateColumnsArr = array("record_id", "subject", "body", "creator_contact_id");
		
		foreach($critArr as $field=>$value) {
			if(in_array($field, $duplicateColumnsArr)) {
				//
				$newKey = "n.". $field;
				$critArr[$newKey] = $value;
				unset($critArr[$field]);
			}
		}
		
		$criteria = string_from_array($critArr, "select");
		
		$query = "SELECT n.*, c.fname, c.lname, contact_get_attribute(n.creator_contact_id, 'email') AS email " .
				"FROM note_table AS n INNER JOIN record_table AS r ON (n.record_id=r.record_id) " .
				"INNER JOIN contact_table AS c ON (n.creator_contact_id=c.contact_id)" .
				"WHERE ". $criteria ." ORDER BY $orderBy";
		
		$this->db->exec($query);
		$numrows = $this->db->numrows();
		$this->lastError = $this->db->errorMsg();
		
		if($this->lastError || $numrows < 1) {
			//something bad happened.
			//TODO: log a database error if there was one.
			if(strlen($this->lastError)) {
				throw new exception("get_notes(): ". $this->lastError);
			}
			$retval = 0;
		} else {
			//good data.
			$retval = $this->db->farray_fieldnames("note_id",NULL,0);
			
			foreach($retval as $id=>$arr) {
				//add some wrapping & cleaning (so the data appears properly)
				$retval[$id]['body'] = wordwrap($arr['body'], 95);
				$retval[$id]['subject'] = cleanString($retval[$id]['subject'], "htmlentity_plus_brackets");
				$retval[$id]['body'] = cleanString($retval[$id]['body'], "htmlentity_plus_brackets");
				
				//make the created & updated fields nicer.
				$cleanDatesArr = array('created', 'updated');
				foreach($cleanDatesArr as $dateField) {
					if(strlen($retval[$id][$dateField])) {
						$tmpDate = explode('.', $retval[$id][$dateField]);
						$retval[$id][$dateField] = $tmpDate[0];
					}
				}
			}
		}
		 
		return($retval);
	}//end get_notes()
	//================================================================================================
	
	
	//================================================================================================
	/**
	 * Wrapper method for get_notes() to retrieve a single record.
	 * 
	 * @param $noteId		<int> id of the note to retrieve.
	 * 
	 * @return <SPECIAL: see header for get_notes(); >
	 */
	function get_note($noteId) {
		$retval = $this->get_notes(array('note_id'=>$noteId));
		$retval = $retval[$noteId];
		return($retval);
	}//end get_note()
	//================================================================================================
	
	
	//================================================================================================
	/**
	 * Takes an array & updates based upon it.
	 * 
	 * @param $updatesArr	<array> Array of field=>value to feed to string_from_array().
	 * 
	 * @return 0			FAIL: Unable to update the record.
	 * @return 1			PASS: update successful!
	 */
	function update_note(array $updatesArr,$noteId=NULL) {
		if(!count($updatesArr) || (isset($updatesArr['record_id']) && !is_numeric($updatesArr['record_id']))) {
			//failure.
			//TODO: log an error here instead!!!
			throw new exception("update_note(): no updates to process, or invalid record_id!");
		}
		
		if(!is_null($noteId)) {
			$this->noteId = $noteId;
		}
		
		if(!is_numeric($this->noteId) || !is_array($updatesArr)) {
			return(0);
		}
		$updatesArr = array_change_key_case($updatesArr, CASE_LOWER);
		$allowedFieldsArr = array(
			"subject"				=> 'sql',
			"body"					=> 'sql',
			"creator_contact_id"	=> 'numeric', 
			"record_id"				=> 'numeric'
		);
		
		//NOTE: 
		//	div1 == date created.
		//	div2 == date updated.
		$finalUpdatesArr = array();
		foreach($updatesArr as $key=>$value) {
			if(isset($allowedFieldsArr[$key])) {
				$finalUpdatesArr[$key] = $value;
			} else {
				debug_print("excluding $key [$value]...");
			}
		}
		
		$updateStr = string_from_array($finalUpdatesArr, "update", NULL, $allowedFieldsArr, TRUE);
		$updateStr .= ", updated=NOW()";
		$sql = "UPDATE note_table SET $updateStr WHERE note_id=". $this->noteId;
		
		//run it...
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		if($this->lastError || $numrows != 1) {
			$retval = 0;
		}
		else {
			$retval = 1;
		}
		
		return($retval);
	}//end update_note()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Does all the work to insert a note into the notes table.
	 * 
	 * @param $dataArr		<array> field=>value data to insert.
	 * 
	 * return -1			FAIL: insert seemed to work, but couldn't get note_id.
	 * @return 0			FAIL: unable to insert data.
	 * @return <n>			(hopefully) PASS: <n> is the note_id we just inserted.
	 */
	function create_note($dataArr) {
		//define the fields that can actually be SPECIFIED by $dataArr:
		$reqFieldsArr = array(
			"subject"				=> "sql", 
			"body"					=> "sql", 
			"record_id"				=> "numeric"
		);
		
		//make sure we've got a valid array.
		$matchingFieldsCount = count(array_intersect(array_keys($dataArr), array_keys($reqFieldsArr)));
		$requiredFieldsCount = count($reqFieldsArr);
		if(!is_array($dataArr) || (is_array($dataArr) && count($dataArr) < 1) || $matchingFieldsCount  != $requiredFieldsCount) {
			return(0);
		}
		
		//data that needs to be appended.
		if(is_numeric($_SESSION['contact_id'])) {
			$creatorContactId = $_SESSION['contact_id'];
		}
		else {
			$creatorContactId = "0";
		}
		$addFieldsArr = array(
			"creator_contact_id"	=> $creatorContactId
		);
		$addFieldsCleaning = array(
			"creator_contact_id"	=> 'numeric' 
		);
		
		//merge it with the fields we manually set..
		$insertArr = array_merge($dataArr, $addFieldsArr);
		
		//make it into an insert statement...
		$insertStr = string_from_array($insertArr, "insert", NULL, $reqFieldsArr);
		#$insertStr .= ", ". string_from_array($addFieldsArr, 'insert', NULL, $addFieldsCleaning);
		$sql = "INSERT INTO note_table $insertStr";
	
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		//determine what to do next...
		if($this->lastError || $numrows != 1) {
			if(strlen($this->lastError)) {
				debug_print($sql);
				throw new exception("create_note(): ". $this->lastError);
			}
			$retval = 0;
		}
		else {
			//got good data... get the note_id.
			$numrows = $this->db->exec("SELECT currval('note_table_note_id_seq')");
			$this->lastError = $this->db->errorMsg();
			
			//make sure we're still okay.
			if($this->lastError || $numrows != 1) {
				$retval = -1;
			}
			else {
				$tmp = $this->db->farray();
				$retval = $tmp[0];
			}
		}
		
		return($retval);
		
	}//end create_note()
	//================================================================================================
	
}//end noteClass{}
?>
