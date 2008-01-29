<?php
/*
 * Created on Nov 20, 2006
 * 
 * SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author: crazedsanity $ 
 * Current Revision:::: $Revision: 17 $ 
 * Repository Location: $HeadURL: https://cs-arraytopath.svn.sourceforge.net/svnroot/cs-arraytopath/releases/0.2.2/arrayToPathClass.php $ 
 * Last Updated:::::::: $Date: 2007-09-12 14:24:46 -0500 (Wed, 12 Sep 2007) $
 * 
 * 
 * Basically traverses an array as though it were a filesystem. In the given example, it looks 
 * 	more complex than necessary, but the "NEW WAY" is very programatic, whereas the "OLD WAY" is
 * 	just that: OLD.  Also, the new way is very extensible, and is handy when performing a LOT of
 * 	complex operations on an array.
 * Example:
 * 		OLD WAY:
 *	 		$my_data  = $array['path']['to']['your']['hidden']['data'];
 *			$my_vault = $array['path']['to']['your']['hidden']['vault'];
 *			$my_other = $array['path']['to']['your']['hidden']['other'];
 *
 *			$array['path']['to']['my'] = array();
 *			$array['path']['to']['my']['data'] = array();
 *			$array['path']['to']['my']['data']['is'] = "here";
 *	 	NEW WAY:
 *			$arrayToPath = new arrayToPath($array);
 *			$my_data  = $arrayToPath('/path/to/your/hidden/data');
 *			$my_vault = $arrayToPath('/path/to/your/hidden/vault');
 *			$my_other = $arrayToPath('/path/to/your/hidden/other');
 *
 *			$arrayToPath->set_data('/path/to/my/data/is', 'here');
 */ 	


require_once(dirname(__FILE__) .'/cs_versionAbstract.class.php');

class arrayToPath extends cs_a2p_versionAbstract {
	
	private $prefix		= NULL;	//the first directory to use.
	private $data;
	private $iteration = 0;
	
	//======================================================================================
	/**
	 * The constructor.
	 * 
	 * @param $array	(array) The data that will be used when 
	 * 
	 * TODO::: there is a strange recursion issue when $prefix is non-null: prefix is presently hardwired as NULL for now... 
	 */
	public function __construct($array, $prefix=NULL) {
		if($array === 'unit_test') {
			//it's a unit test.
			$this->isTest = TRUE;
		}
		else {
			$this->get_version();
			$prefix=NULL;
			if(!is_array($array)) {
				//I don't deal with non-arrays.  Idiot.
				exit('arrayToPath{}->__construct(): got an invalid datatype.');
			}
			//create a reference to the data, so if it changes, the class doesn't have to be re-initialized.
			$this->data = $array;
			
			//now set the prefix ONLY if the prefix is valid.
			if(!is_null($prefix) && strlen($prefix)) {
				//got a good prefix, so use it.
				$this->prefix = $prefix;
			}
		}
	}//end __construct()
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
	public function get_data($path=NULL) {
		$myIndexList = array();
		$path = $this->fix_path($path);
		
		if(is_null($path) || (strlen($path) < 1)) {
			//they just want ALL THE DATA.
			$retval = $this->data;
		}
		else {
			//get the list of indices in our data that we have to traverse.
			$myIndexList = $this->explode_path($path);
			
			//set an initial retval.
			$retval = $this->get_data_segment($this->data, $myIndexList[0]); 
			unset($myIndexList[0]);
			
			if(count($myIndexList) > 0) {
				foreach($myIndexList as $indexName) {
					$retval = $this->get_data_segment($retval, $indexName);
					if(is_null($retval)) {
						//hmm... well, if it's null, it's nothing which can have a sub-index.  Stop here.
						break;
					}
				}
			}
		}
		
		return($retval);
		
	}//end get_data()
	//======================================================================================
	
	
	
	//======================================================================================
	/**
	 * Returns a given index from a piece of data, used by get_data().
	 */
	private function get_data_segment($fromThis, $indexName) {
		if(is_array($fromThis)) {
			//it's an array.
			$retval = $fromThis[$indexName];
		}
		elseif(is_object($fromThis)) {
			//it's an object.
			$retval = $fromThis->$indexName;
		}
		return($retval);
	}//end get_data_segment()
	//======================================================================================
	
	
	
