<?
/*
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */
require_once("site_config.php");

class projectClass extends mainRecord {
	
	var $db;				//database handle.
	var $page;				//our passed-by-reference copy of the GenericPage object.
	var $projectId	= NULL;	//project ID we're on right now, if any.
	var $parent		= NULL;	//id of the parent project...
	var $order = array();	//array of how to order the projects that we get back.
	public $tagObj;
	public $prefObj;
	private $recordTypeId;
	private $logCategoryId;
	public $logsObj;
	protected $groupId = NULL;
	
	private $dbTable = 'project_table';
	
	//================================================================================================
	/**
	 * CONSTRUCTOR.
	 */
	function __construct(cs_phpDB &$db) {
		
		if(is_numeric(LOGCAT__PROJECT)) {
			$this->logCategoryId = LOGCAT__PROJECT;
		}
		else {
			throw new exception(__METHOD__ .": no valid log_category_id defined for project: did you complete setup?");
		}
		
		if(is_numeric(RECTYPE__PROJECT)) {
			$this->recordTypeId = RECTYPE__PROJECT;
		}
		else {
			throw new exception(__METHOD__ .": no valid record_type_id defined for project: did you complete setup?");
		}
		
		
		//check to see if the database object is valid.
		if(is_object($db) && $db->is_connected()) {
			$this->db = $db;
		}
		else {
			exit("no database!!!");
		}
		
		//set the internal group_id.
		$this->set_group_id();
		
		//now create all those internal objects.
		$this->noteObj = new noteClass($this->db);
		$this->todoObj = new todoClass($this->db);
		$this->helpdeskObj  = new helpdeskClass($this->db);
		$this->tagObj  = new tagClass($this->db);
		$this->logsObj = new logsClass($this->db, $this->logCategoryId);
		$this->prefObj = new pref($this->db, $_SESSION['uid']);
		
		parent::__construct();
	}//end __construct()
	//================================================================================================
	
	
	
	//================================================================================================
	public function set_group_id($newId=NULL) {
		$retval = NULL;
		if((is_null($newId) || !is_numeric($newId)) && is_numeric($_SESSION['group_id'])) {
			$newId = $_SESSION['group_id'];
			$this->groupId = $newId;
			$retval = $newId;
		}
		elseif(is_numeric($newId)) {
			$this->groupId = $newId;
			$retval = $newId;
		}
		else {
			if(is_numeric($_SESSION['uid'])) {
				$userObj = new userClass($this->db, $_SESSION['uid']);
				$info = $userObj->get_user_info($_SESSION['uid']);
				$_SESSION['group_id'] = $info['group_id'];
				$this->groupId = $info['group_id'];
			}
			else {
				throw new exception(__METHOD__ .": unable to determine group_id... session probably invalid... newId=(". $newId ."), sessionGroupId=(". $_SESSION['group_id'] .")");
			}
		}
		
		return($retval);
		
	}//end set_group_id()
	//================================================================================================
	
	
	
