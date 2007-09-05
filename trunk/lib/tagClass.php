<?php
/*
 * Created on Feb 21, 2007
 * 
 * Class for tagging items, so they can be viewed in a different way: this way, for items pertaining to
 * the "S.A.L.E.S. Shortlist" can be viewed separately, and potentially even to non-authenticated users.
 * I'm hoping this will help to eliminate the use of excel spreadsheets, as it's frustrating to have to 
 * keep track of things in multiple locations.
 * 
 * SVN INFORMATION:::
 * SVN Signature: $Id:tagClass.php 8 2007-08-23 23:22:35Z crazedsanity $
 * Last Committted Date: $Date:2007-08-23 18:22:35 -0500 (Thu, 23 Aug 2007) $
 * Last Committed Path: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/lib/tagClass.php $
 * 
 */

class tagClass
{
	/** Database object. */
	private $db;
	
	/** Category for logging. */
	private $logCategoryId;
	
	/** Object for logging stuff */
	private $logsObj;
	
	//=========================================================================
	/**
	 * Constructor.  Requires connected phpDB{} object.
	 * 
	 * @param $db		(phpDB object) instance of phpDB class.
	 */
	public function __construct(phpDB $db)
	{
		
		if(is_numeric(LOGCAT__TAGS)) {
			$this->logCategoryId = LOGCAT__TAGS;
		}
		else {
			throw new exception(__METHOD__ .": no valid log_category_id defined for tags: did you complete setup?");
		}
		
		//set the internal database handle.
		$this->db = $db;
		
		//create the logging object.
		$this->logsObj = new logsClass($this->db, $this->logCategoryId);
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Get the entire list of available tags.
	 * 
	 * @param (void)		(void)
	 * 
	 * @return (array)		PASS: contains tag_name_id=>name array.
	 * @return (exception)	database error or no rows.
	 */
	public function get_tag_list()
	{
		$sql = "SELECT * FROM tag_name_table ORDER BY lower(name)";
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows < 1)
		{
			//tell 'em terrible things happened.
			//NOTE: if *ALL* tags are removed, this will *ALWAYS* get thrown.
			$details = "get_tag_list(): unable to retrieve list of tag names";
			$this->logsObj->log_dberror($details);
			throw new exception($details);
		}
		else
		{
			//good to go!
			$data = $this->db->farray_nvp("tag_name_id", "name");
			return($data);
		}
	}//end get_tag_list()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Retrieve all records associated with the given tag_name_id.
	 * 
	 * @param $tagNameId	(int) tag_name_id to search for.
	 * 
	 * @return (array)		PASS: indexed off "record_id".
	 * @return (exception)	FAIL: database error somewhere.
	 */
	public function get_records_for_tag($tagNameId)
	{
		$sql = "SELECT r.record_id, r.public_id, r.is_helpdesk_issue, r.name, r.priority, r.progress, r.ancestry, r.ancestry_level " .
			"FROM tag_table AS t " .
			"INNER JOIN tag_name_table AS tn ON (t.tag_name_id=tn.tag_name_id) " .
			"INNER JOIN record_table AS r ON (r.record_id=t.record_id) " .
			#"INNER JOIN estimate_table AS e ON (e.record_id=t.record_id)" .
			"WHERE t.tag_name_id=". $tagNameId ." " .
			"ORDER BY position ASC, t.tag_id DESC";
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows < 0)
		{
			//no data!
			$details = "get_records_for_tag(): no records ($numrows) or database error:::\n". $dberror;
			$this->logsObj->log_dberror($details);
			throw new exception($details);
		}
		elseif($numrows < 1)
		{
			//no data.  Just return a blank array.
			$retval = array();
		}
		else
		{
			//good to go: retrieve the data.
			$retval = $this->db->farray_fieldnames("record_id", NULL, 0);
			
			foreach($retval as $recId=>$array) {
				$isHelpdeskIssue = cleanString($array['is_helpdesk_issue'], 'bool_strict');
				$retval[$recId]['module'] = 'project';
				if($isHelpdeskIssue == 'true') {
					$retval[$recId]['module'] = 'helpdesk';
				}
			}
		}
		
