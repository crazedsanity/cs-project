<?php
/*
 * Created on Jan 29, 2009
 * 
 * FILE INFORMATION:
 * 
 * $HeadURL: https://cs-content.svn.sourceforge.net/svnroot/cs-content/trunk/1.0/abstract/cs_content.abstract.class.php $
 * $Id: cs_content.abstract.class.php 455 2009-08-28 20:21:25Z crazedsanity $
 * $LastChangedDate: 2009-08-28 15:21:25 -0500 (Fri, 28 Aug 2009) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 455 $
 */



abstract class cs_contentAbstract extends cs_versionAbstract {
	
	//-------------------------------------------------------------------------
    function __construct($makeGfObj=true) {
		$this->set_version_file_location(dirname(__FILE__) . '/../VERSION');
		$this->get_version();
		$this->get_project();
		
		if($makeGfObj === true) {
			//make a cs_globalFunctions{} object.
			$this->gfObj = new cs_globalFunctions();
		}
    }//end __construct()
	//-------------------------------------------------------------------------
	
	
	
}
?>