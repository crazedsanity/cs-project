<?php
///////////////////////
// Author: Trevin Chow
// Email: t1@mail.com
// Date: February 21, 2000
// Last Updated: August 14, 2001
//
// Description:
//  Abstracts both the php function calls and the server information to POSTGRES
//  databases.  Utilizes class variables to maintain connection information such
//  as number of rows, result id of last operation, etc.
//
// Sample Usage:
//  include("include/dblib.php");
//  $db = new phpDB();
//  $db->connect("foobar");
//  $db->exec("SELECT * from TREVIN");
//  while ($db->nextRow()) {
//          $rs = $db->fobject();
//          echo "$rs->description : $rs->color : $rs->price <br>\n";
//  }
//
// Modification History:
// Modification History:
//  - v1.04b, 08/11/2001, Trevin Chow, t1@mail.com
//
//    Fixed following bugs:
//    * added in call to moveFirst() in exec() to ensure that on subsequent calls to exec
//      we are moving row pointer to beginning again. Previously, row pointer wasn't being
//      updated.
//
//  - v1.04a, 08/09/2001, Trevin Chow, t1@mail.com
//
//        Fixed following bugs introduced in v1.03:
//        * connect() function had problem with setting password. Instead of using $this->password, was using $this->userName again for some reason.
//
//  - v1.04, 07/29/2001, Trevin Chow, t1@mail.com
//
//      Added Following function(s):
//      * currRow() - return current row
//
//  - v1.03, 05/18/2001, Lee Pang, wleepang@hotmail.com
//
//    Added the following functions:
//    * moveNext() - same as nextRow(), better syntax for VB/ASP converts like myself.
//    * movePrevious() - like nextRow(), just in the opposite direction.
//    * recordCount() - same as numRows(), better syntax for VB/ASP converts
//    * columnCount() - same as numFields, better syntax
//    * querySafe() - removes "\r" and "\n" and replaces "\'" with "'" in input query
//    * sqlSafe() - replaces "\'" with "\'\'"
//
//    Added more comprehensive error handling:
//    * internal error code $errorCode
//    * in connect()
//    * in errorMsg()
//
//    Modified following functions:
//    * connect() - generates connection string based on available data
//
//    Fixed the following bugs:
//    * Syntax error in numAffected() - if ($this->result = null) ... to if ($this->result == null) ...
///////////////////////


##
##	WARNING::: second include is setup for access from the COMMAND LINE (for scripts running in 
##		the bin directory as cronned jobs).  Removing it will cause those jobs to fail.
##
@include_once("site_config.php");

class phpDB {

	// set when connect() is called, defined in set_db_info()
	var $hostName = '';
	var $port = '';
	var $userName = '';
	var $password = '';
	var $databaseName = '';
	var $connectionID = -1;
	var $row = -1; 	  // a row counter, needed to loop through records in postgres.
	var $result = null;   // point to result set.
	var $errorCode = 0;   // internal error code
	var $last_query = 0;  // keeps track of the last query ran... for debugging purposes.
	var $inTrans = FALSE; // internally tracks whether or not we're in a transaction...

	////////////////////////////////////////////
	// Core primary connection/database function
	////////////////////////////////////////////

	    // Set appropriate parameters for database connection
	function set_db_info($DataBaseReference){
		global $PG_HOST, $PG_PORT, $PG_DBUSER, $PG_DBPASS, $PG_DBNAME, $DBREFARR;
		//do NOT use the "require_once()" or "include_once()", as they DO NOT WORK the way
		//	you would expect!!!
		require("dbConfig.php");
	}

