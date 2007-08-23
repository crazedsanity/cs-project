<?php
/*
 * Created on Mar 8, 2007
 * 
 * 
 * Each class that's trying to log should have an internal var statically set to indicates what category 
 * it is: this allows them to call a method within this and tell it ONLY what "class" the log should be
 * under, so this class can determine the appropriate log_event_id.  This avoids having to hard-code 
 * too many id's that might need to be changed later.  Yay, dynamic code!
 * 
 * QUERY TO GET LAST COUPLE OF LOGS::::
 * select l.log_id, l.creation::date, lclas.name as class_name, lcat.name as category_name, e.description, 
 * u.username as user, u2.username as affected_user, l.details FROM log_table AS l INNER JOIN log_event_table 
 * AS e ON (l.log_event_id=e.log_event_id) INNER JOIN user_table AS u ON (l.uid=u.uid) INNER JOIN user_table 
 * AS u2 ON (l.affected_uid=u2.uid) INNER JOIN log_category_table AS lcat ON (e.log_category_id=lcat.log_category_id) 
 * INNER JOIN log_class_table AS lclas ON (e.log_class_id=lclas.log_class_id) WHERE e.log_category_id <> 10 
 * ORDER BY log_id DESC limit 5;
 * 
 * 
 * SVN INFORMATION:::
 * SVN Signature: $Id$
 * Last Committted Date: $Date$
 * Last Committed Path: $HeadURL$
 */


class logsClass
{
	/** Database handle */
	private $db;
	
	/** Cache of all records in the log_class_table */
	private $logClassCache = array();
	
	/** The log_category_id value to use, set on class creation. */
	private $logCategoryId = NULL;
	
	/** Default uid (users.id) to log under when no uid is available */
	public $defaultUid = 0;
	
	/** Category to use when logging a database error */
	private $databaseCategory = 1;
	
	//=========================================================================
	/**
	 * The constructor.
	 */
	public function __construct(phpDB &$db, $logCategoryId)
	{
		//assign the database object.
		$this->db = $db;
		
		//assign the log_category_id.
		if(!is_numeric($logCategoryId))
		{
			//oh, jeez.
			throw new exception(__METHOD__ .": FATAL: unable to set logCategoryId");
		}
		$this->logCategoryId = $logCategoryId;
		
		//check for a uid in the session.
		if(is_numeric($_SESSION['uid']))
		{
			//got an ID in the session.
			$this->defaultUid = $_SESSION['uid'];
		}
		
		//build our cache.
		$this->build_cache();
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	private function build_cache()
	{
		//build query, run it, check for errors.
		$sql = "SELECT log_class_id, lower(name) as name FROM log_class_table";
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows < 5)
		{
			//something bad happened.
			throw new exception(__METHOD__ .": not enough data ($numrows) or database error:::\n$dberror");
		}
		else
		{
			//got it.
			$this->logClassCache = $this->db->farray_nvp('name', 'log_class_id');
		}
	}//end build_cache()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_log_class_id($name)
	{
		$name = strtolower($name);
		
		//get the id.
		if(isset($this->logClassCache[$name]))
		{
			//set the id.
			$retval = $this->logClassCache[$name];
		}
		else
		{
			//ick.
			throw new exception(__METHOD__ .": unable to get log_class_id for ($name)". debug_print($this->logClassCache,0));
		}
		
		return($retval);
	}//end get_log_class_id()
	//=========================================================================
	
	
	
	//=========================================================================
	function get_log_event_id($logClassName)
	{
		$sqlArr = array
		(
			'log_class_id'		=> $this->get_log_class_id($logClassName),
			'log_category_id'	=> $this->logCategoryId
		);
		$sql = "SELECT log_event_id FROM log_event_table WHERE " .
			string_from_array($sqlArr, 'select', NULL, 'numeric');
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(!strlen($dberror) && $numrows == 0 && is_numeric($sqlArr['log_class_id']))
		{
			//no records & no error: create one.
			$retval = $this->auto_insert_record($sqlArr['log_class_id']);
		}
		elseif(strlen($dberror) || $numrows !== 1)
		{
			//database error... DIE.
			throw new exception(__METHOD__ .": database error:::\n$dberror\nSQL:::$sql");
		}
		else
		{
			//get the data & return it.
			$data = $this->db->farray();
			$retval = $data[0];
		}
		
		return($retval);
	}//end get_log_event_id()
	//=========================================================================
	
	
	
