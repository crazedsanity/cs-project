<?

/*
 * Written 2005-10-04
 * 
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 * 
 * Basically, it's a way to store things in the session in a standard way, without having to 
 * access $_SESSION directly.  Data is, by default, stored on a per-page basis, and thus the
 * data stored for one script is kept separate from another.  By changing a few minor variables
 * and method calls, data can be easily shared from one script to the next.
 * 
 * Everything is stored as indexes of an array in the session, which is normally saved under
 * the main index of "cache".  Sub-items are accessed by using directory-style urls. For instance:
 * $_SESSION['cache']['admin']['shared']['id'] would be accessed by make a call like this:
 * 		sessionClass{}->get_cache("/cache/admin/shared");
 * 
 * If the prefix "cache" is already set, one could use: 
 * 			sessionClass{}->get_cache("admin/shared/id")
 * 
 * NOTE::: the term "URL" or "path" is used in this library to reference the directory-style path used
 * to describe a location in $_SESSION: e.g. "/cache/x/y/z/myIndex" refers to $_SESSION[cache][x][y][z][myIndex].
 * 
 * 
 */ 	

class sessionCache {
	
	var $prefix		= "cache";	//the first directory to use.
	
	//======================================================================================
	/**
	 * The constructor.
	 */
	function sessionCache($prefix="cache") {
		if(!is_string($prefix)) {
			$this->prefix = "cache";
		} else {
			$this->prefix = $prefix;
		}
	}//end sessionCache()
	//======================================================================================
	
	
	//======================================================================================
	/**
	 * Takes a path & returns the appropriate index in the session.
	 * 
	 * @param $path				<str> path to the appropriate section in the session.
	 * 
	 * @return <NULL>			FAIL: unable to find requested index.
	 * @return <mixed>			PASS: this is the value of the index.
	 */
	function get_cache($path) {
		
		//get the list of indices in the session that we have to traverse.
		$myIndexList = $this->explode_path($path);
		
		$retval = $_SESSION[$myIndexList[0]];
		if(count($myIndexList) > 1) {
			unset($myIndexList[0]);
			foreach($myIndexList as $indexName) {
				if(isset($retval[$indexName])) {
					$retval = $retval[$indexName];
				} else {
					$retval = NULL;
					break;
				}
			}
		}
		
		return($retval);
		
	}//end get_cache()
	//======================================================================================
	
	
	//======================================================================================
	/**
	 * Pre-pends the internal $this->prefix onto the given path.
	 * 
	 * @param $path		<str> path to append $this->prefix onto.
	 * 
	 * @return <str>	PASS: this is the path with our prefix added.
	 */
	function fix_path($path) {
		//remove slashes at the beginning or end of the path.
		$path = preg_replace('/\/$/', '', $path);
		
		if(preg_match('/\/$/', $this->prefix)) {
			//remove the trailing slash.
			$this->prefix = preg_replace('/$\//', '', $this->prefix);
		}
		if(preg_match('/^\//', $this->prefix)) {
			$this->prefix = preg_replace('/^\//', '', $this->prefix);
		}
		$path = preg_replace('/\/$/', '', $path);
		
		//should we add our prefix?
		if(preg_match('/^\//', $path)) {
			$retval = $path;
		} else {
			$retval = $this->prefix ."/". $path;
		}
		
		//remove a trailing slash, if present, before returning.
		$retval = preg_replace('/\/$/', '', $retval);
		
		return($retval);
		
	}//end fix_path()
	//======================================================================================
	
	
	
