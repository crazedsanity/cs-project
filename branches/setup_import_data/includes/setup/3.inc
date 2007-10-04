<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	switch($_POST['selectedOption']) {
		case 'convertExisting': {
			store_setup_data(3, $_POST['fields'], 'convertExisting_post_info');
			$convertObj = new __convertDatabase();
			store_setup_data(3, $convertObj->go(), 'text');
			$convertObj->finish($page);
		}
		break;
		
		
		case 'newInstall': {
			$validSubmission = test_submitted_data($page, $_POST['users']);
			if($validSubmission === TRUE) {
				$obj = new __setupDefaultValues();
				store_setup_data(3, $obj->go(), 'text');
				$obj->finish($page);
			}
			else {
				store_setup_data(3, $_POST['users'], 'post_info');
				$page->set_message_wrapper(
					array(
						'title'		=> "Invalid User Data",
						'message'	=> "The user data you submitted appears to be invalid: <BR>\n". $validSubmission,
						'type'		=> "error"
					)
				);
				$page->conditional_header("/setup/3", TRUE);
			}
		}
		break;
		
		
		default:
			throw new exception("Invalid option selected!");
	};
	
	$page->conditional_header("/setup/3", TRUE);
}
else {
	$myData = get_setup_data(3, 'post_info');
	if(is_array($myData)) {
		foreach($myData as $num=>$userArr) {
			foreach($userArr as $userField => $userValue) {
				$varName = "users__". $num ."__". $userField;
				if($userField == 'is_admin') {
					$varName .= "__checked";
					$userValue = "checked";
				}
				$page->add_template_var($varName, $userValue);
			}
		}
	}
	
	//now parse the "convertExisting" stuff...
	$convertData = get_setup_data(3, 'convertExisting_post_info');
	if(is_array($convertData)) {
		foreach($convertData as $userField => $userValue) {
			$page->add_template_var($userField, $userValue);
		}
	}
}



//-----------------------------------------------------------------------------------------------------
function test_submitted_data(cs_genericPage &$page, array $users) {
	$counter = 0;
	$storeThis = array();
	$retval = "something terrible happened";
	foreach($users as $num=>$data) {
		$output = is_valid_user($page, $data);
		if(is_array($output)) {
			$counter++;
			$storeThis[$num] = $output;
		}
		else {
			$retval = "Failure on user #". $num .": ". $output;
			$counter = -1;
			break;
		}
	}
	
	if(is_numeric($counter) && $counter == count($users)) {
		store_setup_data(3, $storeThis, 'post_info');
		$retval = TRUE;
	}
	
	return($retval);
}//end test_submitted_data()
//-----------------------------------------------------------------------------------------------------



//-----------------------------------------------------------------------------------------------------
function is_valid_user(cs_genericPage &$page, array $data) {
	$requiredFields = array('username', 'password', 'confirmPassword', 'email', 'name');
	
	$good=0;
	$retval = "failure";
	foreach($requiredFields as $fieldname) {
		if(isset($data[$fieldname]) && strlen($data[$fieldname]) > 3) {
			$good++;
		}
		else {
			$retval = "Invalid length of data in ". $fieldname ." (or it's missing).";
			break;
		}
	}
	
	$goodToGo = FALSE;
	if($data['password'] == $data['confirmPassword']) {
		if(isset($data['is_admin'])) {
			if($data['is_admin'] === 't') {
				$goodToGo = TRUE;
			}
		}
		else {
			$goodToGo = TRUE;
		}
	}
	else {
		$retval = "Passwords don't match.";
	}
	
	if($good == count($requiredFields) && $goodToGo == TRUE) {
		$retval = $data;
		unset($retval['confirmPassword']);
	}
	
	$page->gfObj->debug_print(__METHOD__ .": RETURNING::: ". $retval);
	
	return($retval);
}//end is_valid_user()
//-----------------------------------------------------------------------------------------------------








class __setupDefaultValues {
	
	
	private $db;
	private $gfObj;
	private $totalRecords=0;
	
	private $data=array();
	
	private $lastNumrows=NULL;
	private $lastDberror=NULL;
	