	function disconnect()
	{
		//Disconnect from $database
		$this->close();
	}
    
    
	// connection function
	function connect($DataBaseReference="site_config"){
		if(isset($DataBaseReference)) {
		if(is_array($DataBaseReference)) {
			$required = array(
				'hostName'		=> "host",
				'port'			=> "port",
				'databaseName'	=> "dbname",
				'userName'		=> "user",
				'password'		=> "password"
			);
			foreach($required as $propertyName => $arrayIndex) {
				if(isset($DataBaseReference[$arrayIndex])) {
					$this->$propertyName = $DataBaseReference[$arrayIndex];
				}
				else {
					throw new exception(__METHOD__ .": failed to set ". $propertyName);
				}
			}
		}
		else {
			$this->set_db_info($DataBaseReference);
		}

		// build connection string based on internal settings.
		$connStr = '';
		($this->hostName != '')        ? ($connStr .= "host=" . $this->hostName . " ")            : ($connStr = $connStr);
		($this->port != '')            ? ($connStr .= "port=" . $this->port . " ")                : ($connStr = $connStr);
		($this->databaseName != '')    ? ($connStr .= "dbname=" . $this->databaseName . " ")    : ($connStr = $connStr);
		($this->userName != '')        ? ($connStr .= "user=" . $this->userName . " ")            : ($connStr = $connStr);
		($this->password != '')        ? ($connStr .= "password=" . $this->password . " ")        : ($connStr = $connStr);
		$connStr = trim($connStr);

		//start output buffer for displaying error.
		ob_start();
		$connID =pg_connect($connStr);
		$connectError = ob_get_contents();
		ob_end_clean();
			if (is_resource($connID)) {
				$this->connectionID = $connID;
				$this->exec("set datestyle='ISO'");
				return $this->connectionID;
			} else {
				// FATAL ERROR - CONNECTI0N ERROR
				$this->errorCode = -1;
				$this->connectionID = -1;
				if(is_string($connectError)) {
					//there was an error message; return it.
					return($connectError);
				} else { 
					//no error message, but still failed.
					return 0;
				}
			}
		} else {
			// FATAL ERROR - FUNCTION CALLED WITH NO PARAMETERS
			$this->connectionID = -1;
			return 0;
		}
	}



	// standard method to close connection
	function close() {
		if ($this->connectionID != "-1") {
			//$this->RollbackTrans(); // rollback transaction before closing
			$closed = pg_close($this->connectionID);
			return $closed;
		} else {
			// connection does not exist
			return null;
		}
	}

	function getHostName() {
		if($this->hostName == null) {
			//no hostname...
			return 0;
		} else {
			//got it.
			return($this->hostName);
		}
	}



	// function to execute sql queries
	function exec($query, $debug=0, $logfile="dbqueries.log"){
		$this->last_query = $query;
		$this->queryList[] = $query;
		$returnVal = false;

		if ($this->connectionID != -1) {
			$beginTime = microtime();
			$this->result = @pg_query($this->connectionID, $query);
			$endTime = microtime();
			$totalTime = $endTime - $beginTime; //Total time for this query to return

			//debug is used for logging all queries to a file... useful for easily spotting
			//	over-used queries, et
			if($debug) {
      		//log the query...
				$fp = fopen($GLOBALS['SITE_ROOT'] . "/logs/$logfile", "a");
				fwrite($fp, $GLOBALS['PHP_SELF'] . ": ".$this->databaseName." : $query - took $totalTime\n=====================================\n");
				fclose($fp);
				//done logging...
			}

			if ($this->result !== false) {
				if (eregi("^[[:space:]]*select", $query))
				{
					//If we didn't have an error and we are a select statement, move the pointer to first result
					$numRows = $this->numRows();
	    	    	if ($numRows > 0) {
						$this->moveFirst();
					}
					$returnVal = $numRows;
					
				}
				else {
					//We got something other than an update. Use numAffected
					$returnVal = $this->numAffected();
				}
			}
 		}
		return $returnVal;
	}



