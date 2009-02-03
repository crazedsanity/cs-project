<?php 
/*
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id:globalFunctions.php 626 2007-11-20 16:54:11Z crazedsanity $
 * Last Author::::::::: $Author:crazedsanity $ 
 * Current Revision:::: $Revision:626 $ 
 * Repository Location: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/lib/globalFunctions.php $ 
 * Last Updated:::::::: $Date:2007-11-20 10:54:11 -0600 (Tue, 20 Nov 2007) $
 */


//##########################################################################
/**
 * Return a list of required libraries & versions.
 */
function get_required_external_lib_versions($projectName=NULL) {
	//format: {className} => array({projectName} => {exactVersion})
	$requirements = array(
		'contentSystem'		=> array('cs-content',		'1.0.0-ALPHA3'),
		'cs_phpxmlParser'	=> array('cs-phpxml',		'1.0.0-ALPHA2'),
		'cs_arrayToPath'	=> array('cs-arrayToPath',	'1.0.0')
	);
	
	if(!is_null($projectName)) {
		$newArr = array();
		foreach($requirements as $index=>$subArr) {
			if($subArr[0] == $projectName) {
				$retval = $requirements[$index];
				break;
			}
		}
	}
	else {
		$retval = $requirements;
	}
	return($retval);
	
}//end get_required_external_lib_versions()
//##########################################################################



//##########################################################################
/**
 * Check to make sure we've got the versions required to run.
 */
function check_external_lib_versions() {
	$retval = 0;
	//format: {className} => array({projectName} => {exactVersion})
	$requirements = get_required_external_lib_versions();
	
	foreach($requirements as $className => $more) {
		$matchProject = $more[0];
		$matchVersion = $more[1];
		
		
		if(class_exists($className)) {
			//hopefully, this initializes each of them as a TEST class.
			$obj = new $className('unit_test');
			if($obj->isTest === TRUE) {
				//okay, get version & project names.
				if(method_exists($obj, 'get_version') && method_exists($obj, 'get_project')) {
					try {
						$realVersion = $obj->get_version();
						$realProject = $obj->get_project();
					}
					catch(exception $e) {
						throw new exception(__METHOD__ .": unable to get project name or version for (". $className ."), DETAILS::: ". $e->getMessage());
					}
					
					if($realVersion === $matchVersion && $realProject === $matchProject) {
						//all looks good.
						$retval++;
					}
					else {
						throw new exception(__FUNCTION__ .": version mismatch (". $realVersion ." != ". $matchVersion .") or " .
							"invalid project name (". $realProject ." != ". $matchProject .")");
					}
				}
				else {
					throw new exception(__FUNCTION__ .": required checking method(s) for (". $className .") missing");
				}
			}
			else {
				throw new exception(__FUNCTION__ .": ". $className ."::isTest isn't set, something is broken");
			}
		}
		else {
			throw new exception(__FUNCTION__ .": required class (". $className .") could not be found");
		}
	}
	
	return($retval);
	
}//end check_external_lib_versions()
//##########################################################################



//##########################################################################
function html_file_to_string($file){
	//take the given file int the template dir and return it as a string;
	$filename = template_file_exists($file);
	if($filename !== 0) {
		$htmlString = file_get_contents($filename);
		return $htmlString;
	}
	else {
		cs_debug_backtrace(1);
		//Could not find the file requested to stringify.
		//Sending warning to user and logging it.

		set_message(
			"Warning!",
			"Could not find all files necessary to create this page.<br>Please call technical support.<BR>\nfile=[". $file ."]",
			"","status"
		);
		return(NULL);
	}

}//end html_file_to_string()
//##########################################################################

//##########################################################################
function page_get_env($showReferer = true, $showRequest = true, $returnArray=FALSE) {
	//////////////////////////////////////////////////////////////////
	// GETS VARIOUS INFORMATION ABOUT THE USER'S CURRENT PAGE, IT'S
	//	REFERRER, IP INFO, ETC. & RETURNS IT IN A STRING. 
	//	CREATED TO AVOID CODE REPLICATION.
	//	
	// INPUTS::
	//		showReferer		Boolean true/false - whether or not to show HTTP_REFERER
	//		showRequest		Boolean true/false - whether or not to show REQUEST_URI
	// OUTPUTS:::
	//		<string>			OK: string holds the key to the city.
	//////////////////////////////////////////////////////////////////
	$referer = NULL;
	$retval = NULL;
	//determine if it's "http" or "https".
	if($_SERVER['SERVER_PORT'] == 80) {
		$pre = "http://";
	}
	else {
		$pre = "https://";
	}

	//start constructing the stuff.
	$info = $pre . $_SERVER['HTTP_HOST']; // Ex. http://www.partslogistics.com

	if ($showRequest) {
		$info .= $_SERVER['REQUEST_URI']; // Ex. /index.php
	}
	$currentPageUri = $info;

	$info .= " -- ". $_SERVER['REMOTE_ADDR']; // Ex. 192.160.10.100
	
	//SHOW THE PAGE THEY'RE VIEWING, ALONG WITH THE REFERER (FOR FUTURE STATS)...
	if (isset($_SERVER['HTTP_REFERER'])) {
		$referer = $_SERVER['HTTP_REFERER'];
	}

	if((strlen($referer) < 1) || !$showReferer) {
		//Set it to nothings if we don't have anything, or we're not supposed to show it.
		$referer = "--";
	}
	
	if(!$returnArray) {
		$retval = $info ." [$referer] -- [". $_SERVER['HTTP_USER_AGENT'] ."]";
	}
	else {
		//
		$retval = array(
			"referer" 		=> $referer,
			"user_agent"	=> $_SERVER['HTTP_USER_AGENT'],
			"current_page"	=> $currentPageUri
		);
	}
	return($retval);
	
}//page_get_env()
//##########################################################################



//##########################################################################
/**
 * Generic way to log activities.
 */
function log_activity(cs_phpDB &$db, $eventId, $affectedUid, $details) {
	
	//create an array to create the insert string.
	$sqlArr = array (
		'log_event_id'		=> cleanString($eventId, 'numeric'),
		'group_id'			=> cleanString($_SESSION['group_id'], 'numeric'),
		'uid'				=> cleanString($_SESSION['uid'], 'numeric'),
		'affected_uid'		=> cleanString($affectedUid, 'numeric'),
		'details'			=> "'". cleanString($details, 'sql') ."'"
	);
	
	//create the insert string.
	$sql = "INSERT INTO log_table ". string_from_array($sqlArr, 'insert');
	
	//now attempt to perform the insert.
	$numrows = $db->exec($sql);
	$dberror = $db->errorMsg();
	
	if(strlen($dberror) || $numrows !== 1) {
		//terrible, terrible things.
		print "<pre>";
		debug_print_backtrace();
		throw new exception(__FUNCTION__ .": failed to create record... numrows=($numrows), dberror:::\n$dberror\nSQL::: $sql");
	}
	else {
		//set a return value, in case the query for the inserted id fails.
		$retval = $numrows;
		
		//good to go: get the log_id that was just created.
		$sql = "SELECT currval('log_table_log_id_seq'::text)";
		$numrows = $db->exec($sql);
		$dberror = $db->errorMsg();
		
		//it's okay if it doesn't work.
		if(!strlen($dberror) && $numrows == 1) {
			//got it!
			$data = $db->farray();
			$retval = $data[0];
		}
	}
	
	return($retval);
	
}//end log_activity()
//##########################################################################



