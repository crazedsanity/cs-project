<?php
/*
 * Created on Sept. 11, 2007
 * 
 * 
 * SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author: crazedsanity $ 
 * Current Revision:::: $Revision: 104 $ 
 * Repository Location: $HeadURL: https://cs-phpxml.svn.sourceforge.net/svnroot/cs-phpxml/trunk/1.0/cs_phpxml.abstract.class.php $ 
 * Last Updated:::::::: $Date: 2009-08-28 15:26:44 -0500 (Fri, 28 Aug 2009) $
 * 
 */


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