	function errorMsg($setMessage=NULL,$logError=NULL, $showActIdInMsg=NULL, $pre=NULL, $post=NULL) {
		//////////////////////////////////////////////////////////
		//RETURNS ANY ERROR CAUSED BY THE LAST QUERY RUN.	//
		//							//
		// INPUTS:::						//
		//	setMessage	If it's sent, and an error 	//
		//			 occurred, a message is set.	//
		//	logError	If there's an error, setting	//
		//			 this will cause it to be 	//
		//			 logged.			//
		// OUTPUTS:::						//
		//	NULL		OK: no error.			//
		//	<string>	error returned from query.	//
		//////////////////////////////////////////////////////////
		$m['title']	 = "Database Error";
		$m['type']	 = "fatal";
		$m['linkText']	 = "Contact Technical Support";
		$m['redirect']	 = "/contactus.php";
		if ($this->connectionID == "-1") {
			switch ($this->errorCode) {
				//###############################################
				case -1:
				$retVal = "FATAL ERROR - CONNECTION ERROR: RESOURCE NOT FOUND";
				break;
				//###############################################
	
				//###############################################
				case -2:
				$retVal = "FATAL ERROR - CLASS ERROR: FUNCTION CALLED WITHOUT PARAMETERS";
				break;
				//###############################################
	
				//###############################################
				default:
				$retVal = null;
				//###############################################
			}
		} else {
			$retVal = pg_last_error($this->connectionID);
		}

		//should the error be logged?
		//NOTE: if connectionID is "-1", this is NOT logged, as 
		//	there is no valid connection to the database.  
		$lastQuery = $this->last_query;
		if(($retVal) AND ($logError) AND ($this->connectionID != "-1")) {
			//if we're in a transaction, make sure we abort it.... be careful, though:
			//	if the calling script doesn't check to see if there was an error (and
			//	abort appropriately), all queries following this will NOT be in a
			//	transaction.
			$this->exec("ABORT");
			$details = $pre . $retVal . $post;
			
			$logsObj = new logsClass($this, 1);
			$tActId = $logsObj->log_dberror($details, $_SESSION['uid'], TRUE);
		}

		//check to see if a message should be set...
		if(($retVal) AND ($setMessage)) {
			$m['title']	 = "Database Error";
			$m['message']	 = "The following error occurred:<BR>\n". $pre . $retVal;
			$m['message']	.= "\n<!-- ". $lastQuery ." -->\n";
			$m['type']	 = "fatal";
			$m['linkText']	 = "Contact Technical Support";
			$m['redirect']	 = "/contactus.php";

			if(($showActIdInMsg) AND (is_numeric($tActId))) {
				$m['message'] .= "<BR><i>ACTIVITY ID: $tActId</i>";
			}
			set_message_wrapper($m);
		} 
		return($retVal);
	}




    ////////////////////
    // Cursor movement
    ////////////////////

    // move pointer to first row of result set
    function moveFirst() {
    	if ($this->result == null) return false;
		else {
			$this->setRow(0);
			return true;
		}
	}



    // move pointer to last row of result set
    function moveLast() {
        if ($this->result == null) return false;
        else {
                $this->setRow($this->numRows()-1);
                return true;
        }
    }




    // point to the next row, return false if no next row
    function moveNext() {
        // If more rows, then advance row pointer
        if ($this->row < $this->numRows()-1) {
            $this->setRow($this->row +1);
            return true;
        }
        else return false;
    }




    // point to the previous row, return false if no previous row
    function movePrevious() {
        // If not first row, then advance row pointer
        if ($this->row > 0) {
            $this->setRow($this->row -1);
            return true;
        }
        else return false;
    }




    // point to the next row, return false if no next row
    function nextRow() {
        // If more rows, then advance row pointer
        if ($this->row < $this->numRows()-1) {
                $this->setRow($this->row +1);
                return true;
        }
        else return false;
    }




    // can be used to set a pointer to a perticular row
    function setRow($row){
        $this->row = $row;
    }




