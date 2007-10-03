<?php
/*
 * Created on Oct 3, 2007
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
			$retval = $this->convert_log_categories_and_classes();
			#$retval .= "<BR>\n". $this->create_record_type_data();
			#$retval .= "<BR>\n". $this->create_attributes();
			#$retval .= "<BR>\n". $this->create_anonymous_contact_data();
			#$retval .= "<BR>\n". $this->create_status_records();
			#$retval .= "<BR>\n". $this->create_tag_names();
			#$retval .= "<BR>\n". $this->build_preferences();
			#$retval .= "<BR>\n". $this->create_users();
			#$retval .= "<BR>\n". $this->create_user_group_records();
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
	private function convert_log_categories_and_classes() {
		$this->gfObj->debug_print(__METHOD__ .": starting... ");
		$retval = 0;
		$exception = NULL;
		
		//retrieve the log categories.
		$data = $this->get_data("SELECT * FROM log_category_table");
		
		if(is_array($data)) {
			foreach($data as $id=>$dataArr) {
				$name = $dataArr['name'];
				
				$insertStr = $this->gfObj->string_from_array($dataArr, 'insert', NULL, 'sql');
				//run an insert, capture the inserted id, and store it.
				$this->run_sql("INSERT INTO log_category_table ". $insertStr);
				
				//now get the inserted ID.
				$this->run_sql("SELECT log_category_id FROM log_category_table WHERE name='". $name ."'");
				$seqData = $this->db->farray();
				$this->configData['logcat__'. strtolower($name)] = $seqData[0];
				$retval++;
			}
			
			//now retrieve the classes & insert 'em into the new database.
			$classData = $this->get_data("SELECT * FROM log_class_table");
			
			if(is_array($classData)) {
				foreach($classData as $id => $dataArr) {
					$insertStr = $this->gfObj->string_from_array($dataArr, 'insert', NULL, 'sql');
					$this->run_sql("INSERT INTO log_class_table ". $insertStr);
					$retval++;
				}
				
				//now retrieve & insert the log events.
				$eventData = $this->get_data("SELECT * FROM log_event_table", 'log_event_id');
				
				if(is_array($eventData)) {
					foreach($eventData as $id => $dataArr) {
						$insertStr = $this->gfObj->string_from_array($dataArr, 'insert', NULL, 'sql');
						$this->run_sql("INSERT INTO log_event_table ". $insertStr);
						$retval++;
					}
				}
				else {
					$exception = "no log event data::: ". $this->gfObj->debug_print($eventData,0);
				}
			}
			else {
				$exception = "no data returned for classes::: ". $this->gfObj->debug_print($classData,0);
			}
		}
		else {
			$exception = "invalid data returned::: ". $this->gfObj->debug_print($data,0);
		}
		
		//now reset some sequences.
		$this->run_sql("SELECT setval('log_category_table_log_category_id_seq'::text, (SELECT max(log_category_id) FROM log_category_table))");
		$this->run_sql("SELECT setval('log_class_table_log_class_id_seq'::text, (SELECT max(log_class_id) FROM log_class_table))");
		$this->run_sql("SELECT setval('log_event_table_log_event_id_seq'::text, (SELECT max(log_event_id) FROM log_event_table))");
		
		if(!is_null($exception)) {
			throw new exception(__METHOD__ .": ". $exception);
		}
		
		$this->gfObj->debug_print(__METHOD__ .": retval=(". $retval .")");
		
		return("Successfully converted ". $retval ." records.");
	}//end convert_log_categories_and_classes()
	//=========================================================================
	
	
}//end convertDatabase{}

?>
