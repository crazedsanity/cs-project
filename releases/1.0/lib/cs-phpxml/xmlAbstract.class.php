<?php
/*
 * Created on Sept. 11, 2007
 * 
 * 
 * SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author: crazedsanity $ 
 * Current Revision:::: $Revision: 35 $ 
 * Repository Location: $HeadURL: https://cs-phpxml.svn.sourceforge.net/svnroot/cs-phpxml/releases/0.5.3/xmlAbstract.class.php $ 
 * Last Updated:::::::: $Date: 2007-09-12 14:30:08 -0500 (Wed, 12 Sep 2007) $
 * 
 */


abstract class cs_xmlAbstract {
	
	public $isTest = FALSE;
	
	abstract public function __construct();
	
	//=========================================================================
	/**
	 * Returns a list delimited by the given delimiter.  Does the work of 
	 * checking if the given variable has data in it already, that needs to be 
	 * added to, vs. setting the variable with the new content.
	 */
	final public function create_list($string = NULL, $addThis = NULL, $delimiter = ", ") {
		if($string) {
			$retVal = $string . $delimiter . $addThis;
		}
		else {
			$retVal = $addThis;
		}

		return ($retVal);
	} //end create_list()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Retrieve our version string from the VERSION file.
	 */
	final public function get_version() {
		$retval = NULL;
		$versionFileLocation = dirname(__FILE__) .'/VERSION';
		if(file_exists($versionFileLocation)) {
			$myData = file($versionFileLocation);
			
			//set the logical line number that the version string is on, and 
			//	drop by one to get the corresponding array index.
			$lineOfVersion = 3;
			$arrayIndex = $lineOfVersion -1;
			
			$myVersionString = trim($myData[$arrayIndex]);
			
			if(preg_match('/^VERSION: /', $myVersionString)) {
				$retval = preg_replace('/^VERSION: /', '', $myVersionString);
			}
			else {
				throw new exception(__METHOD__ .": failed to retrieve version string");
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to retrieve version information");
		}
		
		return($retval);
	}//end get_version()
	//=========================================================================
	
	
	
	//=========================================================================
	final public function get_project() {
		$retval = NULL;
		$versionFileLocation = dirname(__FILE__) .'/VERSION';
		if(file_exists($versionFileLocation)) {
			$myData = file($versionFileLocation);
			
			//set the logical line number that the version string is on, and 
			//	drop by one to get the corresponding array index.
			$lineOfProject = 4;
			$arrayIndex = $lineOfProject -1;
			
			$myProject = trim($myData[$arrayIndex]);
			
			if(preg_match('/^PROJECT: /', $myProject)) {
				$retval = preg_replace('/^PROJECT: /', '', $myProject);
			}
			else {
				throw new exception(__METHOD__ .": failed to retrieve project string");
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to retrieve project information");
		}
		
		return($retval);
	}//end get_project()
	//=========================================================================
}
?>