	//================================================================================================
	function list_projects($parent=0, $primaryOrder=NULL, $filterArr=NULL) {
		
		$filterArr['is_helpdesk_issue'] = 'f';
		$filterArr['parent'] = $parent;
		$retval = $this->get_records($filterArr, $primaryOrder);
		
		if(is_array($retval) && count($retval)) {
			foreach($retval as $index=>$array) {
				//format the start date so it's just a date.
				$tmp = explode(' ', $array['start_date']);
				$retval[$index]['start_date'] = $tmp[0];
				
				//now format the last_updated field so it doesn't have microseconds.
				$tmp = explode('\.', $array['last_updated']);
				$retval[$index]['last_updated'] = $tmp[0];
			}
		}
		
		return($retval);
		
	}//end list_projects()
	//================================================================================================
	
	
	//================================================================================================
	function set_category_text()
	{
		print "<pre>";
		$this->logsObj->log_by_class("called set_category_text()!");
		throw new exception("called set_category_text()");
	}//end set_category_text()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Returns all details about a given project ID.
	 * 
	 * @param $projectId	(int) ID to lookup information for.
	 * @param $getRelated 	(bool) whether the related notes/todos/issues 
	 * 							should be retrieved or not.
	 * 
	 * @return <array>		PASS: contains all data relevant to given id.
	 * @return 0			FAIL: unable to retrieve data.
	 */
	function get_details($projectId, $getRelated = TRUE) {
		//build the query to get the data...
		if($projectId) {
			$this->projectId = $projectId;
		}
			
		//set a filter.
		$critArr = array(
			'public_id'			=>$projectId,
			'is_helpdesk_issue'	=> 'f',
			'status_id'			=> 'all'
		);
		$retval = parent::get_records($critArr);
		if($retval == 0) {
			//it's an internal lookup error, or something.
			$retval = 0;
		}
		elseif(!is_array($retval) || !is_array($retval[$projectId])) {
			//hrmm... need to log something here.
			$retval = 0;
		}
		else {
			$retval = $retval[$projectId];
			
			$retval['linked_users'] = $this->get_project_user_associations($this->projectId);
			
			//set the internal var to our current parent...
			$this->parent = $retval['parent'];
			
			$this->internalRecordId = $retval['record_id'];
			if($getRelated) {
				$retval['related'] = array(
					"todo"	=> $this->get_todos(),
					"note"	=> $this->get_notes(),
					"issue"	=> $this->get_issues()
				);
			}
			else {
				$retval['related'] = array();
			}
		}
		
		return($retval);
	}//end get_details()
	//================================================================================================
	
	
	//================================================================================================
	function list_users($useUid=FALSE) {
		if(isset($this->groupId) && is_numeric($this->groupId)) {
			$idField = 'contact_id';
			if($useUid) {
				$idField = 'uid';
			}
			$query = "SELECT u.". $idField .", u.username FROM user_group_table AS ug INNER JOIN user_table AS u " .
					"USING (uid) WHERE ug.group_id=". $this->groupId ." ORDER BY u.username";
			$this->db->exec($query);
			
			$numrows = $this->db->numRows();
			$dberror = $this->db->errorMsg();
			
			if($dberror || $numrows < 1) {
				if($numrows == 0) {
					$details = __METHOD__ .": No rows returned... ". $query;
				}
				else {
					$details = __METHOD__ .": $dberror || $query";
				}
				$this->logsObj->log_dberror($details);
				$retval = 0;
			}
			else {
				$retval = $this->db->farray_nvp($idField, 'username');
			}
		}
		else {
			//no session: could be running from the command line.  :) 
			$retval = NULL;
		}
		
		return($retval);
	}//end list_users()
	//================================================================================================
	
	
	
	//================================================================================================
	function create_user_option_list($selectThis=NULL,$displayColumn="username", $valueColumn="uid", $addBlankOption=TRUE) {
		$myList = $this->list_users();
		
		if(is_null($displayColumn)) {
			$displayColumn = "username";
		}
		if(is_null($valueColumn)) {
			$valueColumn = "uid";
		}
		
		//now create the actual option list.
		if(!is_array($myList)) {
			$optionList = 0;
			$this->logsObj->log_dberror(__METHOD__ .": no array passed... ". debug_print(func_get_args(), 0));
		}
		else {
			$baseRow = "\n\t\t" . '<option value="{contact_id}"{selected}>{username}</option>' . "\n";
			foreach($myList as $contactId=>$username) {
				$selected = "";
				if(is_array($selectThis) && isset($selectThis[$contactId])) {
					$selected = " selected";
				}
				elseif(is_numeric($selectThis) && $contactId == $selectThis) {
					$selected = " selected";
				}
				$repArr = array(
					'contact_id'	=> $contactId,
					'username'		=> $username,
					'selected'		=> $selected
				);
				$optionList .= mini_parser($baseRow, $repArr);
			}
			if($addBlankOption) {
				$blankValue = "---- N/A ----";
				if(is_string($addBlankOption)) {
					$blankValue = $addBlankOption;
				}
				$optionList = "\n\t\t<option value=\"\">$blankValue</option>" . $optionList;
			}
		}
		
		return($optionList);
	}//end create_user_option_list()
	//================================================================================================
	
	
	
