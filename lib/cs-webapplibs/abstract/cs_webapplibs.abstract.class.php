<?php
/*
 * Created on Aug 19, 2009
 *
 *  SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author: crazedsanity $ 
 * Current Revision:::: $Revision: 145 $ 
 * Repository Location: $HeadURL: https://cs-webapplibs.svn.sourceforge.net/svnroot/cs-webapplibs/trunk/0.3/abstract/cs_webapplibs.abstract.class.php $ 
 * Last Updated:::::::: $Date: 2009-08-28 15:30:54 -0500 (Fri, 28 Aug 2009) $
 */

abstract class cs_webapplibsAbstract extends cs_versionAbstract {
	
	protected $gfObj;
	
	//-------------------------------------------------------------------------
    function __construct($makeGfObj=true) {
		$this->set_version_file_location(dirname(__FILE__) . '/../VERSION');
		$this->get_version();
		$this->get_project();
		
		if($makeGfObj === true) {
			//make a cs_globalFunctions{} object.
			//TODO::: find a way to avoid h
			$this->gfObj = new cs_globalFunctions();
		}
    }//end __construct()
	//-------------------------------------------------------------------------
}

?>
