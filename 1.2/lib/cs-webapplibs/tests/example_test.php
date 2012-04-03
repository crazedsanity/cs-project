<?php
/*
 * Created on Jan 25, 2009
 * 
 * FILE INFORMATION:
 * 
 * $HeadURL: https://cs-webapplibs.svn.sourceforge.net/svnroot/cs-webapplibs/trunk/0.3/tests/example_test.php $
 * $Id: example_test.php 127 2009-08-20 20:39:35Z crazedsanity $
 * $LastChangedDate: 2009-08-20 15:39:35 -0500 (Thu, 20 Aug 2009) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 127 $
 */


require_once(dirname(__FILE__) .'/testOfCSVersionParse.php');

$test = &new TestOfA2P();
$test->run(new HtmlReporter())
?>
