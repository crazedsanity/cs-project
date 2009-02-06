<?php
/*
 * Created on Sept. 11, 2007
 * 
 * 
 * SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author: crazedsanity $ 
 * Current Revision:::: $Revision: 63 $ 
 * Repository Location: $HeadURL: https://cs-phpxml.svn.sourceforge.net/svnroot/cs-phpxml/trunk/cs_phpxml.abstract.class.php $ 
 * Last Updated:::::::: $Date: 2009-02-03 13:04:55 -0600 (Tue, 03 Feb 2009) $
 * 
 */

require_once(dirname(__FILE__) .'/../cs-arrayToPath/cs_arrayToPath.class.php');
require_once(dirname(__FILE__) .'/../cs-versionparse/cs_version.abstract.class.php');

abstract class cs_phpxmlAbstract extends cs_versionAbstract {
	
	public $isTest = FALSE;
	protected $a2p;
	
	//=========================================================================
	public function __construct(array $data=null) {
		$this->set_version_file_location(dirname(__FILE__) . '/VERSION');
		if(!is_array($data)) {
			$data = array();
		}
		$this->a2p = new cs_arrayToPath($data);
	}//end __construct()
	//=========================================================================
	
	
	
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
	
	
	
}
?>
