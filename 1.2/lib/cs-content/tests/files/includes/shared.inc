<?php
/*
 * Created on Oct 21, 2009
 *
 *  SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author: crazedsanity $ 
 * Current Revision:::: $Revision: 467 $ 
 * Repository Location: $HeadURL: https://cs-content.svn.sourceforge.net/svnroot/cs-content/trunk/1.0/tests/files/includes/shared.inc $ 
 * Last Updated:::::::: $Date: 2009-10-21 11:30:53 -0500 (Wed, 21 Oct 2009) $
 */


$page->gfObj->debugPrintOpt=1;
$page->allow_invalid_urls(true);
$page->printOnFinish=false;

if(isset($testObj) && is_object($testObj) && get_class($testObj) == 'TestOfCSContent') {
	
	//start unit tests!!!
	
	$testObj->assertTrue(is_array($sectionArr));
	$testObj->assertTrue(is_array($fullSectionArr));
	if(!$testObj->assertEqual(count($sectionArr), count($fullSectionArr))) {
		$testObj->assertEqual(false, true, $page->gfObj->debug_print($fullSectionArr));
	}
	
	foreach(get_defined_vars() as $n=>$v) {
		$acceptableVars = array('testObj', 'page', 'sectionArr', 'fullSectionArr', 'finalURL', 'finalSection', 'this');
		$testObj->assertTrue(in_array($n, $acceptableVars), "local var '". $n ."' not allowed as a local var");
		unset($acceptableVars);
	}
	
}
else {
	throw new exception(__FILE__ .": failed to locate unit test object (testObj) while running include script unit tests");
}


?>
