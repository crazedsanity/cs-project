<?php
/*
 * Created on Jan 25, 2009
 * 
 * FILE INFORMATION:
 * 
 * $HeadURL: https://cs-versionparse.svn.sourceforge.net/svnroot/cs-versionparse/trunk/0.1/tests/example_test.php $
 * $Id: example_test.php 10 2009-01-26 05:39:00Z crazedsanity $
 * $LastChangedDate: 2009-01-25 23:39:00 -0600 (Sun, 25 Jan 2009) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 10 $
 */


require_once(dirname(__FILE__) .'/testOfCSVersionParse.php');

$test = &new TestOfA2P();
$test->run(new HtmlReporter())
?>