	//================================================================================================
	function get_todos() {
		$retval = 0;
		
		//attempt to get a list of the todos...
		$this->todoObj->projectId = $this->projectId;
		
		$todoDisplayPref = $this->prefObj->get_pref_value_by_name('projectDetails_todoDisplayOnlyMine');
		
		$critArr = array("record_id"=>$this->internalRecordId);
		$contactCrit = NULL;
		if($todoDisplayPref != 'all') {
			if($todoDisplayPref == 'mine') {
				$contactCrit = array(
					't.creator_contact_id'	=> $_SESSION['contact_id'],
					't.assigned_contact_id'	=> $_SESSION['contact_id']
				);
			}
			elseif($todoDisplayPref == 'assigned') {
				$contactCrit = array(
					't.assigned_contact_id'	=> $_SESSION['contact_id']
				);
			}
		}
		$retval = $this->todoObj->get_todos($critArr, NULL, $contactCrit);
			
		return($retval);
		
	}//end get_todos()
	//================================================================================================
	
	
	
	//================================================================================================
	function get_notes() {
		$this->noteObj->projectId = $this->projectId;
		$retval = $this->noteObj->get_notes(array('record_id'=>$this->internalRecordId));
		
		$userList = $this->list_users();
		return($retval);
	}//end get_notes()
	//================================================================================================
	
	
	//================================================================================================
	/**
	 * Creates an option list, excluding the current project.
	 * TODO: make better headers!!!
	 */
	function create_project_option_list($currentProject=NULL, $newProject=FALSE, $mustSetProj=FALSE) {
		cs_debug_backtrace();
		throw new exception("create_project_option_list() called!");
	}//end create_project_option_list()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Takes an array of updates & uses it to modify the current project's data.
	 * 
	 * @param $updatesArr	<array>
	 * 
	 * @return 0			FAIL: unable to update data.
	 * @return 1			PASS: Updated successfully. 
	 */
	function update_project($updatesArr) {
		$oldProjectDetails = $this->get_details($this->projectId, FALSE);
		$criteria = array(
			'public_id'			=> $this->projectId, 
			'is_helpdesk_issue'	=>'f',
			'status_id'			=> 'all'
		);
		$updateResult = parent::update_record($criteria, $updatesArr);

		if($updateResult != 1) {
			$this->logsObj->log_dberror("update_project(): failed to update... numrows=($numrows), dberror::: $dberror");
			$retval = 0;
		}
		else {
			$retval = $updateResult;
			
			//send off the list of users for assignment.
			$assignUsersRes = $this->assign_users_to_project($this->projectId, $updatesArr['linked_users']);
			
			//log each item that was changed.
			foreach($updatesArr as $field=>$value) {
				//
				$noLogThese = array('personen');
				if(($oldProjectDetails[$field] != $updatesArr[$field]) && (!in_array($field, $noLogThese))) {
					//log the changes.
					$details = "Changed settings for $field:::  OLD=(". $oldProjectDetails[$field] .") to " .
							"NEW=(". $updatesArr[$field] .")";
					$this->logsObj->log_by_class($details, 'information', NULL, $this->recordTypeId, $this->projectId);
				}
			}
			
			//TODO: better logging for more than just "ENDED" projects.
			$useThisName = $oldProjectDetails['name'];
			if(isset($updatesArr['name'])) {
				$useThisName = $updatesArr['name'];
			}
			if($updatesArr['status_id'] == 4) {
				//they've ENDED the project: log it as such.
				$details = "Ended project #". $this->projectId .": ". $useThisName;
				$this->logsObj->log_by_class($details, 'report', NULL, $this->recordTypeId, $this->projectId);
			}
			elseif(($oldProjectDetails['status_id'] == 4) && (isset($updatesArr['status_id']))) {
				//it's been re-opened.
				$details = "Project re-opened (new status_id=". $updatesArr['status_id'] ."): #". $this->projectId
					.": ". $useThisName;
				$this->logsObj->log_by_class($details, 'report', NULL, $this->recordTypeId, $this->projectId);
			}
		}
		
		return($retval);
	}//end update_project()
	//================================================================================================
	
	
	
