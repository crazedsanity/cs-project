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
	private $totalRecords=0;
	
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
			$retval .= "<BR>\n". $this->create_log_categories_and_classes();
			$retval .= "<BR>\n". $this->create_attributes();
			
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
				$this->totalRecords++;
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
				'name'			=> $name
			);
			$sql = "INSERT INTO log_class_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				//good.
				$counter++;
				$this->totalRecords++;
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
					'name'				=> $name
				);
				$sql = "INSERT INTO log_category_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
				
				$numrows = $this->db->exec($sql);
				$dberror = $this->db->errorMsg();
				
				if(!strlen($dberror) && $numrows == 1) {
					//good.
					$counter++;
					$this->totalRecords++;
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
				
				//format (primary index is log_event_id): log_class_id, log_category_id, description
				$logEvents = array(
				// *  log_event_id | log_class_id | log_category_id |              description
					1	=> array(3,	5,	'Project: created record'),
					2	=> array(5,	5,	'Project: deleted record'),
					3	=> array(4,	5,	'Project: updated record'),
					4	=> array(1,	5,	'Project: ERROR'),
					5	=> array(3,	6,	'Helpdesk: Created record'),
					6	=> array(4,	6,	'Helpdesk: Updated record'),
					7	=> array(2,	6,	'Helpdesk: Information'),
					8	=> array(1,	6,	'Helpdesk: ERROR'),
					9	=> array(6,	6,	'Helpdesk: Report'),
					10	=> array(3,	7,	'Todo: created record'),
					11	=> array(5,	7,	'Todo: deleted record'),
					12	=> array(4,	7,	'Todo: updated record'),
					13	=> array(1,	1,	'Database Error'),
					14	=> array(6,	5,	'Project: Activity Report'),
					15	=> array(6,	7,	'Todo: Activity Report'),
					16	=> array(3,	2,	'User logged-in'),
					17	=> array(5,	2,	'User logged-out'),
					18	=> array(6,	2,	'Login/Logout Report'),
					19	=> array(3,	8,	'Tags: created record'),
					20	=> array(5,	8,	'Tags: deleted record'),
					21	=> array(4,	8,	'Tags: updated record'),
					22	=> array(6,	8,	'Tags: Activity Report'),
					23	=> array(1,	2,	'Authentication: ERROR'),
					24	=> array(2,	10,	'Navigation: Viewed page'),
					25	=> array(4,	9,	'Update: Estimates'),
					26	=> array(1,	9,	'Error: Estimates'),
					27	=> array(2,	5,	'Information: Project'),
					28	=> array(4,	3,	'Update: Users'),
					29	=> array(1,	7,	'Error: Todo'),
					30	=> array(3,	3,	'Create: Users')
				);
				
				foreach($logEvents as $logEventId => $subArr) {
				 	$insertArr = array(
						'log_event_id'		=> $logEventId,
						'log_class_id'		=> $subArr[0],
						'log_category_id'	=> $subArr[1],
						'description'		=> $subArr[2]
					);
					$sql = "INSERT INTO log_event_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
					$numrows = $this->db->exec($sql);
					$dberror = $this->db->errorMsg();
					
					if(!strlen($dberror) && $numrows == 1) {
						$counter++;
						$this->totalRecords++;
					}
					else {
						throw new exception(__METHOD__ .": failed to create log_event_id #". $insertArr['log_event_id'] 
						.", description: ". $insertArr['description'] ."<BR>\nERROR::: ". $dberror ."<BR>\nSQL::: ". $sql);
					}
				}
				
				//FINAL SANITY CHECKS!!!
				if($counter == (count($classes) + count($categories) + count($logEvents))) {
					$retval = "Successfully created all category, class, and log event records";
				}
				else {
					throw new exception(__METHOD__ .": Internal error, failed to create all category and class records");
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
		//format:
		//	{record_type_id} => array({name}, {module})
		$recordTypes = array(
			1	=> array('Project',		'project'),
			2	=> array('Todo',		'todo'),
			3	=> array('Helpdesk',	'helpdesk')
		);
		
		$retval = 0;
		foreach($recordTypes as $recTypeId => $subData) {
			$insertArr = array(
				'record_type_id'	=> $recTypeId,
				'name'				=> $subData[0],
				'module'			=> $subData[1]
			);
			$sql = "INSERT INTO record_type_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$this->totalRecords++;
				$retval++;
			}
			else {
				throw new exception(__METHOD__ .": failed to create record for ". $insertArr['name'] 
					.", dberror::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if(count($recordTypes) == $retval) {
			
			$sql = "SELECT setval('record_type_table_record_type_id_seq'::text, (SELECT max(record_type_id) FROM record_type_table)))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				if($retval == count($recordTypes)) {
					$retval = "Created all record types";
				}
				else {
					throw new exception(__METHOD__ .": internal error (sanity check failed)");
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to reset sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error");
		}
		
		return($retval);
	}//end create_record_type_data()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_attributes() {
		$attributes = array(
			1	=> array('company',		'sql'),
			2	=> array('email',		'email'),
			3	=> array('phone',		'phone'),
			4	=> array('fax',			'phone'),
			5	=> array('cell',		'phone'),
			6	=> array('im_yahoo',	'alphanumeric'),
			7	=> array('im_skype',	'alphanumeric'),
			8	=> array('im_aol',		'alphanumeric'),
			9	=> array('im_msn',		'alphanumeric'),
			10	=> array('im_icq',		'alphanumeric'),
			11	=> array('address',		'sql'),
			12	=> array('city',		'alphanumeric'),
			13	=> array('state',		'alphanumeric'),
			14	=> array('zip',			'alphanumeric')
		);
		
		$retval = 0;
		foreach($attributes as $attributeId => $subData) {
			$insertArr = array(
				'attribute_id'	=> $attributeId,
				'name'			=> $subData[0],
				'clean_as'		=> $subData[1]
			);
			$sql = "INSERT INTO attribute_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval++;
				$this->totalRecords++;
			}
			else {
				throw new exception(__METHOD__ .": failed to insert data for attribute (". $insertArr['name'] .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if($retval == count($attributes)) {
			$sql = "SELECT setval('attribute_table_attribute_id_seq'::text, (SELECT max(attribute_id) FROM attribute_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval = "Successfully created all attributes!";
			}
			else {
				throw new exception(__METHOD__ .": failed to reset sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error (sanity check failed)");
		}
		
		return($retval);
	}//end create_attributes()
	//=========================================================================
	
	
}//end __setupDefaultValues{}

?>