//##########################################################################
function set_message($title=NULL, $message=NULL, $redirect=NULL, $type=NULL, $linkText=NULL, $overwriteSame=NULL) {
	//this'll set an error or status message into the session.  It's then 
	//	handled appropriately by the print_page() function in genericPageClass.php.
	//
	//$_SESSION["message"]
	//      => "title"      = $title
	//      => "message"    = $msg
	//      => "redirect"   = $redirect

	if(!isset($overwriteSame)) {
		$overwriteSame = 1;
	}

	//create a listing for message priority....
	$priorityArr = array(
		"notice" => 10,
		"status" => 20,
		"error" => 30,
		"fatal" => 100
	);
	
	//make sure the message type is IN the priority array...
	if(!in_array($type, array_keys($priorityArr))) {
		//it's not valid.  Just exit.
		print_r($message);	
		exit("INVALID MESSAGE TYPE: $type<BR>\n");
	}

	//check to see if the message is already set...
	if($_SESSION['message']) {
		//it's set.  Let's see if it's important enough to overwrite the existing one.
		if((!$overwriteSame) AND ($priorityArr[$_SESSION['message']['type']] == $priorityArr[$type])) {
			//all indications point to mostly cloudy with a hint of "LEAVE MY MESSAGE ALONE, BEOTCH".
			return(0);
		}
		elseif($priorityArr[$_SESSION['message']['type']] <= $priorityArr[$type]) {
			// the existing message is less important.  Overwrite it.
			unset($_SESSION['message']);
		}
		else {
			//we've got a more important message waiting.  No pudding for you.
			return(0);
		}
	}


	if(($title == "NULL") AND ($message == "NULL") AND ($redirect == "NULL") AND ($type == "NULL")) {
		//don't bother printing.  Just exit.
		return(0);
	}


	//make SURE we don't run into problems with case-sensitivity.
	if($type) $type = strtolower($type);

	$dTitle    = "Unknown Error";
	$dMsg      = "An unknown error has occurred.<br>Please make note of the steps taken ";
	$dMsg	  .= "to produce this error and report it to technical support.";
	$dMsg	  .= "<BR> TYPE: '$type' || TITLE: '$title'";
	$dRedirect = "/contactus.php";
	$dType     = "error";
	$dLinkText = "Contact Us.";

	//check some things first.
	//runs down the line to see which variables were set; if any
	if($title) {
		$dTitle 	= $title;
		$dMsg 		= $message;
		$dRedirect 	= $redirect;
		$dType 		= $type;
		$dLinkText 	= $linkText;
	}
	$dMsg = wordwrap($dMsg, 57);
	//no ELSE statement; if nothing else is set, the defaults kick in.
	//$title not sent.  Give a generic error.
	$_SESSION["message"] = array(
		"title"   => $dTitle,
		"message" => $dMsg,
		"redirect"=> $dRedirect,
		"linkText"=> $dLinkText,
		"type"    => $dType
		
	);

} // end of set_message()
//##########################################################################



//##########################################################################
function set_message_wrapper($array) {
	//$array should look like this:
	//	"title" 	=> "<title value>",
	//	"message"	=> "<message value>",
	//	"redirect"	=> "<redirect value>",
	//	"type"		=> "<status/error>"

	@set_message($array['title'], $array['message'], $array['redirect'],$array['type'], $array['linkText'], $array['overwriteSame']);
}//end set_message_wrapper
//##########################################################################



//##########################################################################
function swapValue(&$value, $c1, $c2) {
	if(!$value) {
		$value = $c1;
	}


	/* choose the next color */
	if($value == "$c1") {
		$value = "$c2";
	}
	else {
		$value = "$c1";
	}

	return($value);
}//end swapValue
//##########################################################################




//##########################################################################
function get_block_row_defs($templateContents) {
	//////////////////////////////////////////////////////////////////
	// Given the _contents_ of a template, this function will retrieve
	//	all block row definitions in the order that they can be
	//	safely be set via set_block_row() in GenericPage{}.
	// INPUTS:::
	//	templateContents	<str> Contents of the template to
	//				  be parsed.
	// OUTPUTS:::
	//	<array>
	//================================================================
	// NOTE::: if there are no block rows or there's an error, a blank
	//	array will be returned.
	// LAYOUT OF SUCCESSFUL ARRAY:::
	//	array(
	//		incomplete => array(
	//			begin	=> array()
	//			end	=> array()
	//		ordered => array()
	//
	// NOTE 2::: each sub-array (within "incomplete/begin","incomplete/end",
	//	and "ordered" are {number} => {definition_name}
	// NOTE 3::: the {number} inside either of the "incomplete" sub-arrays
	//	will retain the index value they were found at (i.e. if there's
	//	a "BEGIN" for "tempRow" but no end, and its the 3rd definition
	//	on the page, it'll be:
	//		begin => array(
	//			[2] => tempRow
	//		)
	//////////////////////////////////////////////////////////////////
	//cast $retArr as an array, so it's clean.
	$retArr = array();
	
	//NOTE: the value 31 isn't just a randomly chosen length; it's the minimum
	// number of characters to have a block row.  EG: "<!-- BEGIN x -->o<!-- END x -->"
	if(strlen($templateContents) >= 31) {
		//looks good to me.  Run the regex...
		$flags = PREG_PATTERN_ORDER;
		$reg = "/<!-- BEGIN (.+) -->/";
		preg_match_all($reg, $templateContents, $beginArr, $flags);
		$beginArr = $beginArr[1];
		
		$endReg = "/<!-- END (.+) -->/";
		preg_match_all($endReg, $templateContents, $endArr, $flags);
		$endArr = $endArr[1];

		//create a part of the array that shows any orphaned "BEGIN" statements (no matching "END"
		// statement), and orphaned "END" statements (no matching "BEGIN" statements)
		// NOTE::: by doing this, should easily be able to tell if the block rows were defined
		// properly or not.
		if(count($retArr['incomplete']['begin'] = array_diff($beginArr, $endArr)) > 0) {
			//I'm sure there's an easier way to do this, but my head hurts too much when 
			// I try to do the magic.  Maybe I need to put another level in CodeMancer...
			foreach($retArr['incomplete']['begin'] as $num=>$val) {
				unset($beginArr[$num]);
			}
		}
		if(count($retArr['incomplete']['end'] = array_diff($endArr, $beginArr)) > 0) {
			//both of the below foreach's simply pulls undefined vars out of the
			// proper arrays, so I don't have to deal with them later.
			foreach($retArr['incomplete']['end'] as $num=>$val) {
				unset($endArr[$num]);
			}
		}
		
		//YAY!!! we've got valid data!!!
		//reverse the order of the array, so when the ordered array
		// is looped through, all block rows can be pulled.
		$retArr['ordered'] = array_reverse($beginArr);
	}
	else {
		//nothin' doin'.  Return a blank array.
		$retArr = array();
	}
	
	return($retArr);
}//end get_block_row_defs()
//##########################################################################