	//=========================================================================
	public function log_by_class($details, $className="error", $uid=NULL, $recTypeId=NULL, $recId=NULL)
	{
		//make sure we've got a uid to log under.
		if(is_null($uid) || !is_numeric($uid))
		{
			//set it.
			$uid = $this->defaultUid;
		}
		
		//determine the log_event_id.
		try {
			$logEventId = $this->get_log_event_id($className);
		} catch(Exception $e) {
			throw new exception(__METHOD__ .": while attempting to retrieve logEventId, encountered an exception:::\n". $e->getMessage()
			."\n\nCLASS: $className\nDETAILS: $details");
		}
		
		//check to see what uid to use.
		$myUid = $_SESSION['user_ID'];
		if(!is_numeric($myUid))
		{
			//use the internal default uid.
			$myUid = $this->defaultUid;
		}
		
		//okay, setup an array of all the data we need.
		$cleanStringArr = array(
			'log_event_id'	=> 'numeric',
			'group_id'		=> 'numeric',
			'uid'			=> 'numeric',
			'affected_uid'	=> 'numeric',
			'details'		=> 'sql'
		);
		$sqlArr = array
		(
			'log_event_id'	=> cleanString($logEventId, 'numeric'),
			'group_id'		=> $_SESSION['group_id'],
			'uid'			=> $myUid,
			'affected_uid'	=> $uid,
			'details'		=> $details
		);
		
		if(!is_null($recTypeId) && !is_null($recId)) {
			$sqlArr['record_type_id'] = $recTypeId;
			$sqlArr['record_id'] = $recId;
		}
		
		//build, run, error-checking.
		$sql = "INSERT INTO log_table ". string_from_array($sqlArr, 'insert', NULL, $cleanStringArr, TRUE);
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows !== 1)
		{
			//bad.
			throw new exception(__METHOD__ .": no records created ($numrows) or database error:::\n$dberror\n$sql");
		}
		else
		{
			//good to go.
			$retval = $numrows;
			
			//good to go: get the log_id that was just created.
			$sql = "SELECT currval('log_table_log_id_seq'::text)";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			//it's okay if it doesn't work.
			if(!strlen($dberror) && $numrows == 1)
			{
				//got it!
				$data = $this->db->farray();
				$retval = $data[0];
			}
		}
		
