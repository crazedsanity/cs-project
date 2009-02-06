<?php
/*
 * Created on Jan 25, 2009
 * 
 * FILE INFORMATION:
 * 
 * $HeadURL: https://cs-phpxml.svn.sourceforge.net/svnroot/cs-phpxml/trunk/tests/testOfCSPHPXML.php $
 * $Id: testOfCSPHPXML.php 56 2009-01-26 05:48:26Z crazedsanity $
 * $LastChangedDate: 2009-01-25 23:48:26 -0600 (Sun, 25 Jan 2009) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 56 $
 */

require_once(dirname(__FILE__) .'/../cs_phpxmlBuilder.class.php');
require_once(dirname(__FILE__) .'/../cs_phpxmlCreator.class.php');
require_once(dirname(__FILE__) .'/../cs_phpxmlParser.class.php');

class testOfCSPHPXML extends UnitTestCase {
	
	//-------------------------------------------------------------------------
	function __construct() {
		$this->gfObj = new cs_globalFunctions;
	}//end __construct()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function test_pass_data_through_all_classes() {
		
		//first, put it into cs-phpxmlParser.
		$testFile = dirname(__FILE__) .'/files/test1.xml';
		$parser = new cs_phpxmlParser(file_get_contents($testFile));
		
		//now move it into the creator.
		$creator = new cs_phpxmlCreator($parser->get_root_element());
		$creator->load_xmlparser_data($parser);
		
		//now move the data into the xmlBuilder (would be used to make the content of the XML file)
		$builder = new cs_phpxmlBuilder($creator->get_data());
		
		//okay, now let's compare it to the original contents.
		$origMd5 = md5(file_get_contents($testFile));
		$newMd5  = md5($builder->get_xml_string());
		$this->assertEqual($origMd5, $newMd5);
		
	}//end test_pass_data_through_all_classes
	//-------------------------------------------------------------------------
}

?>
