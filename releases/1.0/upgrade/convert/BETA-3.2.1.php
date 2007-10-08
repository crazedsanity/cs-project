<?php
/*
 * Created on Oct 3, 2007
 * 
 * TODO: set internal data 'converted_from' = (version_string)
 * TODO: retrieve required items for the config.xml file...
 * 		-- logcat__{loweredLogCategoryName}
 * 		-- rectype__{loweredRecordTypeName}
 * 
 * TODO: reset all sequences, including the two special ones, so new records can be created.
 * TODO: for speed, consider converting straight INSERT statements into a few COPY commands (unnecessary, unless multiple installs of BETA-3.2.1 are discovered)
 */


class convertDatabase {
	
	private $dbToConvert;
	private $configData = array();
	
	//=========================================================================
	public function __construct(cs_phpDB $dbToConvert, cs_phpDB $newDb) {
		$this->dbToConvert = $dbToConvert;
		$this->db = $newDb;
		
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = 1;
		
		if(!$this->db->is_connected() || !$this->dbToConvert->is_connected()) {
			throw new exception(__METHOD__ .": database is not connected");
		}
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function go() {
		//start converting data.
		try {
			$startTime = time();
			$this->db->beginTrans();
			$retval = $this->convert_data_part1();
			$retval .= "<BR>\n". $this->convert_record_table();
			$retval .= "<BR>\n". $this->convert_todo_data();
			$retval .= "<BR>\n". $this->convert_data_part2();
			$this->db->commitTrans();
			
			$this->db->beginTrans();
			$this->fix_stuff();
			
			$endTime = time();
			
			//TODO: retrieve data for the config file!
			
			$this->db->commitTrans();
		}
		catch(exception $e) {
			$retval = $e->getMessage();
		}
		
		$totalTime = ($endTime - $startTime);
		$totalMinutes = number_format(($totalTime / 60),2);
		
		$this->gfObj->debug_print(__METHOD__ .": config data::: ". $this->gfObj->debug_print($this->configData,0));
		$this->gfObj->debug_print(__METHOD__ .": took (". $totalTime .") seconds ( or about ". $totalMinutes ." minutes) returning::: ". $retval);
		
		return($retval);
	}//end go()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_data($sql, $indexByField=NULL, $atLeastNumRows=1) {
		if(!$this->dbToConvert->is_connected()) {
			$this->dbToConvert->connect(get_config_db_params());
		}
		$this->dbToConvert->beginTrans();
		$numrows = $this->dbToConvert->exec($sql);
		$dberror = $this->dbToConvert->errorMsg();
		
		if(strlen($dberror)) {
			$details = "DBERROR::: ". $dberror;
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: ". $details);
		}
		elseif(!is_null($atLeastNumRows) && $numrows < $atLeastNumRows) {
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: " .
				"rows affected didn't match expectation (". $numrows ." != ". $atLeastNumRows .")");
		}
		elseif(is_null($atLeastNumRows) && $numrows < 1) {
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: " .
				"invalid number of rows affected (". $numrows .")");
		}
		else {
			$retval = $this->dbToConvert->farray_fieldnames($indexByField);
		}
		$this->dbToConvert->rollbackTrans();
		
		return($retval);
	}//end get_data()
	//=========================================================================
	
	
	
	//=========================================================================
	private function run_sql($sql, $atLeastNumRows=1) {
		if(!$this->db->is_connected()) {
			$this->db->connect(get_config_db_params());
		}
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror)) {
			$details = "DBERROR::: ". $dberror;
			cs_debug_backtrace(1);
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: ". $details);
		}
		elseif(!is_null($atLeastNumRows) && $numrows < $atLeastNumRows) {
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: " .
				"rows affected didn't match expectation (". $numrows ." != ". $atLeastNumRows .")");
		}
		elseif(is_null($atLeastNumRows) && $numrows < 1) {
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: " .
				"invalid number of rows affected (". $numrows .")");
		}
		else {
			$retval = TRUE;
		}
		
		return($retval);
	}//end run_sql()
	//=========================================================================
	
	
	
	//=========================================================================
	private function convert_data_part1() {
		$insertedRecords = 0;
		$tables = array(
			'log_class_table',
			'log_category_table',
			'log_event_table',
			'record_type_table',
			'status_table',
			'attribute_table',
			'tag_name_table',
			'contact_table',
			'contact_attribute_link_table',
			'group_table',
			'user_table',
			'user_group_table',
			'pref_type_table',
			'pref_option_table',
			'user_pref_table'
		);
		
		foreach($tables as $tableName) {
			$data = $this->get_data("SELECT * FROM ". $tableName);
			
			$totalRecords = count($data);
			$totalTableInserts = 0;
			foreach($data as $index=>$tableData) {
				$sqlArr = array();
				foreach($tableData as $field=>$value) {
					if(!strlen($value) && (preg_match('/_id$/', $field) || preg_match('/date/', $field) || preg_match('/time/', $field))) {
						
					}
					else {
						$sqlArr[$field] = $value;
					}
				}
				$insertStr = $this->gfObj->string_from_array($sqlArr, 'insert', NULL, 'sql');
				try {
					$this->run_sql("INSERT INTO ". $tableName ." ". $insertStr);
				}
				catch(exception $e) {
					$this->gfObj->debug_print(__METHOD__ .": failed after inserting (". $insertedRecords .") records::: ". $e->getMessage());
					exit;
				}
				$insertedRecords++;
				$totalTableInserts++;
			}
			
			if($totalRecords !== $totalTableInserts) {
				throw new exception(__METHOD__ .": didn't insert all records, got (". $totalTableInserts ."/". $totalRecords .")");
			}
		}
		
		$retval = __METHOD__ .": inserted (". $insertedRecords .") records";
		$this->gfObj->debug_print($retval);
		
		return($retval);
	}//end convert_data_part1()
	//=========================================================================
	
	
	
	//=========================================================================
	private function convert_record_table() {
		$recordTableData = $this->get_data("SELECT * FROM record_table");
		$insertedRecords = 0;
		
		if(is_array($recordTableData)) {
			foreach($recordTableData as $num=>$data) {
				//fix certain columns...
				$cleanArr = array(
					'record_id'				=> 'int',
					'public_id'				=> 'int',
					'ancestry'				=> 'sql',
					'ancestry_level'		=> 'int',
					'group_id'				=> 'int',
					'creator_contact_id'	=> 'int',
					'leader_contact_id'		=> 'int',
					'status_id'				=> 'int',
					'priority'				=> 'int',
					'progress'				=> 'int',
					'start_date'			=> 'datetime',
					'deadline'				=> 'datetime',
					'last_updated'			=> 'datetime',
					'name'					=> 'sql',
					'subject'				=> 'sql',
					'is_helpdesk_issue'		=> 'sql',
					'is_internal_only'		=> 'bool'
				);
				
				foreach($cleanArr as $field=>$cleanArg) {
					if($cleanArg == 'int' && !strlen($data[$field])) {
						$data[$field] = NULL;
					}
					elseif($cleanArg == 'datetime') {
						if(!strlen($data[$field])) {
							$data[$field] = "NULL";
						}
						else {
							$data[$field] = "'". $this->gfObj->cleanString($data[$field], $cleanArg, 0) ."'::timestamp";
						}
					}
					else {
						$sqlQuotes = 1;
						if($cleanArg == "int") {
							$sqlQuotes = 0;
						}
						$data[$field] = $this->gfObj->cleanString($data[$field], $cleanArg, $sqlQuotes);
					}
				}
				
				$insertStr = $this->gfObj->string_from_array($data, 'insert');
				
				try {
					$this->run_sql("INSERT INTO record_table ". $insertStr);
				}
				catch(exception $e) {
					$this->gfObj->debug_print(__METHOD__ .": failed after inserting (". $insertedRecords .")... ". $e->getMessage());
					exit;
				}
				$insertedRecords++;
			}
			if($insertedRecords !== count($recordTableData)) {
				throw new exception(__METHOD__ .": didn't insert all records, got ". $insertedRecords ."/". count($recordTableData));
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to retrieve any records to convert");
		}
		
		$retval = __METHOD__ .": finished, inserted (". $insertedRecords .") of (". count($recordTableData) .")";
		$this->gfObj->debug_print($retval);
		
		return($retval);
		
	}//end convert_record_table()
	//=========================================================================
	
	
	
	//=========================================================================
	private function convert_todo_data() {
		
		$insertedRecords = 0;
		
		$todoTableData = $this->get_data("SELECT * FROM todo_table;");
		
		$cleanArr = array(
			'todo_id'				=> 'int',
			'creator_contact_id'	=> 'int',
			'name'					=> 'sql',
			'body'					=> 'sql',
			'assigned_contact_id'	=> 'int',
			'created'				=> 'datetime',
			'updated'				=> 'datetime',
			'deadline'				=> 'datetime',
			'started'				=> 'datetime',
			'status_id'				=> 'int',
			'priority'				=> 'int',
			'progress'				=> 'int',
			'record_id'				=> 'int',
			'estimate_original'		=> 'float',
			'estimate_current'		=> 'float',
			'estimate_elapsed'		=> 'float'
		);
		
		if(is_array($todoTableData)) {
			$totalTableRecords = count($todoTableData);
			foreach($todoTableData as $field => $data) {
				foreach($cleanArr as $field=>$cleanArg) {
					if($cleanArg == 'int' && !strlen($data[$field])) {
						$data[$field] = NULL;
					}
					elseif($cleanArg == 'datetime') {
						if(!strlen($data[$field])) {
							$data[$field] = "NULL";
						}
						else {
							$data[$field] = "'". $this->gfObj->cleanString($data[$field], $cleanArg, 0) ."'::timestamp";
						}
					}
					else {
						$sqlQuotes = 1;
						if($cleanArg == "int") {
							$sqlQuotes = 0;
						}
						$data[$field] = $this->gfObj->cleanString($data[$field], $cleanArg, $sqlQuotes);
					}
				}
				
				$insertStr = $this->gfObj->string_from_array($data, 'insert');
				
				try {
					$this->run_sql("INSERT INTO todo_table ". $insertStr);
				}
				catch(exception $e) {
					$this->gfObj->debug_print(__METHOD__ .": failed after inserting (". $insertedRecords .")... ". $e->getMessage());
					exit;
				}
				$insertedRecords++;
			}
			
			if($totalTableRecords !== $insertedRecords) {
				throw new exception(__METHOD__ .": failed to insert all records, got (". $insertedRecords ."/". $totalTableRecords .")");
			}
		}
		else {
			throw new exception(__METHOD__ .": no data to convert");
		}
		
		$retval = __METHOD__ .": converted ". $insertedRecords ."/". $totalTableRecords ." records";
		
		return($retval);
		
	}//end convert_todo_data()
	//=========================================================================
	
	
	
	//=========================================================================
	private function convert_data_part2() {
		$insertedRecords = 0;
		$tables = array(
			'record_contact_link_table',
			'note_table',
			#'todo_table',
			'todo_comment_table',
			'tag_table',
			'log_table',
			'log_estimate_table'
		);
		
		foreach($tables as $tableName) {
			$data = $this->get_data("SELECT * FROM ". $tableName);
			
			$totalRecords = count($data);
			$totalTableInserts = 0;
			foreach($data as $index=>$tableData) {
				
				$sqlArr = array();
				foreach($tableData as $field=>$value) {
					if(!strlen($value) && (preg_match('/_id$/', $field) || preg_match('/date/', $field) || preg_match('/time/', $field))) {
						
					}
					else {
						$sqlArr[$field] = $value;
					}
				}
				$insertStr = $this->gfObj->string_from_array($sqlArr, 'insert', NULL, 'sql');
				try {
					$this->run_sql("INSERT INTO ". $tableName ." ". $insertStr);
				}
				catch(exception $e) {
					$this->gfObj->debug_print(__METHOD__ .": failed after inserting (". $insertedRecords .") records::: ". $e->getMessage());
					exit;
				}
				$insertedRecords++;
				$totalTableInserts++;
			}
			
			if($totalRecords !== $totalTableInserts) {
				throw new exception(__METHOD__ .": didn't insert all records, got (". $totalTableInserts ."/". $totalRecords .")");
			}
		}
		
		$retval = __METHOD__ .": inserted (". $insertedRecords .") records";
		$this->gfObj->debug_print($retval);
		
		return($retval);
	}//end convert_data_part2()
	//=========================================================================
	
	
	
	//=========================================================================
	private function fix_stuff() {
		$this->run_sql("UPDATE record_type_table SET module='helpdesk' WHERE module='rts'");
		$this->run_sql("UPDATE pref_option_table SET effective_value='helpdesk' WHERE effective_value='rts'");
		
		$sequenceList = array(
			'attribute_table_attribute_id_seq',
			'contact_attribute_link_table_contact_attribute_link_id_seq',
			'contact_table_contact_id_seq',
			'group_table_group_id_seq',
			'internal_data_table_internal_data_id_seq',
			'log_category_table_log_category_id_seq',
			'log_class_table_log_class_id_seq',
			'log_estimate_table_log_estimate_id_seq',
			'log_event_table_log_event_id_seq',
			'log_table_log_id_seq',
			'note_table_note_id_seq',
			'pref_option_table_pref_option_id_seq',
			'pref_type_table_pref_type_id_seq',
			'record_contact_link_table_record_contact_link_id_seq',
			'record_table_record_id_seq',
			'record_type_table_record_type_id_seq',
			'special__helpdesk_public_id_seq',
			'special__project_public_id_seq',
			'status_table_status_id_seq',
			'tag_name_table_tag_name_id_seq',
			'tag_table_tag_id_seq',
			'todo_comment_table_todo_comment_id_seq',
			'todo_table_todo_id_seq',
			'user_group_table_user_group_id_seq',
			'user_pref_table_user_pref_id_seq',
			'user_table_uid_seq'
		);
		
		foreach($sequenceList as $sequenceName) {
			if($sequenceName == 'special__helpdesk_public_id_seq') {
				$sql = "SELECT setval('". $sequenceName ."'::text, (SELECT max(public_id) FROM " .
					"record_table WHERE is_helpdesk_issue IS TRUE));";
			}
			elseif($sequenceName == 'special__project_public_id_seq') {
				$sql = "SELECT setval('". $sequenceName ."'::text, (SELECT max(public_id) FROM " .
					"record_table WHERE is_helpdesk_issue IS FALSE));";
			}
			else {
				$bits = explode('_table_', $sequenceName);
				$tableName = $bits[0] .'_table';
				$columnName = preg_replace('/_seq$/', '', $bits[1]);
				
				$sql = "SELECT setval('". $sequenceName ."'::text, (SELECT max(". $columnName .") FROM " . $tableName ."))";
			}
			
			$this->run_sql($sql);
		}
	}//end fix_stuff()
	//=========================================================================
	
	
}//end convertDatabase{}

?>