	//================================================================================================
	function create_project($dataArr) {
		//call parent class's method to create the record.
		$newRecord = parent::create_record($dataArr, FALSE);
		
		//capture the list of users assigned to the project, for later assignment.
		$myLinkedUsers = NULL;
		if(isset($dataArr['linked_users'])) {
			$myLinkedUsers = $dataArr['linked_users'];
			unset($dataArr['linked_users']);
		}
		
		//set the ancestry string.
		$myParent = $dataArr['parent'];
		$ancestryString = "currval('project_table_project_id_seq'::text)";
		$dataArr['ancestry_level'] = 1;
		if(!is_null($myParent) && is_numeric($myParent)) {
			$parentAncestry = $this->get_ancestry($myParent);
			$ancestryString = "'". $parentAncestry . ":' || " . $ancestryString;
			$dataArr['ancestry_level'] = count(explode(':', $parentAncestry)) +1;
		}
		else {
			//TODO: make this clean::: fix how cleanString() and string_from_array() work together...
			$ancestryString = "'' || ". $ancestryString; 
		}
		$dataArr['ancestry'] = $ancestryString;
		
		//check for errors, & tell 'em what happened.
		if(!is_numeric($newRecord) || $newRecord < 1) {
			//something bad happened.
			$this->logsObj->log_dberror("create_project(): failed to insert data ($numrows)... $dberror");
			$retval = 0;
		}
		else {
			
			//TODO: deal with ancestry (associated parent record) here.
			if(is_numeric($dataArr['parentRecordId']) && $dataArr['parentRecordId'] > 0) {
				$updateRes = parent::update_record(array('record_id'=>$newRecord), array('parentRecordId' => $dataArr['parentRecordId']));
			}
			
			//retrieve the record, so we can get the public_id.
			//TODO: add error-checking, or something (same for helpdeskClass::create_record()).
			$myNewRecordArr = parent::get_records(array('record_id' => $newRecord), NULL, FALSE);
			$tempKeysArray = array_keys($myNewRecordArr);
			$retval = $tempKeysArray[0];
			
			if(!is_numeric($retval) || $retval < 1) {
				//something bad happened...
				$retval = -1;
			}
			else {
				//now assign the users.
				if(!is_null($myLinkedUsers)) {
					$this->assign_users_to_project($retval, $myLinkedUsers);
				}
			}
			
			//okay, log the creation.
			$details = "Created project #". $retval .": ". $dataArr['name'];
			$this->logsObj->log_by_class($details, 'create', NULL, $this->recordTypeId, $retval);
			$this->logsObj->log_by_class($details, 'report', NULL, $this->recordTypeId, $retval);
		}
		
		return($retval);
	}//end create_project()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Attempt to retrieve issues associated with the current project.
	 */
	function get_issues() {
		//set a default return value.
		$retval = 0;
		
		//retrieve issues.
		$myOrderArr = array("priority"=>"ASC");
		
		$extraCrit = array();
		$myIssuePref = $this->prefObj->get_pref_value_by_name('projectDetails_showCompletedIssues');
		if($myIssuePref == 0) {
			$extraCrit = array(
				'status_id' => $GLOBALS['STATUS_NOTENDED']
			);
		}
		
		$myIssues = $this->get_child_records($this->projectId, TRUE, $extraCrit);
		
		if(is_array($myIssues) && count($myIssues) > 0) {
			$retval = $myIssues;
		}
		
		return($retval);
	}//end get_issues()
	//================================================================================================
	
	
	
