<?php

		//tabClass.php
		//Joe Markwardt
		//3/14/2002
		//Helper class for GenericPageClass to help it keep track of tabs
/*
 * SVN INFORMATION:::
 * SVN Signature: $Id$
 * Last Committted Date: $Date$
 * Last Committed Path: $HeadURL$
 */

class tab {
	var $url; 	//the url to put in the tab
	var $title; 	//the title to put in the tab link 
	var $selected;  //is this tab selected

	function tab($url,$title,$selected=0){
		$this->url=$url;
		$this->title=$title;
		$this->selected=$selected;
		}
	}
?>