//##########################################################################
function array_as_option_list(array $data, $checkedValue=NULL, $type="select", $useTemplateString=NULL, array $repArr=NULL) {
	$typeArr = array (
		"select"	=> "selected",
		"radio"		=> "checked",
		"checkbox"	=> "checked"
	);
	
	$myType = $typeArr[$type];
	if(is_null($useTemplateString)) {
		//
		$useTemplateString = "\t\t<option value='%%value%%'%%selectedString%%>%%display%%</option>";
	}
	
	$retval = "";
	foreach($data as $value=>$display) {
		//see if it's the value that's been selected.
		$selectedString = "";
		if($value == $checkedValue || $display == $checkedValue) {
			//yep, it's selected.
			$selectedString = " ". $myType;
		}
		
		//create the string.
		$myRepArr = array(
			'value'				=> $value,
			'display'			=> $display,
			'selectedString'	=> $selectedString
		);
		if(is_array($repArr) && is_array($repArr[$value])) {
			//merge the arrays.
			$myRepArr = array_merge($repArr[$value], $myRepArr);
		}
		$addThis = mini_parser($useTemplateString, $myRepArr, "%%", "%%");
		$retval = create_list($retval, $addThis, "\n");
	}
	
	return($retval);
}//end array_as_option_list()
//##########################################################################



//##########################################################################
function query_as_option_list(&$db, $selFields, $table, $critSent, $selectVal=NULL, $selectStr="selected",$useCapitals=1) {
	//Regardless of the query, takes $field[0] as the display value, and
	//	$field[1] as the select value.
	//INPUT CHECKING.
	if(!$selFields) {
		return(0);
	}

	$fields = split(",", $selFields);

	//Default, order by $fields[1], and no crit.
	$order = "ORDER BY ".$fields[1];
	$crit="";

	if($critSent) {
		if (eregi("order by",$critSent)) {
			//We are ordering, split the goods
			//Only take criteria before order by
			$crit = strtolower($critSent);
	    	$tempVar = split("order by", $crit);
			if (strlen($tempVar[0]) > 2) {
   		   		$crit = "WHERE ".$tempVar[0];
   	   		}
			else {
				$crit="";
			}
			$order = "ORDER BY ".$tempVar[1];
		}
		else {
			//No order, can use crit as is and order by generic.
			$crit = "WHERE $critSent";
		}
	}
	
	$query = "SELECT $selFields FROM $table $crit $order";
	$db->exec($query);
	$dberror = $db->errorMsg(1,1,1, __FUNCTION__ .": ", "QUERY: $query");
	$numRows = $db->numRows();
	if((!$dberror) AND ($numRows > 0)) {
		$mainArr = $db->farray_fieldnames(NULL,1);
		foreach($mainArr as $row=>$tArr) {
			if ($useCapitals) {
				//Don't do the regular capitalization
				$dataValue=$tArr[$fields[1]];
			}
			else {	
				$dataValue = ucwords(strtolower($tArr[$fields[1]]));
			}
			
			$dataName = $tArr[$fields[0]];
			if($dataName == $selectVal && ($selectVal !== TRUE)) {
				$checked = $selectStr;
			}
			else {
				$checked = NULL;
			}
			$returnStr = "<option value=\"$dataName\" $checked>$dataValue</option>";
			$retVal = create_list($retVal, $returnStr, "\n");
		}
	}
	else {
		$retVal = $numRows;
	}
	return($retVal);
}//end query_as_option_list()
//##########################################################################




//##########################################################################
function conditional_header($url,$permRedir=FALSE) {
	$gf = new cs_globalFunctions;
	$gf->conditional_header($url, FALSE, $permRedir);
}//end conditional_header()
//##########################################################################



//##########################################################################
function create_list($string=NULL, $addThis=NULL, $delimiter=", ") {
	//////////////////////////////////////////////////////////////////
	//RETURNS A COMMA-DELIMITED LIST OF ITEMS... IF	WE		//
	//	WANTED TO GET A LIST OF THINGS W/COMMAS, WE'D 		//
	//	NORMALLY HAVE TO RUN ALL THIS CODE IN THE SCRIPT...	//
	//	$list = array("x", "y");				//
	//								//
	//	NOW IT WOULD LOOK SOMETHING LIKE:			//
	//	foreach ($list as $item) {				//
	//		$newList = this_function($newList,$item);	//
	//	}							//
	//////////////////////////////////////////////////////////////////

	if(isset($string)) {
		$retVal = $string . $delimiter . $addThis;
	}
	else {
		$retVal = $addThis;
	}

	return($retVal);
} //end create_list()
//##########################################################################


