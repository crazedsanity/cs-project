<?php
/*
 * Created on Jan 29, 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */


require_once(dirname(__FILE__) ."/../../public_html/xajax/xajax.inc.php");





$GLOBALS['objects'] = array();
$GLOBALS['objects']['page'] =& $page;
$GLOBALS['objects']['proj'] =& $proj;
$GLOBALS['objects']['tag'] =& $proj->tagObj;


/**
 * Handles creation of javascript functions; AJAX callback functions 
 * should start with "ajax__", and can then be referenced in the HTML 
 * with the prefix "xajax_ajax__".  If the callback function is named 
 * "ajax__function()", the javascript call would be "xajax_ajax__function()".
 * 
 * NOTE: Because PHP lowercases function names when calling get_defined_functions(),
 * javascript calls must use the lowercase version; i.e. if the callback 
 * is named "ajax__showMeStuff()", the javascript would be
 * "xajax_ajax__showmestuff()".
 */
function addAjax() {
	$xajax = new xajax($_SERVER['PHP_SELF']);
	
	//automatically register all functions starting with "ajax__"
	$funcList = get_defined_functions();
	$funcList = $funcList['user'];
	foreach($funcList as $name) {
		if(preg_match('/^ajax__/', $name)) {
			$xajax->registerFunction($name);
		}
	}
	
	$xajax->processRequests();
	
	//since Xajax prints things, capture it in a buffer so it can be added as a template var.
	ob_start();
	$xajax->printJavascript('/xajax');
	$xajaxHeaders = ob_get_contents();
	ob_end_clean();
	
	$page = $GLOBALS['objects']['page'];
	$page->add_template_var('XAJAX_HEADERS', $xajaxHeaders);
}//end addAjax()

function parse_related_todo(array $parseData) {
	$page = $GLOBALS['objects']['page'];
	$bgColor = NULL;
	$bgColor2 = NULL;
	
	$rowName = 'related_todo_row';
	$baseRow = $page->templateRows[$rowName];
	
	foreach($parseData as $id=>$arr) {
		swapValue($bgColor, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
		swapValue($bgColor2, "#d5d5d5", "#c2c2c2");
		$repArr = array(
			"id"		=> $id,
			"bgColor"	=> $bgColor,
			"bgColor2"	=> $bgColor2
		);
		$changeFontColor=TRUE;
		//Making TODO stuff look purdy.  Boy.
		if(!preg_match('/accepted/', strtolower($arr['status_text']))) {
			$repArr['bgColor'] = "e0e0e0";
			$repArr['bgColor2'] = "e0e0e0";
		}
		
		//Add dashes to fields that don't have any information in them.
		if(strlen($arr['deadline']) <6) {
			$changeFontColor = FALSE;
			$arr['deadline'] = "&#151;&#151;&#151;&#151;";
		}
		if(strlen($arr['begin_date']) <6) {
			$changeFontColor = FALSE;
			$arr['begin_date'] = "&#151;&#151;&#151;&#151;";
		}
		if(strlen($arr['assigned_user']) == 0) {
			//put some dashes in if nobody is assigned.
			$arr['assigned_user'] = "&#151;&#151;";
		} 
		
		//cross-out all items that are ended.
		if(preg_match('/ended/', strtolower($arr['status_text']))) {
			$changeFontColor = FALSE;
			$strikeThroughArr = array(
				"creator", "creator", "assigned", "status_text", "remark", "begin_date", "deadline"
			);
			foreach($strikeThroughArr as $myField) {
				$arr[$myField] = '<del>&nbsp;'. $arr[$myField] .'&nbsp;</del>';
			}
			unset($strikeThroughArr);
		}
		
		if(strlen($arr['deadline']) && preg_match('/ended/', strtolower($arr['status_text']))) {
			//make the deadline look nice.
			$today = date("Ymd");
			$myDeadline = str_replace("-", "", $arr['deadline']);
			settype($myDeadline, "int");
			settype($today, "int");
			
			if(!$changeFontColor) {
				//do nothing.
			}
			elseif($myDeadline <= $today) {
				$arr['deadline'] = '<font color="red"><b>'. $arr['deadline'] .'</b></font>';
			}
			else {
				$arr['deadline'] = '<font color="green">'. $arr['deadline'] .'</font>';
			}
		}
	
		if($arr['status_id'] == 2) {
			$repArr['font_weight'] = "bold";
		}
		else {
			$repArr['font_weight'] = "normal";
		}
					
		//put every item in our array into the replacement array.
		foreach($arr as $x=>$y) {
			$repArr[$x] = $y;
		}
		$myRow .= mini_parser($baseRow, $repArr, "%%", "%%");
	}
	$page->add_template_var($rowName, $myRow);
}//end parse_related_todo()



?>