		return($retval);
	}//end log_by_class()
	//=========================================================================
	
	
	
	//=========================================================================
	public function log_dberror($details, $uid=NULL, $skipCurrentCatLog=FALSE)
	{
		//set the error for the current category.
		if(!$skipCurrentCatLog && ($this->logCategoryId !== $this->databaseCategory)) {
			//yep, log it!
			$this->log_by_class($details, 'error', $uid);
		}
		
		//now log the database error.
		$originalCategoryId = $this->logCategoryId;
		$this->logCategoryId = $this->databaseCategory;
		$retval = $this->log_by_class($details, 'error', $uid);
		$this->logCategoryId = $originalCategoryId;
		
		if(ISDEVSITE) {
			cs_debug_backtrace();
			throw new exception(__METHOD__ .": encountered error::: $details");
		}
		
		//give 'em the result.
		return($retval);
	}//end log_dberror()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Attempts to auto-recover if a class was requested that doesn't exist.
	 */
	private function auto_insert_record($logClassId)
	{
		//generate a default name
		$sql = "SELECT (select name FROM log_class_table WHERE log_class_id=". $logClassId .") || ': ' || " .
				"(select name FROM log_category_table WHERE log_category_id=". $this->logCategoryId .") || " .
				"' (auto-generated)' AS details;";
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows !== 1)
		{
			//something bad happened.  Unable to recover.
			throw new exception(__METHOD__ .": failed to recover with log_class_id=(". $logClassId .") " .
					"AND log_category_id=(". $this->logCategoryId .")");
		}
		else
		{
			//retrieve the record.
			$myData = $this->db->farray();
			$details = $myData[0];
			
			//create the sql array.
			$sqlArr = array
			(
				'log_class_id'		=> $logClassId,
				'log_category_id'	=> $this->logCategoryId,
				'description'		=> "'". cleanString($details, 'sql') ."'"
			);
			
			//now run the insert.
			$sql = 'INSERT INTO log_event_table '. string_from_array($sqlArr, 'insert');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(strlen($dberror) || $numrows !== 1)
			{
				//terrible.  So close to auto-recovery.
				throw new exception(__METHOD__ .": unable to recover, numrows=($numrows), dberror:::\n$dberror\n$sql");
			}
			else
			{
				//got it.  Retrieve the id.
				$sql = "SELECT currval('log_event_table_log_event_id_seq'::text)";
				$numrows = $this->db->exec($sql);
				$dberror = $this->db->errorMsg();
				
				if(strlen($dberror) || $numrows !== 1)
				{
					//couldn't get the value... but we inserted it....
					throw new exception(__METHOD__ .": unable to retrieve newly inserted id... " .
							"numrows=($numrows), dberror:::\n$dberror");
				}
				else
				{
					//got our value!!!
					$data = $this->db->farray();
					$retval = $data[0];
				}
			}
		}
		
		return($retval);
	}//end auto_insert_record()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_logs(array $criteria, array $orderBy=NULL, $limit=20, $excludeNavigation=TRUE)
	{
		//set a default for the limit.
		if(!is_numeric($limit) || $limit < 1) {
			//set it again.
			$limit = 20;
		}
		
		if(is_null($orderBy) || count($orderBy) < 1) {
			//set it.
			$orderBy = array(
				'log_id DESC'
			);
		}
		
		//set the fields that can be used, along with what alias for the table & cleaning type to use on the data.
		$allowedCritFields = array(
			'log_class_id'		=> array('cl',	'numeric'),
			'log_category_id'	=> array('ca',	'numeric'),
			'uid'				=> array('l',	'numeric'),
			'affected_uid'		=> array('l',	'numeric'),
			'record_type_id'	=> array('rt',	'numeric'),
			'group_id'			=> array('l',	'numeric'),
			'creation'			=> array('l',	'sql')
		);
		
		//loop through the data to create our cleaned, prefixed array of criteria.
		$sqlArr = array();
		foreach($criteria AS $field => $value) {
			//is this field in the allowed list?
			if(isset($allowedCritFields[$field])) {
				//grab data for this field.
				$myFieldData = $allowedCritFields[$field];
				$cleanStringArg = $myFieldData[1];
				
				//clean the data.
				if($field == 'creation' && is_numeric($value))
				{
					$value = cleanString($value, 'numeric');
					$cleanedData = ">= (NOW() - interval '". $value ." hours')";
				}
				else
				{
					$cleanedData = cleanString($value, $cleanStringArg);
				}
				
				//set the prefixed column name.
				$prefixedName = $myFieldData[0] .'.'. $field;
				
				//now add it to our array.
				$sqlArr[$prefixedName] = $cleanedData;
			}
		}
		
		
		//build the criteria.
		if($excludeNavigation)
		{
			$sqlArr['ca.log_category_id'] = '<>10';
		}
		$critString = string_from_array($sqlArr, 'select');
		
		//check if "timeperiod" is in there (it's special)
		if(isset($criteria['timeperiod']) && isset($criteria['timeperiod']['start']) && isset($criteria['timeperiod']['end']))
		{
			//add it in!
			$myTime = $criteria['timeperiod'];
			$addThis = "(l.creation >= '". $myTime['start'] ."'::date AND l.creation <= '". $myTime['end'] ."'::date + interval '1 day')";
			$critString = create_list($critString, $addThis, ' AND ');
		}
		
		$orderString = string_from_array($orderBy, 'limit');
		$sql = "select " .
				"l.creation, " .
				"l.log_id, " .
				"l.uid, " .
				"rt.name as record_type, " .
				"l.record_id, " .
				"cl.name AS class_name, " .
				"ca.name AS category_name, " .
				"ev.description, " .
				"l.details " .
			"FROM log_table AS l " .
				"INNER JOIN log_event_table AS ev ON (l.log_event_id=ev.log_event_id) " .
				"INNER JOIN log_class_table AS cl ON (cl.log_class_id=ev.log_class_id) " .
				"INNER JOIN log_category_table AS ca ON (ca.log_category_id=ev.log_category_id) " .
				"LEFT OUTER JOIN record_type_table AS rt ON (l.record_type_id=rt.record_type_id) " .
			"WHERE " . $critString . " " .
			"ORDER BY " .
				"log_id DESC " .
			"LIMIT ". $limit;
		
		//run it.
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		$retval = array();
		if(strlen($dberror) || $numrows < 0)
		{
			//log the problem, and make sure it's not logged twice.
			$this->log_dberror(__METHOD__ .": no rows ($numrows) or database error:::\n". $dberror, NULL, TRUE);
		}
		elseif($numrows > 0)
		{
			//retrieve the data.
			$retval = $this->db->farray_fieldnames('log_id', NULL, 0);
		}
		
		return($retval);
	}//end get_logs()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_recent_logs()
	{
		//set the criteria so we only get things within the last hour.
		$criteria = array(
			'creation'	=> 1
		);
		$retval = $this->get_logs($criteria, NULL, 20);
		return($retval);
	}//end get_recent_logs()
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_reports($startPeriod, $endPeriod, array $extraCrit=NULL)
	{
		//build the query.
		$timePeriod = array(
			'start' => $startPeriod,
			'end'	=> $endPeriod
		);
		
		$criteria = $timePeriod;
		if(is_array($extraCrit)) {
			$criteria = $extraCrit;
		}
		$criteria['timeperiod'] = $timePeriod;
		$criteria['log_class_id'] = 6;
		
		$myLogs = $this->get_logs($criteria,NULL,50);
		
		return($myLogs);
	}//end get_reports()
	//=========================================================================
	
	
}//end logsClass{}
?>