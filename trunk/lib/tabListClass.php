<?php
	

	//tabListClass.php
	//Joe Markwardt
	//3/14/2002
	//Helper Class for Generic Page....handle keeping track of the tabs that are on the page;
/*
 * SVN INFORMATION:::
 * SVN Signature: $Id$
 * Last Committted Date: $Date$
 * Last Committed Path: $HeadURL$
 */

	class tabList {
		var $tabs;	//list of tabs that are on this page
		var $index;	//index pointer


		function tabList(){
			$this->index=0;
			}
		function add_tab($url,$title,$selected){
			$this->tabs[$this->index++]=new tab($url,$title,$selected);
			}
		}