//##########################################################################
function string_from_array($array,$style=NULL,$separator=NULL, $cleanString=NULL, $removeEmptyVals=FALSE, $removeValsNotInCleanArr=FALSE) {
	/**
	 * Basically, just a wrapper for create_list(), which returns a list or 
	 * an array of lists, depending upon what was requested.
	 * 
	 * @param $array		<array> list for the array...
	 * @param $style		<str,optional> what "style" it should be returned 
	 *                         as (select, update, etc).
	 * @param $separator	<str,optional> what separattes key from value: see each
	 * 							style for more information.
	 * @param $cleanString	<mixed,optional> clean the values in $array by sending it
	 * 							to cleanString(), with this as the second argument.
	 * @param $removeEmptyVals	<bool,optional> If $cleanString is an ARRAY and this
	 * 							evaluates as TRUE, indexes of $array whose values have
	 *							a length of 0 will be removed.
	 *
	 * TODO: explain return values
	 * TODO: look into a better way of implementing the $removeEmptyVals thing.
	 */
	
	//precheck... if it's not an array, kill it.
	if(!is_array($array)) {
		return(0);
	}
	
	//make sure $style is valid.
	$typesArr = array("insert", "update");
	$style = strtolower($style);
	$previousCleanType = array();
	
	if(is_array($array)) {
	
		//if $cleanString is an array, assume it's arrayIndex => cleanStringArg
		$precleaned = 0;
		if(is_array($cleanString) && (!is_null($style) && (strlen($style)))) {
			$cleanStringArr = array_intersect_key($cleanString, $array);
			if(count($cleanStringArr) > 0 && is_array($cleanStringArr)) {
				foreach($cleanStringArr as $myIndex=>$myCleanStringArg) {
					if(($removeEmptyVals) && ((strlen($array[$myIndex]) == 0) || is_null($array[$myIndex]))) {
						//remove the index.
						unset($array[$myIndex]);
					}
					else {
						$array[$myIndex] = cleanString($array[$myIndex], $myCleanStringArg);
						$previousCleanType[$myIndex] = $myCleanStringArg;
					}
				}
				
				//drop items not explicitely listed in the cleanString array.
				if($removeValsNotInCleanArr) {
					$array = array_intersect_key($array, $cleanString);
				}
			}
			unset($cleanString);
			$precleaned = 1;
		}
		
		switch($style) {
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			case "insert":
			if(!$separator) {
				$separator = " VALUES ";
			}
			//build temporary data...
			foreach($array as $key=>$value) {
				$tmp[0] = create_list($tmp[0], $key);
				//clean the string, if required.
				if((is_null($value)) OR ($value == "") || ($value == "NULL")) {
					$value = "NULL";
				}
				else {
					if($precleaned == 1 && $previousCleanType[$key] == 'none') {
					}
					elseif($cleanString) {
						//make sure it's not full of poo...
						$value = cleanString($value, "sql",1);
					}
					elseif(!is_numeric($value) && !preg_match('/^\'/', $value)) {
						$value = "'". $value ."'";
					}
				}
				$tmp[1] = create_list($tmp[1], $value);
			}
			
			//make the final product.
			$retval = "(". $tmp[0] .")" . $separator . "(". $tmp[1] .")";
			break;
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			case "update":
			if(!$separator) {
				$separator = "=";
			}
			if(!$cleanString) {
				$cleanString = 'sql';
			}
			//build final product.
			foreach($array as $field=>$value) {
				//should it be quoted?
				$sqlQuotes = NULL;
				if(!preg_match('/^\'/',$value) && !preg_match('/\'$/',$value)) {
					$sqlQuotes = 1;
					if($precleaned == 1 && $value === "NULL") {
						//NO!!! It's a literal NULL, don't change the damn thing into a string!
						$sqlQuotes = 0;
					}
				}
				if($cleanString) {
					//make sure it doesn't have crap in it...
					$value = cleanString($value, $cleanString, $sqlQuotes);
				}
				$retval = create_list($retval, $field . $separator . $value);
			}
			break;
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			case "order":
			//for creating strings like "ORDER BY st.service_type_id,r.status DESC, r.length,r.credits ASC";
			$separator = ", ";
			//build final product.
			foreach($array as $field=>$value) {
				//assume key is the field, but if not, switch 'em.
				if(is_numeric($field) && (!is_numeric($value) && strlen($value) > 1)) {
					$tmp = $value;
					$field = $tmp;
					$value = "";
				}
				if($cleanString) {
					//make sure it doesn't have crap in it...
					$value = cleanString($value, "sql");
				}
				$fieldPlusValue = create_list($field, $value, " ");
				$retval = create_list($retval, $fieldPlusValue, $separator);
			}
			if(!preg_match('/order by/', strtolower($retval))) {
				$retval = "ORDER BY ". $retval;
			}
			break;
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			
			case "limit":
			//for creating the "limit 50 offset 35" part of a query... or at least using that "style".
			$separator = " ";
			//build final product.
			foreach($array as $field=>$value) {
				if($cleanString) {
					//make sure it doesn't have crap in it...
					$value = cleanString($value, "sql");
				}
				$retval = create_list($retval, $field . $separator . $value, " ");
			}
			break;
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			case "select":
			//build final product.
			foreach($array as $field=>$value) {
				$separator = "=";
				
				//allow for tricksie things...
				/*
				 * Example: 
				 * string_from_array(array("y"=>3, "x" => array(1,2,3))); 
				 * 
				 * would yield: "y=3 AND (x=1 OR x=2 OR x=3)"
				 */
				$delimiter = "AND";
				if(is_array($value)) {
					//doing tricksie things!!!
					$retval = create_list($retval, $field ." IN (". string_from_array($value) .")", " $delimiter ");
				}
				else {
					//if there's already an operator ($separator), don't specify one.
					if(preg_match('/ like$/', $field)) {
						$field = preg_replace('/ like$/', '', $field);
						$separator = " like ";
					}
					elseif(preg_match('/^[\(<=>]/', $value)) {
						$separator = NULL;
					}
					if($cleanString) {
						//make sure it doesn't have crap in it...
						$value = cleanString($value, "sql");	
					}
					elseif(!is_numeric($value) && isset($separator)) {
						$value = "'". $value ."'";	
					}
					$retval = create_list($retval, $field . $separator . $value, " $delimiter ");
				}
			}
			break;
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			
			
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			case "url":{
				//an array like "array('module'='task','action'='view','ID'=164)" to "module=task&action=view&ID=164"
				if(!$separator) {
					$separator = "&";
				}
				foreach($array as $field=>$value) {
					if($cleanString && !is_array($cleanString)) {
						$value = cleanString($value, $cleanString);
					}
					$retval = create_list($retval, "$field=$value", $separator);
				}
			}
			break;
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			
			
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			case "report":{
				//turn array('first'=>1,'second'=>2) into "first: 1\nsecond: 2"
				foreach($array as $field=>$value) {
					$value = cleanString($value, 'sql');
					$retval = create_list($retval, "$field: $value", "\n");
				}
			}
			break;
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			
			
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			case "text_list":{
				if(is_null($separator)) {
					$separator = '=';
				}
				foreach($array as $field=>$value) {
					$retval = create_list($retval, $field . $separator . $value, "\n");
				}
			}
			break;
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			
			
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			case "html_list":{
				if(is_null($separator)) {
					$separator = '=';
				}
				foreach($array as $field=>$value) {
					$retval = create_list($retval, $field . $separator . $value, "<BR>\n");
				}
			}
			break;
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			
			
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			DEFAULT:
			if(!$separator) {
				$separator = ", ";
			}
			foreach($array as $field=>$value) {
				if($cleanString) {
					$value = cleanString($value, $cleanString);
				}
				$retval = create_list($retval, $value, $separator);
			}
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		}
	}
	else {
		//not an array.
		$retval = 0;
	}
	
	return($retval);
}//end string_from_array()
//##########################################################################




//##########################################################################
	//////////////////////////////////////////////////////////////////
	// Shows a backtrace of function calls; used within a function
	//	to determine how it was called.
	//
	// INPUTS::: 
	//	<void>		<none>
	// OUTPUTS:::
	//	<string>	Denotes the backtrace data.
	//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	// NOTE::: Gotta check to make sure it exists, as PHP5 actually
	//	has a function with this name (I'm hoping it does the
	//	same thing)...
	//////////////////////////////////////////////////////////////////