    ///////////////////////
    // Result set related
    ///////////////////////
    /*
    // get length of field.
    function fieldSize()
    {
         if($this->result ==null || $this->row == "-1") return null;
         else
         {
              $this->fieldSize = @pg_fieldsize($this->result);
              return($this->fieldSize);
         }
    }*/


    // used to pull the results back
    function fobject() {
        if ($this->result == null || $this->row == "-1") return null;
        else {
                $object = pg_fetch_object($this->result,$this->row);
                return $object;
        }
    }

    // another method to obtain results
    // NOTE::: using count() on the returned array will result in a number
    //          which is TWICE what would normally be expected.
    //          REASON::: In addition to storing the data in the numeric
    //          indices of the result array, it also stores the data in
    //          associative indices, using the field names as keys.
    function farray(){
        if ($this->result == null || $this->row == "-1") return null;
        else {
                @$arr = pg_fetch_array($this->result,$this->row);
                return $arr;
        }
    }

    //Yet another method to obtain results
    //should return a single numeric indexed array
    //usefull for foreach loops
    
    function frow(){
    	if ($this->result == null || $this->row == "-1") return null;
	else {
		$arr = pg_fetch_row($this->result, $this->row);
		return $arr;
	}
    }

	function farray_fieldnames($index=NULL, $numbered=NULL,$unsetIndex=1)
	{
		//returns an array whose indexes are the fieldnames.
		
		//keep any errors/warnings from printing to the screen by using OUTPUT BUFFERS.
		ob_start();

		//before we get too far, let's make sure there's something there.
		if($this->numRows() <= 0) {
			return(0);
		}

		$x = 0;
		do
		{
			$temp = $this->farray();
			foreach($temp as $key=>$value)
			{
				//remove the numbered indexes.
				if(is_string($key)) $tArr[$key] = $value;
			}
			$newArr[$x] = $tArr;
			$x++;
		}
		while($this->nextRow());
		
		if($index) {
			foreach($newArr as $row=>$contents) { //For each of the returned sets of information
				foreach($contents as $fieldname=>$value) { //And now for each of the items in that set
					if($fieldname == $index){
						//The index for the new array will be this fieldname's value
						$arrayKey = $value;
					}

					$tempContent[$fieldname] = $value;
					//don't include the "index" field in the subarray; that always seems to end badly.
					if ($unsetIndex)
					{
						unset($tempContent[$index]);
					}
				}

				if (!isset($tempArr[$arrayKey]))	//Make sure we didn't already set this in the array. If so, then we don't have a unique variable to use for the array index. 
				{
					$tempArr[$arrayKey] = $tempContent;
				}
				else
				{
					//log the error...
					$details = "farray_fieldnames(): Non-unique index requested.<BR>\n"
						."ARGUMENTS: index=[$index], numbered=[$numbered], unsetIndex=[$unsetIndex]<BR>\n"
						."LAST QUERY: ". $this->last_query;
					throw new exception($details);
				}
				$arrayKey = NULL; //Blank this out after using it, just in case we don't find one in the next iteration
			}
			if(count($tempArr) != count($newArr)) {
				//Should log here actually. Put some information in. Why did it fail?
				throw new exception("farray_fieldnames(): ARRAYS DON'T MATCH!!!<BR>\n");
			}
			$newArr = $tempArr;
		}
		//this is where, if there's only one row (and the planets align just the way 
		//	I like them to), there's no row w/ a sub-array...  This is only done 
		//	if $index is NOT set...
		if(($this->numRows() == 1) AND (!$index) AND (!$numbered)) {
			 $newArr = $newArr[0];
		}
		$retArr = $newArr;
		ob_end_clean();
		return($retArr);
	}

