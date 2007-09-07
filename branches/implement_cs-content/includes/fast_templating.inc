<?

/*This file should just be **SYMLINKED** as another filename: appropriate entries need to be made in the
	.htaccess file, to force it to be run as a PHP file.

EXAMPLE: public_html/content  ->  ./index.php
  ADD TO .htaccess:::
<files content>
	ForceType application/x-httpd-php
</files>
*/

if(!isset($mustBeLoggedIn)) {
	$mustBeLoggedIn=0;
}
$page = new GenericPage($mustBeLoggedIn);

//parse the url...
//NOTE: $_SERVER[SCRIPT_NAME] will report "/test/yermom" as "/test", since the file "/test" is actually being executed.
//NOTE: $_SERVER[PHP_SELF] will report "/test/yermom" as exactly that, even though "/test" is actually being executed.
add_templates($page,FALSE);

//before including anything, setup a special array in the session, for determining section + subsection info.
$page->ftsSections = array();
if($_SERVER['REQUEST_URI'] != "/") {
	$page->ftsSections[0] = get_base_script_name();
	$mySubsectionData = get_subsection_data(TRUE);
	if(is_array($mySubsectionData) && count($mySubsectionData) > 0) {
		$page->ftsSections = array_merge($page->ftsSections, $mySubsectionData);
	}
}

$includeScriptsArr = get_included_scripts();
$page->fts_includedScripts = array();
if(is_array($includeScriptsArr)) {
	foreach($includeScriptsArr as $includeThisScript) {
		$page->fts_includedScripts[] = $includeThisScript;
		include_once($includeThisScript);
	}
}


//========================================================================================================
/**
 * Determines what the real base script's name is: in the URL "/content/x/y", "content" would be returned.
 * 
 * @param (void)
 * 
 * @return (string)	PASS: the base script name.
 */
function get_base_script_name() {
	$retval = preg_replace('/^\//', '', $_SERVER['SCRIPT_NAME']);
	$tArr = preg_split("/[\s\.]/", $retval);
	$retval = $tArr[0];
	return($retval);
}//end get_base_script_name()
//========================================================================================================


//========================================================================================================
function get_subsection_data($returnAsArr=FALSE) {
	//get the url, so we can start stripping it down.
	$myUrl = $_SERVER['PHP_SELF'];
	
	//get rid of opening slashes...
	$myUrl = preg_replace('/^\//', '', $myUrl);
	$baseScript2Remove = get_base_script_name();
	$myUrl = preg_replace('/^'. $baseScript2Remove .'/', '', $myUrl);
	
	//once again, to make sure we don't have a beginning slash after stripping the base script off...
	$myUrl = preg_replace('/^\//', '', $myUrl);
	
	//make sure we don't have a TRAILING slash: that'll cause it to return an extra index with no value.
	$myUrl = preg_replace('/\/$/', '', $myUrl);
	
	if(strlen($myUrl) > 0) {
		$finalStr = NULL;
		$finalArr = array();
		//before going any further, fix it so something like "yermom.html/x.y/ok.you.suck.php" becomes "yermom/x/ok"
		$exploded = explode('/', $myUrl);
		$retval = NULL;
		foreach($exploded as $fixThis) {
			$tempArr = preg_split("/[\s\.]/", $fixThis);
			$fixedStr = $tempArr[0];
			$finalStr = create_list($finalStr, $fixedStr, '/');
			$finalArr[] = $fixedStr;
		}
	} else {
		$retval = $myUrl;
	}
	
	if($returnAsArr) {
		$retval = $finalArr;
	} else {
		$retval = $finalStr;
	}
	
	return($retval);
}//end get_subsection_data()
//========================================================================================================