if(!function_exists("debug_print_backtrace")) {
	function debug_print_backtrace() {
		$stuff = debug_backtrace();
		if(is_array($stuff)) {
			foreach($stuff as $num=>$arr) {
				if($arr['function'] !== "debug_print_backtrace") {
					$fromClass = $arr['class'];
					if(!$fromClass) {
						$fromClass = "**GLOBAL**";
					}
					$tempArr[$num] = $arr['function'] ."{". $fromClass ."}";
				}
				unset($fromClass);
			}
			array_reverse($tempArr);
			foreach($tempArr as $num=>$func) {
				$backTraceData = create_list($backTraceData, $func, "<-");
			}
		}
		else {
			//nothing available...
			#$backTraceData = "No backtrace available.";
			$backTraceData = $stuff;
		}
		return($backTraceData);
	}//end debug_print_backtrace()
}
//##########################################################################

//##########################################################################
function debug_print($input=NULL, $printItForMe=NULL, $removeHR=NULL) {
	//////////////////////////////////////////////////////////////////
	// WRAPS GIVEN $input IN <pre> TAGS: WORKS NICELY WHEN PRINTING	//
	//	AN ARRAY TO THE SCREEN.					//
	//								//
	// INPUTS:::							//
	//	$input		Information to print/return.		//
	//	$printItForMe	Print it.				//
	// OUTPUTS:::							//
	//	<string>	Returns "<pre>\n$input\n<pre>\n"	//
	//////////////////////////////////////////////////////////////////
	if(!is_numeric($printItForMe)) {
		$printItForMe = DEBUGPRINTOPT;
	}
	
	if(!is_numeric($removeHR)) {
		$removeHR = DEBUGREMOVEHR;
	}

	ob_start();
	print_r($input);
	$output = ob_get_contents();
	ob_end_clean();

	$output = "<pre>$output</pre>";

	if(!$_SERVER['SERVER_PROTOCOL']) {
		$output = strip_tags($output);
		$hrString = "\n***************************************************************\n";
	}
	else {
		$hrString = "<hr>";
	}
	if($removeHR) {
		unset($hrString);
	}
	
	if($printItForMe) {
		print "$output". $hrString ."\n";
	}
	
	return($output);
} //end debug_print()
//##########################################################################




//##############################################################################################
function valid_email($email_address) {
	//Tell whether or not an email is valid
	if (eregi("^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$",$email_address)) {
		return 1;
	}
	else {
		return 0;
	}
}//end valid_email()
//##############################################################################################




//================================================================================================================
function cleanString($cleanThis=NULL, $cleanType="all",$sqlQuotes=NULL) {
	$cleanType = strtolower($cleanType);
	if(is_array($cleanThis)) {
		foreach($cleanThis as $index=>$value) {
			$cleanThis[$index] = cleanString($value, $cleanType, $sqlQuotes);
		}
	}
	else {
		switch ($cleanType) {
			case "none":
				//nothing to see here (no cleaning wanted/needed).  Move along.
				$sqlQuotes = 0;
			break;
			
			case "query":
				/*
					replace \' with '
					gets rid of evil characters that might lead to SQL injection attacks.
					replace line-break characters
				*/
				$evilChars = array("\$", "%", "~", "*",">", "<", "-", "{", "}", "[", "]", ")", "(", "&", "#", "?", ".", "\,","\/","\\","\"","\|","!","^","+","`","\n","\r");
				$cleanThis = preg_replace("/\|/","",$cleanThis);
				$cleanThis = str_replace($evilChars,"", $cleanThis);
				$cleanThis = stripslashes(addslashes($cleanThis));
			break;
			
			case "sql":
				$cleanThis = addslashes(stripslashes($cleanThis));
			break;
	
			case "double_quote":
				//This will remove all double quotes from a string.
				$cleanThis = str_replace('"',"",$cleanThis);
			break;
	
			case "htmlspecial":
				/*
				This function is useful in preventing user-supplied text from containing HTML markup, such as in a message board or guest book application. 
					The translations performed are:
				      '&' (ampersand) becomes '&amp;'
				      '"' (double quote) becomes '&quot;'.
				      '<' (less than) becomes '&lt;'
				      '>' (greater than) becomes '&gt;' 
					Also converts "{" and "}" to their html entity.
				*/
				$cleanThis = htmlspecialchars($cleanThis);
				$cleanThis = str_replace('{', '&#123;', $cleanThis);
				$cleanThis = str_replace('}', '&#125;', $cleanThis);
			break;
	
			case "htmlspecial_q":
			/*
				'&' (ampersand) becomes '&amp;'
				'"' (double quote) becomes '&quot;'.
				''' (single quote) becomes '&#039;'.
				'<' (less than) becomes '&lt;'
				'>' (greater than) becomes '&gt;
					Also converts "{" and "}" to their html entity.
			*/
				$cleanThis = htmlspecialchars($cleanThis,ENT_QUOTES);
				$cleanThis = str_replace('{', '&#123;', $cleanThis);
				$cleanThis = str_replace('}', '&#125;', $cleanThis);
			break;
	
			case "htmlspecial_nq":
			/*
				'&' (ampersand) becomes '&amp;'
				'<' (less than) becomes '&lt;'
				'>' (greater than) becomes '&gt;
			*/
				$cleanThis = htmlspecialchars($cleanThis,ENT_NOQUOTES);
			break;
	
			case "htmlentity":
				/*	
					Convert all applicable text to its html entity
					Will convert double-quotes and leave single-quotes alone
				*/
				$cleanThis = htmlentities(html_entity_decode($cleanThis));
				$cleanThis = str_replace('$', '&#36;', $cleanThis);
			break;
	
			case "htmlentity_plus_brackets":
				/*	
					Just like htmlentity, but also converts "{" and "}" (prevents template 
					from being incorrectly parse).
					Also converts "{" and "}" to their html entity.
				*/
				$cleanThis = htmlentities(html_entity_decode($cleanThis));
				$cleanThis = str_replace('$', '&#36;', $cleanThis);
				$cleanThis = str_replace('{', '&#123;', $cleanThis);
				$cleanThis = str_replace('}', '&#125;', $cleanThis);
			break;
	
			case "double_entity":
				//Removed double quotes, then calls html_entities on it.
				$cleanThis = str_replace('"',"",$cleanThis);
				$cleanThis = htmlentities(html_entity_decode($cleanThis));
			break;
		
			case "meta":
				// Returns a version of str with a backslash character (\) before every character that is among these:
				// . \\ + * ? [ ^ ] ( $ )
				$cleanThis = quotemeta($cleanThis);
			break;
	
			case "email":
				//Remove all characters that aren't allowed in an email address.
				$cleanThis = preg_replace("/[^A-Za-z0-9\._@-]/","",$cleanThis);
			break;
	
			case "email_plus_spaces":
				//Remove all characters that aren't allowed in an email address.
				$cleanThis = preg_replace("/[^A-Za-z0-9\ \._@-]/","",$cleanThis);
			break;
	
			case "phone_fax":
				//Remove everything that's not numeric or +()-   example: +1 (555)-555-2020 is valid
				$cleanThis = preg_replace("/[^0-9-+() ]/","",$cleanThis);
			break;
			
			case "integer":
			case "numeric":
			case "number":
				//Remove everything that's not numeric.
				$sqlQuotes = 0;
				if(is_null($cleanThis) || !is_numeric($cleanThis)) {
					//non-numeric: set it as a string NULL..
					$cleanThis = "NULL";
				}
				else {
					$cleanThis = preg_replace("/[^0-9\.-]/","",$cleanThis);
				}
			break;
			
			case "decimal":
			case "float":
				//same as integer only the decimal point is allowed
				$cleanThis = preg_replace("/[^0-9\.]/","",$cleanThis);
			break;
			
			case "name":
			case "names":
				//removes everything in the "alpha" case, but allows "'".
				$cleanThis = preg_replace("/[^a-zA-Z']/", "", $cleanThis);
			break;
	
			case "alpha":
				//Removes anything that's not English a-zA-Z
				$cleanThis = preg_replace("/[^a-zA-Z]/","",$cleanThis);
			break;
			
			case "bool":
			case "boolean":
				//makes it either T or F (gotta lower the string & only check the first char to ensure accurate results).
				$cleanThis = interpret_bool($cleanThis, array('f', 't'));
			break;
			
			case "bool_strict":
			case "boolean_strict";
				$cleanThis = interpret_bool($cleanThis, array('false', 'true'));
			break;
			
			case "varchar":
				$cleanThis=cleanString($cleanThis,"query");
				$cleanThis="'" . $cleanThis . "'";
				if($cleanThis == "''") {
					$cleanThis="NULL";	
				}
			break;
			
			case "date":
				$cleanThis = preg_replace("/[^0-9\-]/","",$cleanThis);
				break;
				
			case "datetime":
				$cleanThis=preg_replace("/[^A-Za-z0-9\/: \-\'\.]/","",$cleanThis);
			break;
				
			case "all":
			default:
				// 1. Remove all naughty characters we can think of except alphanumeric.
				$cleanThis = preg_replace("/[^A-Za-z0-9]/","",$cleanThis);
				$sqlQuotes = 0;
			break;
	
		}
		if($sqlQuotes) {
			$cleanThis = "'". $cleanThis ."'";
		}
	}
	return $cleanThis;
}//end cleanString()
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//================================================================================================================


