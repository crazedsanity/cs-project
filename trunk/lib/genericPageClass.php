<?php
/*
 * SVN INFORMATION:::
 * SVN Signature: $Id:genericPageClass.php 8 2007-08-23 23:22:35Z crazedsanity $
 * Last Committted Date: $Date:2007-08-23 18:22:35 -0500 (Thu, 23 Aug 2007) $
 * Last Committed Path: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/lib/genericPageClass.php $
 */
require_once("includes.php");

class GenericPage extends cs_genericPage {
	var $session;		//session_class object to manage our sessin variables
	var $db;			//db object to provide access to the database
	var $template;		//template object to parse the pages
	var $templateVars;	//our copy of the global templateVars
	var $templateFiles;	//our copy of the global templateFiles
	var $mainTemplate;	//the default layout of the site
	var $logPageView;		//determines if the page view should be logged.
	
	
	//##############################################################################################
	function GenericPage($mustBeLoggedIn=1, $logPageView=1, $mainTemplate=NULL,$minimal="0") {
		parent::__construct($mustBeLoggedIn, $mainTemplate);
		//initializes ALL of the $this vars...
		$this->logPageView = $logPageView;
		$this->minimal=$minimal;
		
		if((is_null($mainTemplate)) OR (strlen($mainTemplate) < 2)) {
			//nothing valid sent... use the default main template.
			$mainTemplate=$GLOBALS['TMPLDIR'] ."/system.main.tmpl";
		}
		$this->initialize_locals($mainTemplate, $logPageView);
		
		//banner-ads
		if(!$this->minimal){
			$this->check_login($mustBeLoggedIn,NULL);
		}
		
	}//end GenericPage constructor
	//##############################################################################################
	
	
	
	//##############################################################################################
	function initialize_locals($mainTemplate, $logPageView=1) {
		
		global $TMPLDIR,$LIBDIR;  //tell this function to use the global values of these variables
		
		parent::initialize_locals($mainTemplate);
		
		$this->template=new template("$TMPLDIR","keep"); //initialize a new template parser
		$this->db = new cs_phpDB;  				 //initialize a new database connection
		$connID = $this->db->connect(get_config_db_params());
		
		//Used to have OR (eregi("db_error.tmpl", $mainTemplate) in as well. Couldn't find any reason it needed to live (no comments)
		if((!is_resource($connID)) OR (WORKINGONIT))
		{
			//There is no database connection or we are Working on something
			$errorTmpl = "page_sections/db_error.tmpl";
			if(!file_exists("$TMPLDIR/$errorTmpl"))
			{
				//something has went HORRIBLY WRONG.
				$exitMsg  = "<b><u>Error #1:</u> Database is Unnaccessible.</b><BR>\n";
				$exitMsg .= "Cannot connect to the database.<BR><BR><BR>\n\n\n";
				$exitMsg .= "<b><u>Error #2:</u> Unable to Open Error Template.</b><BR>\n";
				$exitMsg .= "Could not open template file to display Error #1.<BR><BR><BR>\n\n\n";
				$exitMsg .= "Template file: $errorTmpl<BR>\n";
				$exitMsg .= "Please report these errors to <a href=\"mailto:fatalErrors@avsupport.com\">";
				$exitMsg .= "fatalErrors@avsupport.com</a><BR>\n";
				exit($exitMsg);
			}
			else
			{
				//Our error template doesn't even exist...What happens now?
				//the string replaces will prevent people from finding out our directory structure.
				$connID = str_replace("$LIBDIR/", "", $connID);
				$connID = str_replace("<b>Warning</b>:  pg_connect() ", "", $connID);
				$this->mainTemplate=$errorTmpl;
				$this->print_page();
				exit();
			}
		}
		else
		{
			//be sure to set the main template, or the template class will freak-out.
			$this->mainTemplate=$mainTemplate; //load the default layout
			
			if(!$this->minimal)
			{	//Do this stuff if we're not using the minimalist page style
				$this->session=new Session($this->db);		//initialize a new session object
				$this->uid = $this->session->uid;
			
				//THINGS THAT REQUIRE $this->db GO BELOW HERE!!!
				$pageData = page_get_env(TRUE,TRUE,TRUE);
				$this->session->set_last_page_viewed($pageData['current_page']);
			}
		}	
	} //end of initialize_locals()
	//##############################################################################################
		
		
	