	//////////////////////////////////////////////////////////////////////////////////
	//this one takes the results from a query that only returned two results and	//
	//	puts it into an array like so:						//
	//	$array[<$index value>] => <$value value>				//
	//////////////////////////////////////////////////////////////////////////////////
	function farray_nvp($index, $value) {
		if((!$index) OR (!$value)) {
			return(0);
		}
		$tArr = $this->farray_fieldnames(NULL,1);
		if(!is_array($tArr)) {
			return(0);
		}

		//loop through it & grab the proper info.
		foreach($tArr as $row=>$array) {
			$tKey = $array[$index];
			$tVal = $array[$value];
			$retArr[$tKey] = $tVal;
		}

		//return the new array.
		return($retArr);
	}//end farray_nvp()



	function farray_numbered() {
		//////////////////////////////////////////////////////////////////////////
		//returns a numbered listing of what's in the array... this ASSUMES     //
		//      that there be only one field returned in the query.             //
		//////////////////////////////////////////////////////////////////////////

		do
		{
			$temp = $this->frow();
			$retArr[] = $temp[0];
		}
		while($this->nextRow());
		return($retArr);
	}


    function numAffected() {
	//////////////////////////////////////////////////
	// RETURNS THE NUMBER OF TUPLES AFFECTED BY AN	//
	//	INSERT/DELETE/UPDATE QUERY.		//
	//////////////////////////////////////////////////
	if($this->result == null) {
		return 0;
	} else {
		$this->affectedRows = pg_affected_rows($this->result);
		return($this->affectedRows);
	}
    }

    // get the number of rows in a result
    //works just like pg_numrows().
    function numRows(){
        if ($this->result == null) return 0;
        else {
			$this->numrows = pg_num_rows($this->result);
			return $this->numrows;
        }
    }
    function affectedRows(){
	return($this->numAffected);
    }
	

    // return current row
    function currRow(){
        return $this->row;
    }

    function recordCount() {
        return $this->numRows();
    }

    // get the number of fields in a result
    function numFields() {
        if ($this->result == null) return 0;
        else return pg_num_fields ($this->result);
    }

    function columnCount() {
        return $this->numFields();
    }

    // get last OID (object identifier) of last INSERT statement
    function lastOID($doItForMe=0, $field=NULL) {
        if ($this->result == NULL) {
		return(NULL);
        } else {
		$tOid = pg_last_oid($this->result);
		$retVal = $tOid;
		if(($doItForMe) AND (eregi("^insert", $this->last_query))) {
			//attempt to parse the insert statement, then select 
			// all fields (unless $field is set) from it.
			$t = split(" into ", strtolower($this->last_query));
			$t = split(" ", $t[1]);
			$t = split("\(", $t[0]);
			$table = $t[0];

			//now we have the table. 
			if(!$field) {
				$field = "*";
			}
			$query = "SELECT $field FROM $table WHERE OID=$tOid";
			$this->exec($query);
			$dberror = $this->errorMsg(1,1,1,"lastOID(): ");

			if(!$dberror) {
				//
				$res = $this->farray();
				if(is_string($field)) {
					$retVal = $res[0];
				}
			}
		}
		return($retVal);
	}
    }

    // get result field name
    function fieldname($fieldnum) {
        if ($this->result == null) return null;
        else return pg_field_name($this->result, $fieldnum);
    }

    ////////////////////////
    // Transaction related
    ////////////////////////

    function beginTrans() {
    	$this->inTrans = TRUE;
        return @pg_query($this->connectionID, "begin");
    }

    function commitTrans() {
    	$this->inTrans = FALSE;
        return @pg_query($this->connectionID, "commit");
    }

    // returns true/false
    function rollbackTrans() {
    	$this->inTrans = FALSE;
        return @pg_query($this->connectionID, "rollback");
    }

    ////////////////////////
    // SQL String Related
    ////////////////////////
    function querySafe($string) {
		// replace \' with '
		// gets rid of evil characters that might lead to SQL injection attacks.

		// replace line-break characters
		return (cleanString($string,"query"));
	}

    function sqlSafe($string) {
        // replace \' with \'\'
        // use this function only for text fields that may contain "'" s
		return (cleanString($string,"sql"));
    }
} // end class phpDB

?>