//========================================================================================================
function add_templates(&$page,$allowBadUrls=TRUE) {
	//before going any further, see what our default list of templates is:
	//	any file in templates/ that is called "system.<tmplVar>.tmpl" will
	//	be checked: the list things for <tmplVar> will be the REQUIRED list,
	//	for which each section will be checked to see if there's something
	//	to override the default... if that made any sense.
	$fs = new fileSystemClass($GLOBALS['SITE_ROOT'] ."/templates");
	
	//get a list of templates...
	$defaultTmplList = array();
	$defaultTmplList = get_template_list($fs, "system");
	
	//for the first level of content (if url is "/test.php", I mean the "test" part), need to
	//	look for specially-named files.
	$baseScriptName = get_base_script_name();
	$dirList = $fs->ls(NULL, "d");
	
	$subsectionData = get_subsection_data();
	
	//check to see if we've got a directory for this script's templates.
	if(isset($dirList[$baseScriptName])) {
		//okay, we've got something to work with: get a list of files in here...
		$tmplList2filename = $defaultTmplList;
		
		$myInheritance = perform_template_inheritance($fs);
		$tmplList2filename = array_merge($defaultTmplList, $myInheritance);
		
	} elseif((!strlen($subsectionData)) && ($tryThisList = get_template_list($fs,get_base_script_name()))) {
		//okay, there's no subdirectory for this script, but there's templates for the page itself.
		$tmplList2filename = array_merge($defaultTmplList, $tryThisList);
	}
	
	//if a section isn't specifically overridden, use the default template.
	if(is_array($tmplList2filename) && count($tmplList2filename) > 0) {
		foreach($defaultTmplList as $tmplName=>$unneeded) {
			if(!isset($tmplList2filename[$tmplName])) {
				$tmplList2filename[$tmplName] = $defaultTmplList[$tmplName];
			}
		}
	} else {
		//no template list given... use the default.
		//TODO: if not an "index" or "/" page, throw an error.
		$tmplList2filename = $defaultTmplList;
		if(!$allowBadUrls && $baseScriptName != "index") {
			set_message_wrapper(array(
				"title"		=> "Error Loading Templates",
				"message"	=> "No templates appear to be available for the current URL.<BR>\n" .
						"Please create the appropriate files and try again.",
				"type"		=> "error"
			));
			#debug_print(get_base_script_name());
		}
	}

	//for temporary purposes, assume no special templates exist.		
	$page->autoFillArr = array();
	foreach($tmplList2filename as $tmplVar => $filename) {
		//inject some comments..
		$fileContents =  html_file_to_string($filename);
		$fileContents = "\n<!-- *** AUTO-FILL START: $filename *** -->". $fileContents 
			."<!-- *** AUTO-FILL END: $filename *** -->\n";
		$page->add_template_var($tmplVar, $fileContents);
		
		//update an internal list in GenericPage{} that explains what templates were auto-filled.
		$page->autoFillArr[$tmplVar] = $filename;
	}

}//end add_templates()
//========================================================================================================


//========================================================================================================
function get_template_list(&$fs, $usePrefix=NULL) {
	$tmplList = $fs->ls();
#debug_print($tmplList);
	if(is_null($usePrefix)) {
		$subSectionData = get_subsection_data(TRUE);
		if(is_array($subSectionData)) {
			$currentSection = array_pop($subSectionData);
		} else {
			$currentSection = get_base_script_name();
		}
	} else {
		$currentSection = $usePrefix;
	}

	$retval = array();
	if(is_array($tmplList) && count($tmplList) > 0) {
#debug_print("<b>get_template_list(): CWD=[". $fs->cwd ."]</b>");
		foreach($tmplList as $myFile => $myType) {
			if($myType == "file") {
				//so far, so good.  Split it on ".", and only care about the "system" ones.
				$myArr = explode('.', $myFile);
				$tmplType = $myArr[0];
				$tmplVar  = $myArr[1];
				$fileExt  = $myArr[2];
				
				//ensure it's an **ACTUAL** template file.
				if($fileExt == "tmpl" && $tmplType == $currentSection) {
					$myCwd = preg_replace('/^\//', '', $fs->cwd);
					$tmplFile = $myCwd ."/". $myFile;
					if(!preg_match('/^\//', $tmplFile)) {
						$tmplFile = '/'. $tmplFile;
					}
#debug_print("get_template_list(): usePrefix=[$usePrefix], currentSection=[$currentSection], tmplType=[$tmplType].. adding $tmplFile");
					$retval[$tmplVar] = $tmplFile;
				} else {
#debug_print("get_template_list(): UNSUPPORTED: fileExt=[$fileExt]... myFile=[$myFile] curSec=[$currentSection]");
				}
			}
		}
	}
	return($retval);
}//end get_template_list()
//========================================================================================================