//================================================================================================================
function interpret_bool($inputVal, $interpretArr,$checkArrayKeys=FALSE) {
	//////////////////////////////////////////////////////////////////
	// Takes the given input value, interprets it as though it were
	//	a boolean (using "if($inputVal)"), and returns the associated
	//	values in $interpretArr.
	//	
	// INPUTS:::
	//	inputVal	(str/int/bool) input to evaluate (index 0 is returned on FALSE, index 1 is returned on TRUE).
	//	interpretArr	(array) the value of true & false to return.
	// OUTPUTS:::
	//================================================================
	// EXAMPLE USAGE: 
	//	$inputVal = $this->upgrade_membership();
	//	$interpretAs = array("FAILED", "Successful");
	//	$logMessage = "Result of membership upgrade: ". interpret_bool($inputVal,$interpretAs);
	//	// --->>> gives "Result of membership upgrade: FAILED", or
	//	//		"Result of membership upgrade: Successful"
	//////////////////////////////////////////////////////////////////
	
	if($checkArrayKeys == TRUE) {
		//use the keys of $interpretArr to determine whick value should be returned..
		#$checkArr = array();
		$arrayKeys = array_keys($interpretArr);
		$arrayVals = array_values($interpretArr);
		
		$valueToUse = array_search($inputVal, $arrayKeys);
		$retval = $arrayVals[$valueToUse];
	}
	else {
		//straight-up check.
		if($inputVal) {
			//true.
			$retval = $interpretArr[1];
		}
		else {
			//false.
			$retval = $interpretArr[0];
		}
	}
	
	return($retval);

}//end interpret_bool()
//================================================================================================================




//================================================================================================================
function mini_parser($template, $repArr, $b=NULL, $e=NULL) {
	//////////////////////////////////////////////////
	//give me a template (as a variable),
	//	I'll parse it & return the
	//	final product...
	//
	// INPUT VALUES:::
	//  $template: The template to put the values contained in $repArr into
	//  $repArr:  Array of the form "templateVarName" => "Value"
	//  %b: character(s) that signal the begin of a template variable
	//  %e: character(s) that signal the end of a template variable
	//////////////////////////////////////////////////

	//NOTE::: this doesn't use the templates class...

	//template vars must be like this:: {template_var} unless $b & $e are 
	//	specified.  $b == "{", $e == "}"
	if((!$b) OR (!$e)){
		$b="{";
		$e="}";
	}
	foreach($repArr as $key=>$value) {
		//modify the array...
		$finalArr[$b . $key . $e] = $value;
	}
	
	//run the replacements.  
	$keys = array_keys($finalArr);
	$vals = array_values($finalArr);
	$template = str_replace($keys, $vals, $template);
	
	return($template);
}//end mini_parser()
//================================================================================================================

//##############################################################################################
function get_user_fname($uid=NULL) {
	//////////////////////////////////////////////////////////////////
	// BREAKING THIS INTO IT'S OWN FUNCTION ALLOWS OTHER FUNCTIONS
	//	TO CALL IT (HELPS TO ELIMINATE CODE REPLICATION).
	//////////////////////////////////////////////////////////////////
	if(!is_numeric($uid)) {
		$uid=$_SESSION['uid'];
	}
	
	$db = new phpDB();
	$db->connect();
	//grab their first name.
	$sql="SELECT username 
	      FROM user_table
	      WHERE uid=$uid";

	$db->exec($sql);
	if($db->errorMsg()){
		//log the problem.
		$details  = "globalFunctions.php:get_user_fname() --> ";
		$details .= $db->errorMsg() . $sql . "<br>\n";
		log_activity($db, 13, $uid, $details);
		//set a message, call print_page(), then exit.
		set_message("Database Error",$details,"","fatal");
		#$this->print_page();
		# $exit;
	}
	@$temp=$db->frow();
	$first_name = $temp[0];
	return($first_name);
}//end of get_user_fname()
//##############################################################################################