	//##############################################################################################
	function check_login($mustBeLoggedIn,$redirectToHere="",$alternateDomain="main") {
        if ($this->session->sid_check == 1) {
           #$this->templateVars["infobar"] = html_file_to_string("page_sections/infobars/".$alternateDomain."-logout.tmpl");
        }
        else {
		#	$this->templateVars["infobar"] = html_file_to_string("page_sections/infobars/".$alternateDomain."-login.tmpl");
			
			//CHECK TO SEE IF THEY SHOULD BE LOGGED-IN.
			$tUri = $_SERVER['REQUEST_URI'];
			$doNotRedirectArr = array("/login.php", "/admin/login.php", "/admin.php");
			if(($mustBeLoggedIn) AND (!in_array($tUri, $doNotRedirectArr))) {
				set_message("Log In","You must log in to use this feature.","","status");
				//Check to see if we want to redirect somewhere other than what the user chose
				if ($redirectToHere == "")
				{
					//initialize temporary var.
					$tPage = "";
					
					foreach($_GET as $field=>$val) {
						if($field != "PHPSESSID" && ereg("$field=$val", $tPage)) {
							$tackItOn = create_list($tackItOn,"$field=$val","&");
						}
					}
					$tPage = "/login.php?destination=$tUri";
					if($tackItOn) {
						$tPage .= "?". $tackItOn;
					}
					conditional_header($tPage);
					exit;
				}
				else
				{
					//Yes we do want to redirect to an alternate page
					$curRefCount=count($_SESSION['referrers']);
					//Technically we use $curRefCount-1 because until other parts of the page load, the correct page to use is
					//in ($count-1). After this function runs, something (page generation) sets another referrer (current page)
					$pathArr=pathinfo($_SESSION['referrers'][($curRefCount-1)]['to']);
					$_SESSION['redirectToHere']=$pathArr['basename'];
					conditional_header($redirectToHere);
					exit;
				}
			}
		}
		
	} //end check_login()
	//##############################################################################################
	
	
	
	
	
	//##############################################################################################
	function print_page($stripUndefVars=1)
	{
		if(is_array($this->tabList)) {
			$this->process_tabs();
		}
		//don't try to send headers if we're in minimal or have already sent headers: the call wouldn't
		//	work and would only throw errors, anyway.
		if(!headers_sent() && !$this->minimal) {
			header("Cache-control: public");
		}
		//loads all template files into the parser,
		//loads all placeholder values, then 
		//parses each page replacing the placeholders with
		//their values.
		
		//Show any available messages.
		$this->process_set_message();
		
		parent::print_page($stripUndefVars);
		
		//log the page view.
		if(is_numeric(LOGCAT__NAVIGATION)) {
			$logCatId = LOGCAT__NAVIGATION;
		}
		else {
			throw new exception(__METHOD__ .": no log_category_id for navigation: did you complete setup?");
		}
		$logsObj = new logsClass($this->db, $logCatId);
		$logsObj->log_by_class(page_get_env(), 'information');
		
	}//end of print_page()
	//##############################################################################################
		
	
	
