<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	
	$obj = new __setupDefaultValues();
	$obj->go();
}
else {
}


class __setupDefaultValues {
	
	
	private $db;
	private $gfObj;
	
	//=========================================================================
	public function __construct() {
		$this->db = new cs_phpDB;
		$this->gfObj = new cs_globalFunctions;
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function go() {
		
		try {
			$params = get_setup_data(1, 'data');
			$this->db->connect($params);
			$this->db->beginTrans();
			$retval = "Connected successfully to the database.";
			
			//now that we've connected, start doing stuff.
			$retval = $this->set_version();
			$retval = $this->create_log_categories_and_classes();
			
			//TODO: commit the transaction (any exception WILL keep this from running: I tested to make sure).
			//TODO: set results
			//TODO: set data
			//TODO: set next step as accessible
		}
		catch(exception $e) {
			//TODO: rollback the transaction
			$retval = "An error occurred: ". $e->getMessage();
		}
		
		$this->gfObj->debug_print(__METHOD__ .": RETURNING: <b>". $retval ."</b>");
		
		return($retval);
		
	}//end go()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Set version information into the database for future upgradeability.
	 */
	private function set_version() {
		//get the version string.
		$fullVersionString = read_version_file();
		
		$suffixData = explode('-', $fullVersionString);
		if(count($suffixData) == 2 && preg_match('/\./', $suffixData[0]) && !preg_match('/\./', $suffixData[1])) {
			//there's a suffix, and it doesn't contain periods (i.e. "1.0.0-ALPHA1")
			$suffix = $suffixData[1];
		}
		elseif(count($suffixData) == 1) {
			//no suffix.
			$suffix = "";
		}
		else {
			//there's a dash in the name, but it's invalid or contains periods (i.e. "BETA-1.0.0" or "1.0.0-ALPHA1.0")
			throw new exception(__METHOD__ .": version string is invalid (". $fullVersionString ."), suffix contains dashes, or there is a prefix");
		}
		
		//remove the suffix & parse it.
		$versionString = $suffixData[0];
		$versionData = explode('.', $versionString);
		
		
		$sqlData = array(
			'version_string'		=> $fullVersionString,
			'version_major'			=> $versionData[0],
			'version_minor'			=> $versionData[1],
			'version_maintenance'	=> $versionData[2],
			'version_suffix'		=> $suffix
		);
		
		$retval = 0;
		foreach($sqlData as $name => $value) {
			$sql = "SELECT internal_data_set_value('". $name ."', '". $value ."')";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval++;
			}
			else {
				throw new exception(__METHOD__ .": failed to set (". $name .") as (". $value .")::: ". $dberror);
			}
		}
		
		if($retval == count($sqlData)) {
			//okay, the final test: run a query that straps everything together, to ensure it all has the same version.
			$sql = "SELECT internal_data_get_value('version_major') || '.' || internal_data_get_value('version_minor') " .
				" || '.' || internal_data_get_value('version_maintenance') || '-' || internal_data_get_value('version_suffix') as text;";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$data = $this->db->farray();
				$dbVersionString = $data[0];
				
				if($dbVersionString === $fullVersionString) {
					//okay, one final test: check that the "version_string" in the database matches ours.
					$sql = "SELECT internal_data_get_value('version_string')";
					$numrows = $this->db->exec($sql);
					$dberror = $this->db->errorMsg();
					
					if(!strlen($dberror) && $numrows == 1) {
						$data = $this->db->farray();
						$dbVersionString = $data[0];
						
						if($dbVersionString === $fullVersionString) {
							$retval = "Successfully set version string";
						}
						else {
							throw new exception(__METHOD__ .": derived database version string (". $dbVersionString .") doesn't match our version (". $fullVersionString .")");
						}
					}
					else {
						throw new exception(__METHOD__ .": failed to retrieve full version_string from database::: ". $dberror ."<BR>\nSQL::: ". $sql);
					}
				}
				else {
					throw new exception(__METHOD__ .": derived database version string (". $dbVersionString .") doesn't match our version (". $fullVersionString .")");
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to retrieve derived database version string::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			//it's cryptic, but what should it really say???
			throw new exception(__METHOD__ .": internal error, checksum didn't match");
		}
		
		return($retval);
		
	}//end set_version()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_log_categories_and_classes() {
		
		$counter = 0;
		
		$classes = array(
			1	=> 'Error',
			2	=> 'Information',
			3	=> 'Create',
			4	=> 'Update',
			5	=> 'Delete',
			6	=> 'REPORT',
			7	=> 'DEBUG'
		);
		
		
		foreach($classes as $num=>$name) {
			$insertArr = array(
				'log_class_id'	=> $num,
				'name'			=> "'". $name ."'"
			);
			$sql = "INSERT INTO log_class_table ". $this->gfObj->string_from_array($insertArr, 'insert');
			
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				//good.
				$counter++;
			}
			else {
				throw new exception(__METHOD__ .": failed to create class record for (". $name .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		
		//Reset sequence, so new records can be created.
		$sql = "SELECT setval('log_class_table_log_class_id_seq', (SELECT max(log_class_id) FROM log_class_table))";
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(!strlen($dberror) && $numrows == 1) {
			$categories = array(
				1	=> 'Database',
				2	=> 'Authentication',
				3	=> 'Users',
				4	=> 'General',
				5	=> 'Project',
				6	=> 'Helpdesk',
				7	=> 'Todo',
				8	=> 'Tags',
				9	=> 'Estimates',
				10	=> 'Navigation',
				11	=> 'Preferences'
			);
			
			
			foreach($categories as $num=>$name) {
				$insertArr = array(
					'log_category_id'	=> $num,
					'name'				=> "'". $name ."'"
				);
				$sql = "INSERT INTO log_category_table ". $this->gfObj->string_from_array($insertArr, 'insert');
				
				$numrows = $this->db->exec($sql);
				$dberror = $this->db->errorMsg();
				
				if(!strlen($dberror) && $numrows == 1) {
					//good.
					$counter++;
				}
				else {
					throw new exception(__METHOD__ .": failed to create category record for (". $name .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
				}
			}
			
			//Reset sequence, so new records can be created.
			$sql = "SELECT setval('log_category_table_log_category_id_seq', (SELECT max(log_category_id) FROM log_category_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				//FINAL SANITY CHECKS!!!
				if($counter == (count($classes) + count($categories))) {
					$retval = "Successfully created all category and class records.";
				}
				else {
					$retval = "Internal error, failed to create all category and class records.";
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to reset sequence for log_category_table::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to reset sequence for log_class_table::: ". $dberror ."<BR>\nSQL::: ". $sql);
		}
		
		return($retval);
		
	}//end create_log_categories_and_classes()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_record_type_data() {
		$recordTypes = array(
			
		);
	}//end create_record_type_data()
	//=========================================================================
	
	
}//end __setupDefaultValues{}

?>