//================================================================================================================
function truncate_string($string,$maxLength,$endString="...",$strict=FALSE) {
	//////////////////////////////////////////////////////////////////
	// Takes the given string and truncates it so the final string is
	//	a maximum of $maxLength.  Optionally adds a chuck of text to
	//	the end...
	//
	// INPUTS:::
	//	string		<string> this is the string to truncate.
	//	maxLength		<int> Maximum length for the result.
	//	endString		<string,optional> append to the end of
	//				  the string.
	//	strict		<bool> if TRUE, ensures that the final string
	//				  will NOT EXCEED $maxLength.
	// OUTPUTS:::
	//	0			FAIL: unable to truncate (internal error?)
	//	<string>		PASS: truncated string.
	//----------------------------------------------------------------
	// NOTE: "strict" was set because its sometimes not nice to look
	//	at a list of truncated words with certain fonts.
	//////////////////////////////////////////////////////////////////

	//determine if it's even worth truncating.
	$strLength = strlen($string);
	if($strLength <= $maxLength) {
		//no need to truncate.
		$retval = $string;
	}
	else {
		//actually needs to be truncated...
		if($strict) {
			$trueMaxLength = $maxLength - strlen($endString);
		}
		else {
			$trueMaxLength = $maxLength;
		}
		
		//rip the first ($trueMaxLength) characters from string, append $endString, and go.
		$tmp = substr($string,0,$trueMaxLength -1);
		$retval = $tmp . $endString;
	}
	
	return($retval);
	
}//end truncate_string()
//================================================================================================================


//================================================================================================================
function template_file_exists($file) {
	$retval = 0;
	//If the string doesn't start with a /, add one
	if(strncmp("/",$file,1)) {
		//strncmp returns 0 if they match, so we're putting a / on if they don't
		$file="/".$file;
	}
	$filename=$GLOBALS['TMPLDIR'].$file;
	
	if(file_exists($filename)) {
		$retval = $filename;
	} 
	return($retval);
}//end template_file_exists()
//================================================================================================================


//================================================================================================================
// crypt string
function encrypt($password, $saltstring) {
  $salt = substr($saltstring, 0, 2);
  $enc_pw = crypt($password, $salt);
  return $enc_pw;
}//end encrypt()
//================================================================================================================


//================================================================================================================
function parse_date_string($dateString, $showTime=FALSE, $addSeconds=FALSE) {
	$year = substr($dateString,0,4);
	$month= substr($dateString,4,2);
	$day  = substr($dateString,6,2);
	
	$retval = $year .'-'. $month .'-'. $day;
	
	//show the time only if our string's long enough.
	if($showTime && strlen($dateString) == 14) {
		$timeString = substr($dateString,8,6);
		
		//now parse the hours, minutes, & seconds.
		$hours		= substr($timeString,0,2);
		$minutes	= substr($timeString,2,2);
		$seconds	= substr($timeString,4,2);
		
		//drop it into the returned value... 
		$retval .= " ". $hours .":". $minutes;
		if($addSeconds) {
			$retval .= ":". $seconds;
		}
	}
	
	return($retval);
}//end parse_date_string()
//================================================================================================================


//================================================================================================================
/**
 * Takes the present time & returns an acceptable "date string", as the function "parse_date_string()" parses.
 */
function create_date_string() {
	$dateString = date("YmdHis");
	return($dateString);
}//end create_date_string()
//================================================================================================================

	
//================================================================================================
/**
 */
function send_email($toAddr, $subject, $bodyTemplate, $parseArr=NULL) {
	if(!strlen(constant('PHPMAILER_METHOD')) || 
	(constant('PHPMAILER_METHOD') == 'IsSMTP' && !strlen(constant('PHPMAILER_HOST')))) {
		throw new exception(__METHOD__ .": missing constant for method or host");
	}
	if(!ISDEVSITE) {
		//pre-check on the $toAddr.
		$precheck = FALSE;
		$failureString = "";
		if(is_array($toAddr)) {
			$toAddr = array_unique($toAddr);
			foreach($toAddr as $garbage=>$myEmail2Check) {
				if(!valid_email($myEmail2Check)) {
					unset($toAddr[$garbage]);
				}
			}
			if(count($toAddr) > 0) {
				$precheck = TRUE;
			}
			else {
				$failureString = "toAddr not set properly (". $toAddr .")";
			}
		}
		else {
			if(valid_email($toAddr)) {
				$precheck = TRUE;
			}
			else {
				$failureString = "invalid email: ". $toAddr;
			}
		}
		
		if(!$precheck) {
			#return;
			throw new exception(__FUNCTION__ .": failed precheck: ". $failureString);
		}
		
		
		//format the body...
		$body = $bodyTemplate;
		
		//prepend something if it's the dev site.
		if(ISDEVSITE) {
			$subject = "DEV: ". $subject;
		}
		
		if(is_array($parseArr)) {
			//make sure that "project_url" is set.
			$parseArr["project_url"] = PROJECT_URL;
			$parseArr["VERSION_STRING"] = VERSION_STRING;
			$parseArr["PROJ_NAME"] = PROJ_NAME;
	
			foreach($parseArr as $index=>$value) {
				$parseArr[$index] = cleanString($value, "htmlentity_plus_brackets");
			}
			$body = mini_parser($bodyTemplate, $parseArr);
		}
		
		//if magic quotes is on, kill extra slashes.
		if(get_magic_quotes_gpc()) {
			$body = stripslashes($body);
		}
		
		//auto-determine the type of content, and set arguments so we know what to pass to the emailFaxClass{}.
		$contentType = "text";
		$efArg2 = NULL;
		$efArg3 = $body;
		if(preg_match('/<html>/', $body)) {
			$efArg2 = $body;
			$efArg3 = NULL;
			$contentType = "html";
		}
		
		//if multiple recipients, must send multiple emails.
		if(is_array($toAddr)) {
			foreach($toAddr as $emailAddr) {
				try {
					$retval = create_list($retval, send_single_email($emailAddr, $subject, $body));
				}
				catch(exception $e) {
					$retval = create_list($retval, $emailAddr ." (failed: ". $e->getMessage() .")");
				}
			}
		}
		else {
			try {
				$retval = send_single_email($toAddr, $subject, $body);
			}
			catch(exception $e) {
					$retval = $toAddr ." (failed: ". $e->getMessage() .")";
			}
		}
	}
	else {
		//tell 'em what happened.
		$retval = "no emails sent (ISDEVSITE=". ISDEVSITE .")";
	}
	return($retval);
}//end send_email()
//================================================================================================