	//##############################################################################################
	function process_set_message() {
		//########################################################
		//  check to see if there's an error to print...
		//########################################################
		//if there's not a message set, skip.
		#return("function unavailable for now");
		if(!template_file_exists("system.message_box.tmpl")) {
			exit("AAAAHHHH!! Couldn't find the message box template!!!");
		}
		$errorBox = html_file_to_string("/system.message_box.tmpl");
		if($this->session->sid_check == "-1") {
			//need to set a message saying the session has expired.  No session is 
			//	available anymore, so we have to do this manually... GRAB THE ASTROGLIDE!!!
			$this->change_content($errorBox);
			
			//setup the message...
			$msg = "For your protection, your session has been expired.<BR>\nPlease re-login.<BR>\n";
			
			//drop all the proper variables into place.
			$this->add_template_var("title", "Session Expired");
			$this->add_template_var("message", $msg);
			$this->add_template_var("redirect", "<a href='login.php'>Solve this problem.</a>");
			$this->add_template_var("messageType", "fatal");
		} elseif(is_array($_SESSION['message'])) {
			//let's make sure the "type" value is *lowercase*.
			$_SESSION['message']['type'] = strtolower($_SESSION['message']['type']);
			
			//WARNING::: if you give it the wrong type, it'll STILL be parsed. Otherwise 
			//	this has to match set_message() FAR too closely. And it's a pain.
			if($_SESSION['message']['type'] == "fatal")
			{
				//it's bad!!! grab a vile of hemostat and some morphine, STAT!!!
				$this->change_content($errorBox);
			} else {
				
				//don't worry... it's non-fatal (heckle, heckle)
				$this->add_template_var("error_msg", $errorBox);
			}
			
			$this->add_template_var("title", $_SESSION["message"]["title"]);
			$this->add_template_var("message", $_SESSION["message"]["message"]);
			$this->add_template_var("type", $_SESSION["message"]["type"]);
			$this->add_template_var("messageType", $_SESSION["message"]["type"]);
			if(($_SESSION["message"]["redirect"]) AND (is_string($_SESSION["message"]["redirect"])))
			{
				if(!$_SESSION['message']['linkText']) {
					$_SESSION['message']['linkText'] = "Contact Us.";
				}
				$tText  = "<a href='". $_SESSION["message"]["redirect"];
				$tText .= "'>". $_SESSION['message']['linkText'] ."</a>\n";
				$this->add_template_var("redirect", $tText);
			}
			else $this->add_template_var("redirect", "");
		} 
		
		//now that we're done displaying the message, let's get it out of the session (otherwise
		//	they'll never get past this point).
		unset($_SESSION['message'], $_SESSION['messageArr']);
	}//end of process_set_message()
	//##############################################################################################
	
	
	//##############################################################################################
	function process_tabs(){
		/////////////////////////////////////////////////////////////////////////////////////
		// Takes care of parsing the tab template and turning the tabList into
		// actual tabs on a page.
		/////////////////////////////////////////////////////////////////////////////////////
		//This is gonna be messy....
		//id'd comment this better but im not even sure how it does what it does....
		
		//initialize some vars
		$row1common1 = NULL;
		$row1common2 = NULL;
		$row1grey = NULL;
		$row1blue = NULL;

		global $TMPLDIR;
		$filename=$TMPLDIR . "/system.tabs.tmpl";
		$template=fopen($filename, "r"); // open our template file
		
		//ensure our temporary vars are initialized...
		$lastRow = NULL;
		$lastSection = NULL;
		if($template){
			while (!feof ($template)){
				$temp=fgets($template,1024);
				if( preg_match("/BEGIN FIRST ROW/i",$temp)){
					$lastRow="row1";
				}
				if( preg_match("/begin common area 1/i",$temp)){
					$lastSection="common1";
				}
				if( preg_match("/begin common area 2/i",$temp)){
					$lastSection="common2";
				}
				if( preg_match("/begin blue/i",$temp)){
					$lastSection="blue";
				}
				if( preg_match("/begin grey/i",$temp)){
					$lastSection="grey";
				}
				$varName = $lastRow . $lastSection;
				${$varName} .= $temp;
			}
		} else {
			//print "cant open the file...screw you buddy<br>\n";
		}
		foreach($this->tabList as $tabsection=>$tabSectionTabs){
			$tabHTML=$row1common1;
			foreach($tabSectionTabs->tabs as $tab){
				if($tab->selected){
					$temp1=$row1grey;
					$temp1=str_replace("%%url%%",$tab->url,$temp1);
					$temp1=str_replace("%%title%%",$tab->title,$temp1);
					$tabHTML.=$temp1;
				}
				else {
					$temp1=$row1blue;
					$temp1=str_replace("%%url%%",$tab->url,$temp1);
					$temp1=str_replace("%%title%%",$tab->title,$temp1);
					$tabHTML.=$temp1;
				}
			}
			$tabHTML.=$row1common2;

			$this->add_template_var("tabs_" . $tabsection,$tabHTML);
		}
	}//end process_tabs()
	//##############################################################################################
	
	
	//##############################################################################################
	function add_tab($url,$title,$tabsection,$selected=0){
		//adds a tab into the template var
		if(is_object($this->tabList[$tabsection])){
			$this->tabList[$tabsection]->add_tab($url,$title,$selected);
			}
		else {
			$this->tabList[$tabsection]=new tabList;
			$this->tabList[$tabsection]->add_tab($url,$title,$selected);
		}
	}//end add_tab()
	//##############################################################################################
}

?>