	//=========================================================================
	public function __construct() {
		$this->db = new cs_phpDB;
		$this->gfObj = new cs_globalFunctions;
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function go() {
		store_setup_data(3, 0, 'result');
		try {
			$params = get_db_params();
			$this->db->connect($params);
			$this->db->beginTrans();
			$retval = "Connected successfully to the database.";
			
			//now that we've connected, start doing stuff.
			$retval = $this->set_version();
			$retval .= "<BR>\n". $this->create_log_categories_and_classes();
			$retval .= "<BR>\n". $this->create_record_type_data();
			$retval .= "<BR>\n". $this->create_attributes();
			$retval .= "<BR>\n". $this->create_anonymous_contact_data();
			$retval .= "<BR>\n". $this->create_status_records();
			$retval .= "<BR>\n". $this->create_tag_names();
			$retval .= "<BR>\n". $this->build_preferences();
			$retval .= "<BR>\n". $this->create_users();
			$retval .= "<BR>\n". $this->create_user_group_records();
						
			$commitRes = $this->db->commitTrans();
			if($commitRes == 1) {
				$retval .= "<BR>\n ----------- Created (". $this->totalRecords ."), result of commit: (". $commitRes .").";
				store_setup_data(3, 1, 'result');
				store_setup_data(4, 1, 'accessible');
			}
			else {
				store_setup_data(3, 0, 'result');
				throw new exception(__METHOD__ .": failed to commit the transaction (". $commitRes .")");
			}
			
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
							$this->data['version_string'] = $fullVersionString;
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
					$this->data['logcat__'. strtolower($name)] = $num;
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
					
					//reset the sequence.
					$sql = "SELECT setval('log_event_table_log_event_id_seq', (SELECT max(log_event_id) FROM log_event_table))";
					if($this->run_sql($sql) === TRUE) {
						$retval = "Successfully created all category, class, and log event records (". $counter .")";
					}
					else {
						throw new exception(__METHOD__ .": failed to reset sequence for log_event table::: ". $dberror ."<BR>\nSQL::: ". $sql);
					}
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
				$this->data['rectype__'. $insertArr['module']] = $recTypeId;
			}
			else {
				throw new exception(__METHOD__ .": failed to create record for ". $insertArr['name'] 
					.", dberror::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if(count($recordTypes) == $retval) {
			
			$sql = "SELECT setval('record_type_table_record_type_id_seq'::text, (SELECT max(record_type_id) FROM record_type_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				if($retval == count($recordTypes)) {
					$retval = "Created all record types (". $retval .")";
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
				$retval = "Successfully created all attributes (". $retval .")!";
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
	
	
	
	//=========================================================================
	/**
	 * Create the anonymous user.  This includes their contact data and the 
	 * default group.
	 * 
	 * TODO: change "short_name" into "display_name".
	 */
	private function create_anonymous_contact_data() {
		$allRecords = array(
			'disabled group'	=> array(
				'name' => 'group_table',
				'data' => array(
					'group_id'		=> 0,
					'name'			=> 'disabled',
					'short_name'	=> 'DISABLED',
					'leader_uid'	=> 0
				),
			),
			'anonymous record in user table' => array(
				'name' => 'user_table',
				'data' => array(
					'uid'			=> 0,
					'username'		=> 'Anonymous',
					'password'		=> 'disabled',		//this is PURPOSELY an invalid password (passwords should be 32-char md5's
					'is_admin'		=> 'f',
					'is_active'		=> 'f',
					'group_id'		=> 0,
					'contact_id'	=> 0
				),
			),
			'default group' => array(
				'name' => 'group_table',
				'data' => array(
					'group_id'		=> 1,
					'name'			=> 'default',
					'short_name'	=> '-DEFAULT-',
					'leader_uid'	=> 0
				),
			),
			'anonymous contact record' => array(
				'name' => 'contact_table',
				'data' => array(
					'contact_id'	=> 0,
					'fname'			=> 'Anonymous',
					'lname'			=> ''
				)
			)
		);
		
		$retval = 0;
		foreach($allRecords as $operationName => $subData) {
			$tableName = $subData['name'];
			$insertArr = $subData['data'];
			
			$sql = "INSERT INTO ". $tableName ." ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$this->totalRecords++;
				$retval++;
			} else {
				throw new exception(__METHOD__. ": failed perform operation (". $operationName .") for table (". $tableName .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if($retval == count($allRecords)) {
			//reset the sequence for the group table...
			$sql = "SELECT setval('group_table_group_id_seq'::text, (SELECT max(group_id) FROM group_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				//good to go.
				$retval = "Successfully created anonymous user, anonymous contact record, and the two default groups (". $retval .")!";
			}
			else {
				throw new exception(__METHOD__ .": failed to reset group sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error (failed sanity check)");
		}
		
		return($retval);
	}//end create_anonymous_contact_data()
	//=========================================================================
	
	
	//=========================================================================
	private function create_status_records() {
		//format: {status_id}	=> array({name}, {description})
		$statuses = array(
			0	=> array('New/Offered',			'New record'),
			1	=> array('Pending',				'Not new: pending review, or nearly complete.'),
			2	=> array('Running/Accepted',	'Work is underway'),
			3	=> array('Stalled',				'Unable to complete, or dependent on other things.'),
			4	=> array('Ended/Solved',		'Work is complete!'),
			5	=> array('Rejected',			'Denied.'),
			6	=> array('Re-opened',			'Was solved, but is once again open.'),
		);
		
		$retval = 0;
		foreach($statuses as $statusId => $subData) {
			$insertArr = array(
				'status_id'		=> $statusId,
				'name'			=> $subData[0],
				'description'	=> $subData[1]
			);
			$sql = "INSERT INTO status_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval++;
				$this->totalRecords++;
			}
			else {
				throw new exception(__METHOD__ .": failed to create status (". $insertArr['name'] .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if($retval == count($statuses)) {
			//reset the status_table.status_id sequence...
			$sql = "SELECT setval('status_table_status_id_seq'::text, (SELECT max(status_id) FROM status_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				//good to go.
				$retval = "Successfully created all status records (". $retval .")!";
			}
			else {
				throw new exception(__METHOD__ .": failed to reset status sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error (failed sanity check)");
		}
		
		return($retval);
		
	}//end create_status_records()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_tag_names() {
		$tags = array(
			1	=> 'bug',
			2	=> 'feature request',
			3	=> 'information',
			4	=> 'network related'
		);
		
		$retval = 0;
		foreach($tags as $id=>$name) {
			$insertArr = array(
				'tag_name_id'	=> $id,
				'name'			=> $name
			);
			$sql = "INSERT INTO tag_name_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval++;
				$this->totalRecords++;
			}
			else {
				throw new exception(__METHOD__ .": failed to insert data for (". $name .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if($retval == count($tags)) {
			//reset sequence for tag_name_table.tag_name_id
			$sql = "SELECT setval('tag_name_table_tag_name_id_seq'::text, (SELECT max(tag_name_id) FROM tag_name_table))";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				//good to go.
				$retval = "Successfully created all tags (". $retval .")!";
			}
			else {
				throw new exception(__METHOD__ .": failed to reset tag_name sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error (sanity check failed)");
		}
		
		return($retval);
	}//end create_tag_names()
	//=========================================================================
	
	
	
	//=========================================================================
	private function build_preferences() {
		//format: {pref_type_id} => array({name}, {default_value}, {display_name}, {description})
		$prefTypes = array(
			1	=> array('startModule',	'helpdesk',	'Starting on Module',	'Defines which section will be loaded upon login if nothing was selected.'),
			5	=> array('sorting_rts', 'public_id|DESC', 'Helpdesk Sorting', 'Define the type of sorting for the helpdesk page.'),
			6	=> array('sorting_project', 'priority|ASC', 'Project Sorting', 'Define the type of sorting for the helpdesk page.'),
			7	=> array('projectDetails_todoDisplayOnlyMine', 'all', 'Project Details: Todo display', 'Define what todos are displayed on a project\'s details page.'),
			8	=> array('projectDetails_showCompletedIssues', '1', 'Project Details: Display completed issues', 'Should completed issues display in the details of a project?')
		);
		
		$retval = 0;
		foreach($prefTypes as $prefTypeId => $subData) {
			$insertArr = array(
				'pref_type_id'	=> $prefTypeId,
				'name'			=> $subData[0],
				'default_value'	=> $subData[1],
				'display_name'	=> $subData[2],
				'description'	=> $subData[3]
			);
			
			$sql = "INSERT INTO pref_type_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval++;
				$this->totalRecords++;
			}
			else {
				throw new exception(__METHOD__ .": failed to insert data for (". $insertArr['name'] .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if($retval == count($prefTypes)) {
			//format: {pref_option_id}	=> array({pref_type_id}, {name}, {effective_value})
			$prefOptions = array(
				1	=>array(1,	'Helpdesk',							'helpdesk'),
				2	=>array(1,	'Projects',							'project'),
				3	=>array(1,	'Summary ',							'summary'),
				8	=>array(5,	'ID - Descending',					'public_id|DESC'),
				9	=>array(5,	'ID - Ascending',					'public_id|ASC'),
				10	=>array(5,	'Priority - Descending',			'priority|DESC'),
				11	=>array(5,	'Priority - Ascending',				'priority|ASC'),
				12	=>array(5,	'Submit - Descending',				'start_date|DESC'),
				13	=>array(5,	'Submit - Ascending',				'start_date|ASC'),
				14	=>array(5,	'Title - Descending',				'name|DESC'),
				15	=>array(5,	'Title - Ascending',				'name|DESC'),
				16	=>array(5,	'Status ID - Descending',			'status_id|DESC'),
				17	=>array(5,	'Status ID - Ascending',			'status_id|DESC'),
				18	=>array(5,	'Assigned - Descending',			'assigned|DESC'),
				19	=>array(5,	'Assigned - Ascending',				'assigned|DESC'),
				20	=>array(6,	'Priority - Ascending',				'priority|ASC'),
				21	=>array(6,	'Priority - Descending',			'priority|DESC'),
				22	=>array(6,	'Name of Project - Ascending',		'name|ASC'),
				23	=>array(6,	'Name of Project - Descending',		'name|DESC'),
				24	=>array(6,	'Begin - Ascending',				'start_date|ASC'),
				25	=>array(6,	'Begin - Descending',				'start_date|DESC'),
				26	=>array(6,	'End - Ascending',					'end|ASC'),
				27	=>array(6,	'End - Descending',					'end|DESC'),
				28	=>array(6,	'Status ID - Ascending',			'status_id|ASC'),
				29	=>array(6,	'Status ID - Descending',			'status_id|DESC'),
				30	=>array(6,	'Progress - Ascending',				'progress|ASC'),
				31	=>array(6,	'Progress - Descending',			'progress|DESC'),
				32	=>array(6,	'Leader - Ascending',				'leader_contact_id|ASC'),
				33	=>array(6,	'Leader - Descending',				'leader_contact_id|DESC'),
				34	=>array(7,	'Show everything',					'all'),
				35	=>array(7,	'All of mine (created & assigned)',	'mine'),
				36	=>array(7,	'Only my assigned items',			'assigned'),
				37	=>array(8,	'Yes',								'1'),
				38	=>array(8,	'No',								'0'),
			);
			
			foreach($prefOptions as $prefOptionId => $subData) {
				$insertArr = array(
					'pref_option_id'	=> $prefOptionId,
					'pref_type_id'		=> $subData[0],
					'name'				=> $subData[1],
					'effective_value'	=> $subData[2]
				);
				$sql = "INSERT INTO pref_option_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
				$numrows = $this->db->exec($sql);
				$dberror = $this->db->errorMsg();
				
				if(!strlen($dberror) && $numrows == 1) {
					$retval++;
					$this->totalRecords++;
				}
				else {
					throw new exception(__METHOD__ .": failed to insert data for pref option (". $insertArr['name'] .")::: ". $dberror ."<BR>\nSQL::: ". $sql);
				}
			}
			
			if($retval == (count($prefTypes) + count($prefOptions))) {
				//reset the pref_type_table.pref_type_id sequence.
				$sql = "SELECT setval('pref_type_table_pref_type_id_seq'::text, (SELECT max(pref_type_id) FROM pref_type_table))";
				$numrows = $this->db->exec($sql);
				$dberror = $this->db->errorMsg();
				
				if(!strlen($dberror) && $numrows == 1) {
					if($retval == (count($prefTypes) + count($prefOptions))) {
						//reset the pref_option_table.pref_option_id sequence.
						$sql = "SELECT setval('pref_option_table_pref_option_id_seq'::text, (SELECT max(pref_option_id) FROM pref_option_table))";
						$numrows = $this->db->exec($sql);
						$dberror = $this->db->errorMsg();
						
						if(!strlen($dberror) && $numrows == 1) {
							$retval = "Successfully created all preferences and options (". $retval .")!";
						}
						else {
							throw new exception(__METHOD__ .": failed to reset the pref_option sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
						}
					}
					else {
						throw new exception(__METHOD__ .": internal error (sanity check #2 failed)");
					}
				}
				else {
					throw new exception(__METHOD__ .": failed to reset pref_type sequence::: ". $dberror ."<BR>\nSQL::: ". $sql);
				}
			}
			else {
				throw new exception(__METHOD__ .": internal error (sanity check #2 failed): (". $retval ." != ". (count($prefTypes) + count($prefOptions)) .")");
			}
		}
		else {
			throw new exception(__METHOD__ .": internal error (sanity check #1 failed)");
		}
		
		return($retval);
		
	}//end build_preferences()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_users() {
		
		//retrieve the user information.
		$userData = get_setup_data(3, 'post_info');
		if(!is_array($userData) || count($userData) != 2) {
			throw new exception(__METHOD__ .": no user data...?". $this->gfObj->debug_print($userData,0));
		}
		
		$counter = 0;
		$retval = "Successfully created records for::: ";
		foreach($userData as $num => $subData) {
			
			//split their name up, based upon spaces.
			$nameData = explode(' ', $subData['name']);
			$insertArr = array();
			if(count($nameData) == 1) {
				$insertArr['fname'] = $nameData[0];
				$insertArr['lname'] = "";
			}
			elseif(count($nameData) == 2) {
				$insertArr['fname'] = $nameData[0];
				$insertArr['lname'] = $nameData[1];
			}
			else {
				$insertArr['fname'] = $nameData[0];
				$insertArr['lname'] = preg_replace('/^'. $insertArr['fname'] .' /', '', $subData['name']);
			}
			
			//create their contact record.
			$sql = "INSERT INTO contact_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$sql = "SELECT currval('contact_table_contact_id_seq'::text)";
				if($this->run_sql($sql, 1) === TRUE) {
					$this->totalRecords++;
					
					$data = $this->db->farray();
					$contactId = $data[0];
					
					//now create the user.
					$xUser = $subData['username'];
					$insertArr = array(
						'username'		=> $subData['username'],
						'password'		=> md5($subData['password'] .'_'. $contactId),
						'is_admin'		=> interpret_bool($subData['is_admin'], array('f', 't')),
						'contact_id'	=> $contactId
					);
					$sql = "INSERT INTO user_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
					
					if($this->run_sql($sql, 1) === TRUE) {
						$sql = "SELECT currval('user_table_uid_seq'::text)";
						if($this->run_sql($sql,1) === TRUE) {
							$data = $this->db->farray();
							$lastUid = $data[0];
							
							//create an email attribute for them.
							$sql = "SELECT contact_update_attribute(". $contactId .", 'email', '". $subData['email'] ."')";
							if($this->run_sql($sql,1) === TRUE) {
								$counter++;
								$retval = $this->gfObj->create_list($retval, " --- Created record for ". $subData['username'] ." (". $lastUid .")", "<BR>\n");
							}
							else {
								throw new exception(__METHOD__ .": failed to create email for ". $subData['username'] ."::: ". $dberror ."<BR>\nSQL::: ". $sql);
							}
						}
						else {
							throw new exception(__METHOD__ .": failed to retrieve uid for ". $subData['username'] ."::: ". $dberror ."<BR>\nSQL::: ". $sql);
						}
					}
					else {
						throw new exception(__METHOD__ .": failed to create user record for ". $subData['username'] ."::: ". $dberror ."<BR>\nSQL::: ". $sql);
					}
				}
				else {
					throw new exception(__METHOD__ .": unable to retrieve contact_id for ". $subData['username'] ."::: ". $dberror ."<BR>\nSQL::: ". $sql);
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to create contact record for ". $subData['username'] ."::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		
		if($counter == 2) {
			$retval = $this->gfObj->create_list($retval, " --->>> done, created  (". $counter .") records", "<BR>\n");
		}
		else {
			throw new exception(__METHOD__ .": failed to create users (". $counter .")::: ". $this->gfObj->debug_print($userData,0));
		}
		
		return($retval);
	}//end create_users()
	//=========================================================================
	
	
	
	//=========================================================================
	public function finish(cs_genericPage &$page) {
		
		$stepRes = get_setup_data(3, 'result');
		if($stepRes == 1) {
			$page->set_message_wrapper(array(
				'title'		=> "Successfully Setup Data",
				'message'	=> "All default data was stored in the new database successfully!",
				'type'		=> "status"
			));
			store_setup_data(3, $this->data, 'data');
			$page->conditional_header("/setup/4", TRUE);
		}
		else {
			$page->set_message_wrapper(array(
				'title'		=> "Step Failed",
				'message'	=> "Please review the errors below and proceed accordingly.",
				'type'		=> "error"
			));
			$page->conditional_header("/setup/3", TRUE);
		}
		
	}//end finish()
	//=========================================================================
	
	
	
	//=========================================================================
	private function run_sql($sql, $expectedNumrows=NULL) {
		if(strlen($sql)) {
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			$this->lastNumrows = $numrows;
			$this->lastDberror = $dberror;
			
			if(!strlen($dberror) && $numrows >= 0) {
				if(is_numeric($expectedNumrows)) {
					if($expectedNumrows == $numrows) {
						$retval = TRUE;
					}
					else {
						$retval = FALSE;
					}
				}
				else {
					//don't care if it's numeric.
					$retval = TRUE;
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to run SQL, got numrows=(". $numrows ."), dberror::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			throw new exception(__METHOD__ .": no SQL to run...");
		}
		
		return($retval);
	}//end run_sql()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_user_group_records() {
		$sql = "INSERT INTO user_group_table (uid, group_id) SELECT uid, group_id FROM user_table";
		if($this->run_sql($sql, 3) === TRUE) {
			$retval = "Successfully created user_group linkage.";
		}
		else {
			throw new exception(__METHOD__ .": unable to create user group records::: ". $this->lastDberror ."<BR>\nSQL::: ". $sql);
		}
		return($retval);
	}//end create_user_group_records()
	//=========================================================================
	
	
}//end __setupDefaultValues{}



class __convertDatabase {
	
	
	
	private $db;
	private $gfObj;
	
	private $lastNumrows=NULL;
	private $lastDberror=NULL;
	
	//=========================================================================
	public function __construct() {
		$this->db = new cs_phpDB;
		
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt=1;
	}//end __construct()
	//=========================================================================
	
	
	//=========================================================================
	public function go() {
		//determine what script we have to include & run.
		$this->gfObj->debug_print(__METHOD__ .": starting...");
		
		//retrieve the connection parameters.
		$storedParams = get_setup_data(3, 'convertExisting_post_info');
		
		$params = array();
		foreach($storedParams as $index=>$value) {
			$key = preg_replace('/^database__/', '', $index);
			$params[$key] = $value;
		}
		$this->gfObj->debug_print($params);
		
		$this->db->connect($params);
		if(!$this->db->is_connected()) {
			throw new exception(__METHOD__ .": failed to connect to database...");
		}
		else {
			$this->gfObj->debug_print(__METHOD__ .": connected.  Determining version...");
			
			$numrows = $this->db->exec("SELECT internal_data_get_value('version_string')");
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$data = $this->db->farray();
				$version = $data[0];
				$this->gfObj->debug_print(__METHOD__ .": version is (". $version .")");
				
				$this->run_conversion($version);
				$configData = $this->get_config_values();
			}
			else {
				throw new exception(__METHOD__ .": failed to get version... ". $dberror);
			}
		}
		
	}//end go()
	//=========================================================================
	
	
	
	//=========================================================================
	private function run_conversion($fromVersion) {
		
		//check if the script exists.
		$convertScript = dirname(__FILE__) .'/../../upgrade/convert/'. $fromVersion .'.php';
		if(file_exists($convertScript)) {
			require_once($convertScript);
			try {
				//create the object...
				$newDb = new cs_phpDb;;
				$newDb->connect(get_db_params());
				$obj = new convertDatabase($this->db, $newDb);
				if(method_exists($obj, 'go')) {
					$result = $obj->go();
				}
				else {
					throw new exception(__METHOD__ .": convertDatabase::go() does not exist");
				}
			}
			catch(exception $e) {
				$this->gfObj->debug_print(__METHOD__ .": FATAL EXCEPTION OCCURRED::: ". $e->getMessage());
			}
		}
		else {
			throw new exception(__METHOD__ .": conversion script does not exist: ". $convertScript);
		}
	}//end run_conversion()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_config_values() {
		
		store_setup_data(3, 0, 'result');
		$configData = array();
		//setup the list of required items.
		$requiredItems = array(
			'log_category_table' => array(
				'prefix'	=> 'logcat',
				'idColumn'	=> 'log_category_id',
				'data'		=> array(
					'database', 'authentication', 'users', 'general', 'project', 'helpdesk', 'todo', 
					'tags', 'estimates', 'navigation', 'preferences' 
				),
			),
			'record_type_table' => array(
				'prefix'	=> 'rectype',
				'idColumn'	=> 'record_type_id',
				'data'		=> array(
					'project', 'todo', 'helpdesk'
				)
			)
		);
		
		foreach($requiredItems as $tableName => $info) {
			//check that all the required values are present.}
			foreach($info['data'] as $itemName) {
				$configIndex = $info['prefix'] .'__'. $itemName;
				
				$getItemSql = "SELECT * FROM ". $tableName ." WHERE lower(name) = '". $itemName ."'";
				$numrows = $this->db->exec($getItemSql);
				$dberror = $this->db->errorMsg();
				
				if(!strlen($dberror)) {
					if($numrows == 1) {
						//retrieve the id & store it.
						$data = $this->db->farray_fieldnames();
						$configData[$configIndex] = $data[$info['idColumn']];
					}
					else {
						throw new exception(__METHOD__ .": invalid data...? numrows=(". $numrows .")");
					}
				}
				else {
					throw new exception(__METHOD__ .": failed to retrieve id for (". $itemName ."), rows=(". $numrows .") ERROR::: ". $dberror ."<BR>\nSQL::: ". $getItemSql);
				}
			}
		}
		
		$this->gfObj->debug_print(__METHOD__ .": config data::: ". $this->gfObj->debug_print($configData,0));
		
		//set this step's results.
		store_setup_data(3, 1, 'result');
		
		return($configData);
	}//end get_config_values()
	//=========================================================================
	
	
	
	//=========================================================================
	public function finish(cs_genericPage &$page) {
		
		store_setup_data(4, 1, 'accessible');
		$stepRes = get_setup_data(3, 'result');
		if($stepRes == 1) {
			$page->set_message_wrapper(array(
				'title'		=> "Successfully Setup Data",
				'message'	=> "All default data was stored in the new database successfully!",
				'type'		=> "status"
			));
			store_setup_data(3, $this->configData, 'data');
			$page->conditional_header("/setup/4", TRUE);
		}
		else {
			$page->set_message_wrapper(array(
				'title'		=> "Step Failed",
				'message'	=> "Please review the errors below and proceed accordingly.",
				'type'		=> "error"
			));
			$page->conditional_header("/setup/3", TRUE);
		}
		
	}//end finish()
	//=========================================================================
	
}//end __convertDatabase{}

?>