	//================================================================================================
	function get_ancestry_link_list($projectId, $formatIt=TRUE, $lastItemIsLink=FALSE, $showSingleAncestry=FALSE) {
		$retval = NULL;
		if(is_numeric($projectId) && $projectId > 0) {
			//get the list of ancestors.
			$myAncestors = $this->get_ancestry($projectId);
			$ancestorList = explode(':', $myAncestors);
			
			$retval = NULL;
			if(count($ancestorList) > 1) {
				$projects = parent::get_records(array('record_id' => $ancestorList, 'status_id' => 'all'));
				
				//if we've got a proper array, loop through it.
				if(is_array($projects) && count($projects) > 0) {
					//GO FOR IT
					//NOTE: *must* loop through the ancestorList, as $projects has them ordered (for lineage).
					$finalData = array();
					$useThis = array_flip($ancestorList);
					foreach($projects as $publicId=>$data) {
						$internalId = $data['record_id'];
						$ancestorNum = $useThis[$internalId];
						$finalData[$ancestorNum] = $projects[$publicId];
					}
					ksort($finalData);
					foreach($finalData as $crap => $data) {
						$id = $data['public_id'];
						$name = $data['name'];
						//concatenation.  Woot.
						$name = cleanString($name, "htmlspecial_nq");
						$name = cleanString($name, "htmlentity_plus_brackets");
						if($formatIt === TRUE) {
							if($id == $projectId && $lastItemIsLink === FALSE) {
								$string = '<b>'. $name .'</b>';
							}
							else {
								$string = '<a href="/content/project/view/?ID=' . $id . '">' . $name . '</a>';
							}
							$retval = create_list($retval, $string, " / ");
						}
						else {
							$retval = create_list($retval, $name, " / ");
						}
					}
				}
			}
			elseif($showSingleAncestry === TRUE && is_array($ancestorList) && count($ancestorList) == 1) {
				$allData = array_values($this->get_records(array('record_id' => $ancestorList[0])));
				$data = $allData[0];
				$id = $data['public_id'];
				$name = $data['name'];
				//concatenation.  Woot.
				$name = cleanString($name, "htmlspecial_nq");
				$name = cleanString($name, "htmlentity_plus_brackets");
				if($formatIt === TRUE) {
					if($id == $projectId && $lastItemIsLink === FALSE) {
						$string = '<b>'. $name .'</b>';
					}
					else {
						$string = '<a href="/content/project/view/?ID=' . $id . '">' . $name . '</a>';
					}
					$retval = create_list($retval, $string, " / ");
				}
				else {
					$retval = create_list($retval, $name, " / ");
				}
			}
		}
		
		return($retval);
	}//end get_ancestry_link_list()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Get the array of children projects (array of id=>name)
	 */
	function get_children($projectId, $filter=NULL)
	{
		//get the project's lineage.
		$myAncestry = $this->get_details($projectId);
		
		$retval = NULL;
		if(is_array($myAncestry)) {
			//get the ancestry string.
			$ancestryString = $myAncestry['ancestry'];
			
			$sql = "SELECT public_id, name FROM record_table WHERE is_helpdesk_issue IS FALSE AND ancestry LIKE '". $ancestryString .":%'";
			if(is_array($filter)) {
				$sql = create_list($sql, string_from_array($filter, 'select'), ' AND ');
			}
			$sql = create_list($sql, 'ORDER BY ancestry', ' ');
			
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(strlen($dberror) || $numrows < 1) {
				//TODO: log an error.
				if(strlen($dberror)) {
					$this->logsObj->log_dberror("get_children(): database error::: \n". $dberror ."\nSQL::: ". $sql);
				}
			}
			else {
				//retrieve the data.
				$retval = $this->db->farray_nvp('public_id', 'name');
				asort($retval);
			}
			
		}
		
		return($retval);
	}//end get_children()
	//================================================================================================
	
	
	
	//================================================================================================
	function get_children_string($projectId, $filter=NULL)
	{
		//retrieve the array of data.
		$childrenList = $this->get_children($projectId, $filter);
		
		//if it's not null, loop it.
		$retval = NULL;
		if(!is_null($childrenList)) {
			//LOOP IT!
			foreach($childrenList as $id=>$name) {
				//create the string.
				$name = cleanString($name, "htmlspecial_nq");
				$name = cleanString($name, "htmlentity_plus_brackets");
				
				//TODO: implement a template file instead of just creating strings...
				$string = ' -- <a href="/content/project/view/?ID='. $id .'">'. $name .'</a>';
				$retval = create_list($retval, $string, "<BR>\n");
			}
		}
		
		return($retval);
	}//end get_children_string()
	//================================================================================================
	
	
	
