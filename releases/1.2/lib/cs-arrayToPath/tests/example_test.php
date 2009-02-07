<?php
/*
 * Created on Jan 25, 2009
 * 
 * FILE INFORMATION:
 * 
 * $HeadURL: https://cs-arraytopath.svn.sourceforge.net/svnroot/cs-arraytopath/trunk/tests/example_test.php $
 * $Id: example_test.php 23 2009-01-25 21:25:10Z crazedsanity $
 * $LastChangedDate: 2009-01-25 15:25:10 -0600 (Sun, 25 Jan 2009) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 23 $
 */


require_once(dirname(__FILE__) .'/testOfA2P.php');

$test = &new TestOfA2P();
$test->run(new HtmlReporter())
?>