	//======================================================================================
	/**
	 * Sets data into the given path, with options to override our internal prefix, and to
	 * force-overwrite data if it's not an array.
	 * 
	 * @param $path				<str> path to set the data into.
	 * @param $data				<mixed> what to set into the given path.
	 * @param $overridePrefix	<bool> should a beginning "/" cause the path to evaluate as
	 * 								a non-relative path?
	 * 
	 * @return 0				FAIL: old data doesn't match new data.
	 * @return 1				PASS: everything lines-up.
	 */
	function set_cache($path, $data) {
		//get the list of indices in the session that we have to traverse.
		$myIndexList = $this->explode_path($path);
		
		$initialIndex = $myIndexList[0];
		array_shift($myIndexList);
		
		//Use an internal iterator to go through the little bits of the session & set the
		//	data where it's supposed to be.
		$this->internal_iterator($_SESSION[$initialIndex], $myIndexList, $data);
		
		//now, we'll check the data we're setting with the data that's actually there..
		$realMd5 = md5(serialize($data));
		
		//now check against what's in the session...
		$checkThisMd5 = md5(serialize($this->get_cache($path)));
		
		$retval = 0;
		if($realMd5 == $checkThisMd5) {
			$retval = 1;
		}
		
		return($retval);
	}//end set_cache()
	//======================================================================================
	
	
	//======================================================================================
	/**
	 * Iterates through the session to create the values for set_cache().  This method passes
	 * AND returns the $array argument by reference.
	 * 
	 * @param &$array		<array> iterate through this.
	 * @param $indexList	<array> numbered array of keys, representing a path through the
	 * 							session to go through to set $data.
	 * @param $data			<mixed> data to set into the path referenced in $indexList.
	 * 
	 * @return <void>
	 */
	function &internal_iterator(&$array, $indexList, $data) {
		$retval = 0;
		$myIndex = $indexList[0];
		if(is_array($indexList) && count($indexList) > 1) {
				if(!is_array($array[$myIndex])) {
					$array[$myIndex] = array(); 
				}
				$array = &$array[$myIndex];
				array_shift($indexList);
				$this->internal_iterator($array, $indexList, $data);
		} elseif(is_array($indexList) && count($indexList) == 1) {
			$array[$indexList[0]] = $data;
		}
		
	}//end internal_iterator()
	//======================================================================================
	
	
	
	
	//======================================================================================
	//this will unset the final index in the $path var.  I.E. to unset $_SESSION['x']['y'], with
	//	no prefix set, call sessionCache{}->unset_cache('/x/y')
	function unset_cache($path) {
		
		//get the last index off our path.
		$tmp = explode('/', $path);
		$indexToKill = array_pop($tmp);
		$path = substr($path, 0, (strlen($path) - strlen($indexToKill)));
		$path = preg_replace('/\/$/', '', $path);
		
		//pull the appropriate bit of cache.
		$myCache = $this->get_cache($path);
		
		//now remove the requested index & re-store cache.
		unset($myCache[$indexToKill]);
		$this->set_cache($path, $myCache);
	}//end unset_cache()
	//======================================================================================
	
	
	//======================================================================================
	/**
	 * Build to emulate the functionality of the depracated userInputClass{}->magic_userInput()
	 * method.  Note that this will be stored as $this->prefix ."/". $key
	 * 
	 * @param $key				<str> Key to search for.
	 * @param $default			<mixed> Value to use by default if it is not found.
	 */
	function magic_cache($key, $default, $prefixPath=NULL, $checkOrder=NULL, $lowerIt=TRUE) {
		$checkOrderArr = array("_POST", "_GET", "_SESSION");
		if(isset($checkOrder) && strlen($checkOrder) > 3) {
			$checkOrderArr = explode(',', $checkOrder);
		}
		
		//if needs-be, set the path we'll look for/store this data in.
		$path = $key;
		if(!is_null($prefixPath) && strlen($prefixPath) > 1) {
			$prefixPath = preg_replace('/\/$/', '', $prefixPath);
			$key = preg_replace('/^\//', '', $key);
			$key = preg_replace('/\/$/', '', $key);
			$path = $prefixPath ."/". $key;
		}
		
		//alright, now let's see what we can find.
		$keepChecking = TRUE;
		foreach($checkOrderArr as $superGlobal) {
			switch($superGlobal) {
				case "_GET":
					$checkIt = $_GET[$key];
					break;
	
				case "_POST":
					$checkIt = $_POST[$key];
					break;
					
				case "_SESSION":
					$checkIt = $this->get_cache($path);
					break;
					
				default:
					//Do nothing
					break;
			}
			if(isset($checkIt)) {
				$retval = $checkIt;
				break;
			}
		}
		
		//if we've got nothing, use the default.
		if(!isset($retval)) {
			$retval = $default;
		}
		
		//put in lowercase, if required.
		if($lowerIt) {
			$retval = strtolower($retval);
		}
		
		$this->set_cache($path, $retval);
		
		return($retval);
	}//end magic_cache()
	//======================================================================================
	
	
	
	//======================================================================================
	/**
	 * Performs all the work of exploding the path and fixing it.
	 * 
	 * @param $path		<string> Path to work with.
	 * @return <array>	PASS: array contains exploded path.
	 */
	function explode_path($path) {
		$path = $this->fix_path($path);
		$retval = explode('/', $path);
		
		//if the initial index is blank, just remove it.
		if($retval[0] == '' || strlen($retval[0]) < 1) {
			//it was blank!  KILL IT!
			$checkItOut = array_shift($retval);
		}
		
		return($retval);
	}//end explode_path()
	//======================================================================================
	
}//end sessionCache

?>
