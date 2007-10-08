<?php
/*
 * Created on Sept. 12, 2007
 * 
 * SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author: crazedsanity $ 
 * Current Revision:::: $Revision: 155 $ 
 * Repository Location: $HeadURL: https://cs-content.svn.sourceforge.net/svnroot/cs-content/releases/0.9.0/cs_versionAbstract.class.php $ 
 * Last Updated:::::::: $Date: 2007-09-12 14:28:20 -0500 (Wed, 12 Sep 2007) $
 */

abstract class cs_versionAbstract {
	
	public $isTest = FALSE;
	
	abstract public function __construct();
	
	
	
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