//--------------------------------------------------------------------------------------------------.
function store_and_return_sorting(&$page, $sortField, $sortType=NULL) {
	$module = $page->ui->get_cache("module");
	
	//retrieve their current sorting information from session cache.
	$currentSortData = $page->ui->get_cache("$module/currentSort");
	
	//create the pref{} object & retrieve sorting data for this module: if none exists, don't bother trying to do anything else.
	$prefObj = new pref($page->db, $_SESSION['uid']);
	$checkThis = $prefObj->get_pref_value_by_name("sorting_". $module);
	
	if(strlen($checkThis)) {
		if(!is_array($currentSortData)) {
			//load preferences (no sorting is stored).
			$tmp = explode('|', $checkThis);
			$sortField = $tmp[0];
			$sortType = $tmp[1];
			$currentSortData = array(
				$sortField => $sortType
			);
			$sortCache = $currentSortData;
		}
		else {
			$sortCache = $page->ui->get_cache("$module/sortCache");
			
			if($sortField) {
				//initialize the sortType, if needed.
				if(!$sortType) {
					$sortType = "ASC";
				}
				
				if(!is_array($sortCache)) {
					//no cache: initialize it.
					$sortCache = array(
						$sortField => $sortType
					);
				}
				else {
					//we've got cache.
					$sortCache[$sortField] = $sortType;
				}
			}
		}
		
		//add template vars for it, so they can reverse-sort the column.
		if(is_array($sortCache)) {
			foreach($sortCache as $key => $value) {
				$page->add_template_var($key ."_sortType", swapValue($value, "ASC", "DESC"));
			}
		}
		
		//store the what the CURRENT sorting is.
		if($sortField) {
			$currentSortData = array(
				$sortField	=> $sortType
			);
		}
		else {
			$currentSortData = $page->ui->get_cache("$module/currentSort");
		}
		
		//now store it again...
		if($sortField) {
			$page->ui->set_cache("$module/sortCache", $sortCache);
			$page->ui->set_cache("$module/currentSort", $currentSortData);
		}
	}
	
	return($currentSortData);

}//end store_and_return_sorting()
//--------------------------------------------------------------------------------------------------


//--------------------------------------------------------------------------------------------------
function create_priority_option_list($selectThis=NULL, $max=50, $min=0, $addEmptyOption=NULL) {
	if($max < $min) {
		$tMin = $max;
		$min = $max;
		$max = $tMin;
	}
	if(!is_numeric($min)) {
		$min = 0;
	}
	if(!is_numeric($max)) {
		$max = 50;
	}
	if($addEmptyOption) {
		$priorityOptionList .= "<option value=\"\" $selected></option>\n";
	}
	
	for($i=$max; $i >= $min; $i--) {
		$selected = "";
		if($selectThis == $i) {
			$selected = "selected";
		}
		$priorityOptionList .= "<option value=\"$i\" $selected>$i</option>\n";
	}
	
	return($priorityOptionList);
}//end create_priority_option_list()
//--------------------------------------------------------------------------------------------------


function cs_debug_backtrace($printItForMe=NULL,$removeHR=NULL) {
	if(is_null($printItForMe)) {
		$printItForMe = DEBUGPRINTOPT;
	}
	if(is_null($removeHR)) {
		$removeHR = DEBUGREMOVEHR;
	}
	if(function_exists("debug_print_backtrace")) {
		//it's PHP5.  use output buffering to capture the data.
		ob_start();
		debug_print_backtrace();
		
		$myData = ob_get_contents();
		ob_end_clean();
	}
	else {
		//create our own backtrace data.
		$stuff = debug_backtrace();
		if(is_array($stuff)) {
			foreach($stuff as $num=>$arr) {
				if($arr['function'] !== "debug_print_backtrace") {
					$fromClass = $arr['class'];
					if(!$fromClass) {
						$fromClass = "**GLOBAL**";
					}
					$tempArr[$num] = $arr['function'] ."{". $fromClass ."}";
				}
				unset($fromClass);
			}
			array_reverse($tempArr);
			foreach($tempArr as $num=>$func) {
				$myData = create_list($myData, $func, "<-");
			}
		}
		else {
			//nothing available...
			$myData = $stuff;
		}
	}
	
	$backTraceData = debug_print($myData, $printItForMe, $removeHR);
	return($backTraceData);
}//end cs_debug_backtrace()



function get_config_db_params() {
	$requiredFields = array('dbname', 'host', 'port', 'user', 'password');
	$params = array();
	foreach($requiredFields as $name) {
		$constantName = 'DATABASE__'. strtoupper($name);
		if(defined($constantName)) {
			$params[$name] = constant($constantName);
		}
		else {
			throw new exception(__FUNCTION__ .": missing setting for (". $constantName ."): have you gone through setup?");
		}
	}
	
	return($params);
}//end get_config_db_params()





//=============================================================================
function create_page_title(cs_genericPage &$page, array $parts) {
	$titleCacheURL = '/pageData/title';
	$argCacheURL = '/pageData/pieces';
	
	if(!isset($page->ui) || get_class($page->ui) != 'sessionCacheClass') {
		$page->ui = new sessionCache("/userInput/content");
	}
	$cachedParts = $page->ui->get_cache($argCacheURL);
	if(!strlen($parts['module']) || !strlen($parts['title'])) {
		if(!strlen($parts['module']) && !strlen($cachedParts['module'])) {
			throw new exception(__METHOD__ .": found cache, but module missing");
		}
	}

	if(!strlen($parts['module'])) {
		$parts['module'] = $cachedParts['module'];
	}
	$retval = ucfirst($parts['module']) ."" . " [". PROJ_NAME ."]";
	if(strlen($parts['title'])) {
		$retval = ucwords($parts['title']) ." -- ". $retval;
	}
	
	$page->ui->set_cache($argCacheURL, $parts);
	$page->ui->set_cache($titleCacheURL, $retval);
	
	$page->add_template_var('html_title', $retval);
	
	return($retval);
}//end create_page_title();
//=============================================================================



//=============================================================================
function get_page_title(cs_genericPage &$page) {
	$cacheURL = '/pageData/title';
	return($page->ui->get_cache($cacheURL));
}//end get_page_title()
//=============================================================================



//=============================================================================
/**
 * Send an email to a single address with no special parsing.
 */
function send_single_email($toAddr, $subject, $body) {
		
	//in order to parse special BBCode, gotta create a lot of objects.
	$db = new cs_phpDB;
	$db->connect(get_config_db_params());
	$proj = new projectClass($db);
	$help = new helpdeskClass($db);
	$bbCodeParser = new bbCodeParser($proj, $help);
	
	$mail = new PHPMailer();
	$mail->SetLanguage("en");
	$mail->IsSendmail();
	
	$methodName = PHPMAILER_METHOD;
	$mail->$methodName();
	if(strlen(PHPMAILER_HOST)) {
		$mail->Host = PHPMAILER_HOST;
	}
	$mail->From = "cs-project__DO_NOT_REPLY@". PROJECT_URL;
	$mail->FromName = PROJ_NAME ." Notice";
	$mail->AddAddress($toAddr);
	$mail->ContentType = "text/html";
	$mail->Subject = $subject;
	$mail->Body = $bbCodeParser->parseString($body);
	
	$logsObj = new logsClass($db, 'Email');
	
	if(!$mail->Send()) {
		$details = __FUNCTION__ .": Message to (". $toAddr .") could not be sent::: ". $mail->ErrorInfo;
		$logsObj->log_by_class($details, 'error');
		throw new exception($details);
	}
	else {
		$logsObj->log_by_class('Successfully sent email to ('. $toAddr .'): '. $subject, 'information');
	}
	return($toAddr);
}//end send_single_email()
//=============================================================================
	

?>