	//======================================================================================
	/**
	 * Pre-pends the internal $this->prefix onto the given path.
	 * 
	 * @param $path		<str> path to append $this->prefix onto.
	 * 
	 * @return <str>	PASS: this is the path with our prefix added.
	 */
	private function fix_path($path) {
		if(is_null($path) && is_null($this->prefix)) {
			//all data is null. don't bother changing it.
			$retval = NULL;
		}
		else {
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
			}
			else {
				$retval = $this->prefix ."/". $path;
			}
			
			//remove a trailing slash, if present, before returning.
			$retval = preg_replace('/\/$/', '', $retval);
		}
		
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
	 * 
	 * @return 0				FAIL: old data doesn't match new data.
	 * @return 1				PASS: everything lines-up.
	 */
	public function set_data($path, $data) {
		//get the list of indices in the session that we have to traverse.
		$myIndexList = $this->explode_path($path);
		
		$retval = 0;
		//Use an internal iterator to go through the little bits of the session & set the
		//	data where it's supposed to be.
		if($path === '/' || count($myIndexList) == 0) {
			//setting the data.
			$this->data = $data;
			$retval = 1;
		}
		elseif(count($myIndexList) == 1) {
			//that should be simple: set the index to be $data.
			$this->data[$myIndexList[0]] = $data;
			$retval = 1;
		}
		elseif(count($myIndexList) > 1) {
			$this->internal_iterator($this->data, $path, $data);
			$retval = 1;
		}
		
		return($retval);
	}//end set_data()
	//======================================================================================
	
	
	//======================================================================================
	/**
	 * Iterates through the session to create the values for set_data().  This method passes
	 * AND returns the $array argument by reference.
	 * 
	 * @param &$array		(array) iterate through this.
	 * @param $path			(array) numbered array of keys, representing a path through the
	 * 							internal data to go through to set $data.
	 * @param $data			(mixed) data to set into the path referenced in $indexList.
	 * 
	 * @return <void>
	 */
	protected function &internal_iterator(&$array, $path, $data) {
		//make sure it doesn't call itself to death.  ;) 
		$this->iteration++;
		
		if($this->iteration > 1000) {
			throw new exception("arrayToPath{}: too many iterations, path=($path)");
		}
		
		$retval = 0;
		$indexList = $this->explode_path($path);
		$myIndex = array_shift($indexList);
		$path = $this->string_from_array($indexList);

		if(is_array($array) && !strlen($path)) {
			if(isset($myIndex)) {
				//set the final piece of the array.
				$array[$myIndex] = $data;
			}
			else {
				//something is broken.
				throw new exception("arrayToPath{}->internal_iterator(): no index ($myIndex) to follow at the end of the path.");
			}
		}
		elseif(is_array($array) && strlen($path)) {
			if((count($indexList) == 0) || (is_array($indexList) && count($indexList) > 1)) {
				if(!is_array($array[$myIndex])) {
					$array[$myIndex] = array(); 
				}
				$array = &$array[$myIndex];
				$newPath = $this->string_from_array($indexList);
				
				$this->internal_iterator($array, $path, $data);
			}
			elseif(is_array($indexList) && count($indexList) == 1) {
				if(!is_array($array[$myIndex])) {
					$array[$myIndex] = array();
				}
				$array = &$array[$myIndex];
				$this->internal_iterator($array, $indexList[0], $data);
			}
			else {
				//not sure what to do but throw an exception.
				throw new exception("arrayToPath{}->internal_iterator(): unknown error ('not sure what to do'): ($array)");
			}
		}
		elseif(is_object($array)) {
			//can't handle objects...?
			throw new exception("arrayToPath{}->internal_iterator(): can't handle objects...?");
		}
		else {
			//something is... er... broken.
			throw new exception("arrayToPath{}->internal_iterator(): found unknown data type in path ($array)");
		}
		
		//decrement the iteration, so methods using it can call it multiple times without worrying about accidentally hitting the limit.
		$this->iteration--;
	}//end internal_iterator()
	//======================================================================================
	
	
	
	
	//======================================================================================
	/**
	 * Will unset the final index in the $path var.  I.E. to unset $this->array['x']['y'],
	 *	call unset_data('/x/y')
	 * 
	 * @param $path		(str) path to unset data; The last item in the path will be removed.
	 */
	public function unset_data($path) {
		//explode the path.
		$pathArr = $this->explode_path($path);
		$removeThis = array_pop($pathArr);
		$path = $this->string_from_array($pathArr);
		
		//retrieve the data...
		$myData = $this->get_data($path);
		
		if(is_array($myData)) {
			//now remove the bit of data as requested.
			unset($myData[$removeThis]);
			//update the path with our new data.
			$retval = $this->set_data($path, $myData);
		}
		else {
			//throw a terrible error.
			throw new exception("unset_data(): data ($myData) wasn't an array! ($path)");
		}
		
		return($retval);
	}//end unset_data()
	//======================================================================================
	
	
	
	//======================================================================================
	/**
	 * Performs all the work of exploding the path and fixing it.
	 * 
	 * @param $path		<string> Path to work with.
	 * @return <array>	PASS: array contains exploded path.
	 */
	public function explode_path($path) {
		$path = preg_replace('/\/\//', '/', $path);
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
	
	
	
	//======================================================================================
	public function reload_data($array) {
		//call the constructor on it, and pass along the CURRENT prefix, so it doesn't get reset.
		$this->__construct($array, $this->prefix);
	}//end reload_data()
	//======================================================================================
	
	
	
	//======================================================================================
	private function string_from_array(array $array) {
		$retval = "";
		foreach($array as $index) {
			if(strlen($retval)) {
				$retval .= "/". $index;
			}
			else {
				$retval = $index;
			}
		}
		return($retval);
	}//end string_from_array()
	//======================================================================================
	
}//end arrayToPath{}

?>