		return($retval);
	}//end get_records_for_tag()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Show all the tags attached to a given record.
	 * 
	 * @param $recordId		(int) record_id to search table for
	 * 
	 * @return (array)		PASS: contains tag_name_id=>name array.
	 * @return NULL			FAIL: no records
	 * @return (exception)	FAIL: database error
	 */
	public function get_tags_for_record($recordId)
	{
		//
		$sqlArr = array
		(
			'record_id'			=> cleanString($recordId, 'numeric')
		);
		$sql = "SELECT tag_name_id, name FROM tag_name_table INNER JOIN tag_table USING (tag_name_id) WHERE ". string_from_array($sqlArr, 'select');
		
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows < 0)
		{
			//database error.
			if(strlen($dberror)) {
				$details = "get_tags_for_record(): invalid rows ($numrows) or database error:::\n$dberror";
				$this->logsObj->log_dberror($details);
			}
			$retval = NULL;
		}
		elseif($numrows == 0)
		{
			//no data.
			$retval = NULL;
		}
		else
		{
			//retrieve the data for returning.
			$retval = $this->db->farray_nvp('tag_name_id', 'name');
		}
		
		return($retval);
	}//end get_tags_for_record()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Shows all the tags NOT attached to the given record.
	 * 
	 * @param $recordId		(int) record_id in table to search for
	 * 
	 * @return (array)		PASS: data contains tag_id=>name array of tags that
	 * 							are not associated with the current record.
	 * @return (exception)	FAIL: the call to get_tag_list() had an unhandled
	 * 							exception.
	 */
	public function get_available_tags_for_record($recordId) {
		//well, first, let's get a listing of all the tags available.
		$allTagsList = $this->get_tag_list();
		
		//now, get all the tags that are associated with this record.
		$associatedTags = $this->get_tags_for_record($recordId);
		
		//set default return.
		$retval = $allTagsList;
		
		if(is_array($associatedTags))
		{
			//got some tags associated?  Cool: show only those that are NOT 
			//	associated with this record.
			$retval = array_diff($allTagsList, $associatedTags);
		}
		
		return($retval);
	}//end get_available_tags_for_record()
	//=========================================================================
	
	
	
	//=========================================================================
	public function add_tag($recId, $tagNameId)
	{
		//create the insert statement.
		$sqlArr = array
		(
			'record_id'			=> cleanString($recId, 'number'),
			'tag_name_id'		=> cleanString($tagNameId, 'number')
		);
		$sql = "INSERT INTO tag_table ". string_from_array($sqlArr, 'insert');
		
		//run it
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		//check for errors & set the return value.
		if(strlen($this->lastError) || $numrows !== 1)
		{
			//something bad happened.
			$details = "add_tag(): ". $this->lastError;
			$this->logsObj->log_dberror($details);
			$retval = 0;
		}
		else
		{
			//good to go.
			$retval = 1;
		}
		
		return($retval);
	}//end add_tag()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Delete the tag attached to this record.
	 * 
	 * @param $recId
	 * @param $tagNameId
	 * 
	 * @return 1			PASS: record deleted.
	 * @return (>1)			FAIL: too many records deleted (transaction was
	 * 							rolled-back).
	 * @return 0			FAIL: no record to delete.
	 * @return NULL			FAIL: database error (check $this->lastError)
	 */
	public function remove_tag($recId, $tagNameId) {
		//create the delete statement.
		$sqlArr = array
		(
			'record_id'			=> cleanString($recId, 'number'),
			'tag_name_id'		=> cleanString($tagNameId, 'number')
		);
		$sql = "DELETE FROM tag_table WHERE ". string_from_array($sqlArr, 'select');
		
		//start a transaction & run it.
		$this->db->beginTrans();
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		if(strlen($this->lastError) || $numrows !== 1)
		{
			//database error.
			$this->db->rollbackTrans();
			if(strlen($this->lastError))
			{
				//make sure it's apparent that something bad happened.
				$this->logsObj->log_dberror("remove_tag(): unable to delete ($numrows) or dberror::: ". $this->lastError);
				$retval = NULL;
			}
		}
		else
		{
			//good to go.
			$this->db->commitTrans();
		}
		
		return($numrows);
	}//end remove_tag()
	//=========================================================================
	
	
	
	//=========================================================================
	private function update_tag_record($critArr, array $changes)
	{
		$sql = "UPDATE tag_table SET ". string_from_array($changes, 'update') ."" .
				"WHERE ". string_from_array($critArr, 'select', NULL, 'numeric');
		
		//start a transaction & run the update.
		$numrows = $this->db->exec($sql);
		$this->lastError = $this->db->errorMsg();
		
		if(strlen($this->lastError) || $numrows !== 1)
		{
			//something bad happened.
			$retval = 0;
			if(strlen($this->lastError))
			{
				//make it apparent that something went wrong.
				$this->logsObj->log_dberror("update_tag_record(): unable to update ($numrows) or dberror::: ". $this->lastError);
				$retval = NULL;
			}
		}
		else
		{
			//good to go.
			$retval = $numrows;
		}
		
		return($retval);
	}//end update_tag_record
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * 
	 * 
	 * NOTE: this will *always* re-organize positions for ALL records within
	 * the given tag_name_id scope.
	 * NOTE2: yes, this is horribly complex... but it works.
	 */
	public function update_record_position_for_tag($tagId, $tagNameId, $upOrDown='up')
	{
		$upOrDown = strtolower($upOrDown);
		if(is_null($upOrDown) || ($upOrDown !== 'up' && $upOrDown !== 'down'))
		{
			//set a default
			$upOrDown = 'up';
		}
		
		//get all records for this tag.
		$allRecords = $this->get_records_for_tag($tagNameId);
		
		//NOTE: $allRecords isn't properly sorted (by position), this logic will BREAK.
		$position = 0;
		$positionToId = array();
		$currentPosition = NULL;
		foreach($allRecords as $index=>$subData)
		{
			$position++;
			$myTagId = $subData['tag_id'];
			$positionToId[$position] = $myTagId;
			//set current position.
			if($myTagId == $tagId)
			
			{
				$currentPosition = $position;
			}
		}
		
		//make sure we didn't encounter a nasty internal error...
		if(is_null($currentPosition) || !is_numeric($currentPosition))
		{
			//what can we do?
			$details = "update_record_position_for_tag(): couldn't find current position for tagId=($tagId)";
			$this->logsObj->log_dberror($details);
			throw new exception($details);
		}
		else
		{
			
			//we're still good.  Figure out what position it should be in.
			//REMEMBER: this is sorted ASCENDING, so moving up means the number is less, & vice-versa.
			if($upOrDown == 'down')
			{
				$newPosition = $currentPosition +1;
			}
			else
			{
				$newPosition = $currentPosition -1;
			}
			$idToSwap = $positionToId[$newPosition];
			
			//
			$newPositionArr = $positionToId;
			$newPositionArr[$newPosition] = $tagId;
			$newPositionArr[$currentPosition] = $idToSwap;
			
			//do the appropriate updates.
			$totalUpdates = 0;
			foreach($newPositionArr as $myPosition => $myId)
			{
				//only do an update if the proposed position does NOT match the current one.
				$originalPosition = $allRecords[$myId]['position'];
				if($myPosition != $originalPosition)
				{
					//set the criteria & what's being updated.
					$criteria = array
					(
						'tag_id'	=> $myId
					);
					$changes = array
					(
						'position'	=> $myPosition
					);
					$totalUpdates += $this->update_tag_record($criteria, $changes);
				}
			}
			$retval = $totalUpdates;
		}
		
		return($retval);
	}//end update_record_position_for_tag()
	//=========================================================================
	
	
	
	//=========================================================================
	public function create_new_tag_name($tagName)
	{
		//set a default return value.
		$retval = FALSE;
		if(strlen($tagName) && is_string($tagName))
		{
			//okay, insert it.
			$tagName = cleanString($tagName, 'sql');
			$sql = "INSERT INTO tag_name_table (name) VALUES ('". $tagName ."')";
			$numrows = $this->db->exec($sql);
			$this->lastError = $this->db->errorMsg();
			
			if(strlen($this->lastError) || $numrows !== 1)
			{
				//something failed.
				$details = "create_new_tag_name(): failed to insert record:::<BR>\n". $this->lastError;
				$this->logsObj->log_dberror($details);
				throw new exception($details);
			}
			else
			{
				//inserted.  Set the return value, then try to get the id.
				$retval = TRUE;
				
				$sql = "SELECT currval('tag_name_table_tag_name_id_seq'::text)";
				$numrows = $this->db->exec($sql);
				$this->lastError = $this->db->errorMsg();
				
				//only care if we found the value.
				if(!strlen($this->lastError) && $numrows == 1)
				{
					//good.  return the value.
					$data = $this->db->farray();
					$retval = $data[0];
				}
				else
				{
					//log the problem.
					$this->logsObj->log_dberror("create_new_tag_name(): unable to retrieve currval " .
							"($numrows) or dberror::: ". $this->lastError);
				}
			}
		}
		
		return($retval);
	}//end create_new_tag_name()
	//=========================================================================
}

?>