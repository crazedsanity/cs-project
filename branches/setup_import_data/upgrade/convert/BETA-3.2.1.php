<?php
/*
 * Created on Oct 3, 2007
 * 
 * TODO: set internal data 'converted_from' = (version_string)
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
			$this->db->beginTrans();
			$retval = $this->convert_data_part1();
			$retval .= "<BR>\n". $this->convert_record_table();
			
			//TODO: convert todo & todo comments.
			
			$retval .= "<BR>\n". $this->convert_data_part2();
		}
		catch(exception $e) {
			$retval = $e->getMessage();
		}
		
		$this->gfObj->debug_print(__METHOD__ .": returning::: ". $retval);
		$this->gfObj->debug_print(__METHOD__ .": config data::: ". $this->gfObj->debug_print($this->configData,0));
		
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
			'group_table',
			'user_table',
			'user_group_table',
			'pref_type_table',
			'pref_option_table',
			'user_pref_table',
			#'record_table',
			#'record_contact_link_table',
			#'note_table',
			#'todo_table',
			#'todo_comment_table',
			#'tag_table'
		);
		
		foreach($tables as $tableName) {
			$data = $this->get_data("SELECT * FROM ". $tableName);
			
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
					'is_helpdesk_issue'		=> 'bool',
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
	private function convert_data_part2() {
		$insertedRecords = 0;
		$tables = array(
			'record_contact_link_table',
			'note_table',
			#'todo_table',
			#'todo_comment_table',
			'tag_table'
		);
		
		foreach($tables as $tableName) {
			$data = $this->get_data("SELECT * FROM ". $tableName);
			
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
			}
		}
		
		$retval = __METHOD__ .": inserted (". $insertedRecords .") records";
		$this->gfObj->debug_print($retval);
		
		return($retval);
	}//end convert_data_part2()
	//=========================================================================
	
	
}//end convertDatabase{}

?>
