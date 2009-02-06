<?php
/*
 * Created on Jan 29, 2009
 * 
 * FILE INFORMATION:
 * 
 * $HeadURL: https://cs-content.svn.sourceforge.net/svnroot/cs-content/trunk/1.0/abstract/cs_content.abstract.class.php $
 * $Id: cs_content.abstract.class.php 335 2009-01-29 21:25:58Z crazedsanity $
 * $LastChangedDate: 2009-01-29 15:25:58 -0600 (Thu, 29 Jan 2009) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 335 $
 */

require_once(dirname(__FILE__) ."/../../cs-versionparse/cs_version.abstract.class.php");


abstract class cs_contentAbstract extends cs_versionAbstract {
	
	//-------------------------------------------------------------------------
    function __construct($makeGfObj=true) {
		$this->set_version_file_location(dirname(__FILE__) . '/../VERSION');
		$this->get_version();
		$this->get_project();
		
		if($makeGfObj === true) {
			//make a cs_globalFunctions{} object.
			require_once(dirname(__FILE__) ."/../cs_globalFunctions.class.php");
			$this->gfObj = new cs_globalFunctions();
		}
    }//end __construct()
	//-------------------------------------------------------------------------
	
	
	
}
?>