//========================================================================================================
function perform_template_inheritance($fs) {
	//TODO: option to fail if a "content" template for the current section does not exist:
	//	I.E.: if url is /content/project/view/300, and /templates/content/project/view/300/300.content.tmpl 
	//		doesn't exist, and we're told to FAIL, no further contents are attempted to be loaded.
	//	CAVEAT: only fail completely if there isn't a "shared" content template in the directories beneath (this
	//		should probably also be an option).
	$retval = array();
	
	//get some information for later.
	$baseDir = get_base_script_name();
	$urlDir  = $baseDir ."/". get_subsection_data();
	$subSectionArr = get_subsection_data(TRUE);
	$myCount = count($subSectionArr);
	
	if($myCount >= 1) {
		//gotta CD into the proper directory...
		if($myCount == 1) {
			//they're only on a "base" script: no need to do CD'ing, other than to ensure we're in the right spot.
			#debug_print("perform_template_inheritance(): got 1: baseDir=[$baseDir], urlDir=[$urlDir]");
			$fs->cd("/". $baseDir);
			#debug_print($fs);
		}
	}
	
	//go to the deepest, darkest template directory.
	$cdCheck = $fs->cd("/". $urlDir);
	
	$finalSectionName = $subSectionArr[(count($subSectionArr) -1)];
	if(!$cdCheck) {
		array_pop($subSectionArr);
		foreach($subSectionArr as $x=>$y) {
			$tryThisDir = "/". $baseDir ."/". string_from_array($subSectionArr, NULL, "/");
			$cdCheck = $fs->cd($tryThisDir);
			if($cdCheck) {
			} else {
				array_pop($subSectionArr); 
			}
		}
	}
	
	if($cdCheck && is_array($subSectionArr) && count($subSectionArr)) {
		//this may be a bit of a misnomer, but I'd rather use a foreach() than a for() loop.
		foreach($subSectionArr as $unused) {
			//get the list of NORMAL templates
			$myTmplList =  get_template_list($fs, $finalSectionName);
			if(is_array($myTmplList) && count($myTmplList)) {
				foreach($myTmplList as $tmplType => $myFilename) {
					if(!isset($retval[$tmplType])) {
						$retval[$tmplType] = $myFilename;
					}
				}
			}
			
			//get a list of shared templates, if any, and load any that aren't already defined.
			$sharedTemplatesArr = get_template_list($fs, "shared");
			if(is_array($sharedTemplatesArr) && count($sharedTemplatesArr)) {
				foreach($sharedTemplatesArr as $tmplType => $myFilename) {
					if(!isset($retval[$tmplType])) {
						$retval[$tmplType] = $myFilename;
					}
				}
			}
			$fs->cd("../");
		}
	}
	
	return($retval);
}//end perform_template_inheritance()
//========================================================================================================



//========================================================================================================
function get_included_scripts() {
	//first, let's create the fileSystemClass{} to see what includes are available.
	$rootDir = $GLOBALS['SITE_ROOT'] ."/includes/auto";
	$fs = new fileSystemClass($rootDir);
	
	$retval = NULL;
	//ensure we've actually got an includes directory to work with, or that we're in that dir.
	if($fs->realcwd !== $rootDir) {
		//bad includes directory
		debug_print("get_included_scripts(): bad includes directory... \n" .
				"realcwd=[". $fs->realcwd ."], \n" .
				"rootDir=[$rootDir]");
		$retval = 0;
	} else {
		$cdCheck = -1;
		$baseScriptName = get_base_script_name();
		$subSectionData = get_subsection_data(TRUE);
		
		$dirInfo = $fs->ls();
		$lastDir = $baseScriptName;
		
		//check to see if we can include a base script...
		$baseIncludeScript = $baseScriptName .'.inc';
		$includeArr = array();
		if(isset($dirInfo[$baseIncludeScript]) && $dirInfo[$baseIncludeScript] == "file") {
			$includeArr[] = $fs->realcwd .'/'. $baseIncludeScript;
		}
		
		//now, if we've got an array for subSectionData, loop through it & see if we can include sub-scripts.
		if(is_array($subSectionData) && count($subSectionData) > 0) {
			foreach($subSectionData as $index=>$value) {
				$cdCheck = $fs->cd($lastDir);
				if($cdCheck) {
					//got something...
					$myScriptName = $value .'.inc';
					$dirInfo = $fs->ls();
					if(isset($dirInfo[$myScriptName]) && $dirInfo[$myScriptName] == "file") {
						//excellent: add the file to the list of inclusions.
						$myIncludedFilename = $fs->realcwd .'/'. $myScriptName;
						$includeArr[] = $myIncludedFilename;
					}
				} else {
					//got NOTHING... break the foreach.
					break;
				}
			}
		}
		if(is_array($includeArr) && count($includeArr) > 0) {
			$retval = $includeArr;
		}
	}
	
	return($retval);
}//end get_included_scripts()
//========================================================================================================

?>