	//================================================================================================
	public function get_project_user_associations($projectIdList=NULL) {
		$retval = NULL;
		if(!is_null($projectIdList)) {
			//set a var that says it should be just listed straight-up.
			$justUserList = TRUE;
			if(is_array($projectIdList)) {
				$justUserList = FALSE;
				$projectIdList = array_unique($projectIdList);
			}
			
			$sqlArr = array(
				'record_id'	=> $projectIdList
			);
			
			//build the query.
			$sql = "SELECT record_contact_link_id, record_id, contact_id FROM record_contact_link_table " .
				"WHERE " . string_from_array($sqlArr, 'select');
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(strlen($dberror) || $numrows < 1) {
				//something went wrong.
				if(strlen($dberror)) {
					//log the error.
					$details = "get_project_user_associations(): numrows=(". $numrows ."), dberror:::\n". 
						$dberror ."\nSQL::: ". $sql;
					$this->logsObj->log_dberror($details);
				}
			}
			else {
				//retrieve the results.
				if($justUserList) {
					//just get the list of users.
					$retval = $this->db->farray_nvp('record_contact_link_id', 'contact_id');
				}
				else {
					//sort them into arrays keyed off project_id.
					$data = $this->db->farray_fieldnames('record_contact_link_id');
					$retval = array();
					foreach($data as $index=>$subArr) {
						$key = $subArr['record_id'];
						$val = $subArr['uid'];
						$retval[$key][] = $val;
					}
				}
			}
		}
		
		return($retval);
		
	}//end get_project_user_associations()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * Link contacts (not necessarily USERS) to a project.
	 */
	public function assign_users_to_project($projectId, array $userList=NULL) {
		//first, get a list of the current users.
		$projectData = $this->get_details($projectId, FALSE);
		$oldUserList = $projectData['linked_users'];
		
		//TODO: make this do intelligent inserts/removes.
		$this->db->beginTrans();
		$sql = "DELETE FROM record_contact_link_table WHERE record_id=". $projectId;
		
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		//set a default return.
		$retval = NULL;
		
		if(strlen($dberror) || $numrows != count($oldUserList)) {
			//TODO: log the problem.
			$this->db->rollbackTrans();
			throw new exception("assign_users_to_project(): projectId=($projectId)... numrows=($numrows), dberror:::\n". $dberror);
		}
		else {
			//must've deleted properly.  Run the inserts.
			if(!is_null($userList) && count($userList)) {
				//run inserts.
				$numInserted = 0;
				foreach($userList as $index=>$uid) {
					//create the SQL statement.
					$sqlArr = array(
						'record_id'		=> $projectId,
						'contact_id'	=> $uid
					);
					$sql = "INSERT INTO record_contact_link_table ". string_from_array($sqlArr, 'insert', NULL, 'numeric');
					
					//run it & capture the results.
					$numrows = $this->db->exec($sql);
					$dberror = $this->db->errorMsg();
					
					if(strlen($dberror) || $numrows !== 1) {
						//fail it!
						$retval = -1;
						$this->db->rollbackTrans();
						throw new exception("assign_users_to_project(): numrows=($numrows), dberror::: ". $dberror);
						break;
					}
					else {
						$numInserted++;
					}
				}
				
				if($numInserted) {
					$retval = $numInserted;
					$this->db->commitTrans();
				}
			}
			else {
				//done.
				$this->db->commitTrans();
				$retval = 0;
			}
		}
		
		return($retval);
		
	}//end assign_users_to_project()
	//================================================================================================
	
	
	
	//================================================================================================
	/**
	 * 
	 */
	private function build_ancestry_string($currentProjectId, $newParent=NULL) {
		//
		if(is_numeric($currentProjectId) && (is_numeric($newParent) && $newParent > 1)) {
			//got a new parent (it's been adopted), the child is non-null.
			$newParentData = $this->get_details($newParent, FALSE);
			$retval = create_list($newParentData['ancestry'], $currentProjectId, ':');
		}
		elseif(is_numeric($currentProjectId) && (is_null($newParent) || !is_numeric($newParent))) {
			//it's supposed to be a top-level project.
			$retval = $currentProjectId;
		}
		else {
			//something is horribly broken.
			cs_debug_backtrace();
			throw new exception("build_ancestry_string(): invalid project (".$currentProjectId.") or newParent (".$newParent.")");
		}
		
		debug_print("build_ancestry_string(): from projectId=($currentProjectId) and newParent=($newParent) we get ($retval)");
		return($retval);
	}//end build_ancestry_string();
	//================================================================================================
	
}//end projectClass{}
?>
