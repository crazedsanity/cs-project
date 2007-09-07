<?
/*
 * SVN INFORMATION:::
 * SVN Signature: $Id$
 * Last Committted Date: $Date$
 * Last Committed Path: $HeadURL$
 */

class fileSystemClass {

	var $root;		//actual root directory.
	var $cwd;		//current directory; relative to $this->root
	var $realcwd;		//$this->root .'/'. $this->cwd
	var $dh;		//directory handle.
	var $fh;		//file handle.
	var $filename;		//filename currently being used.

	//========================================================================================
	function fileSystemClass($rootDir=NULL, $cwd=NULL, $initialMode=NULL) {
		//set the root directory that we'll be using; this is considered just like "/" in 
		//	linux.  Directories above it are considered non-existent.
		if(strlen($rootDir) > 1 && preg_match('/\/$/', $rootDir)) {
			$rootDir = preg_replace('/\/$/', '', $rootDir);
		}
#debug_print("fileSystemClass(): rootDir: $rootDir || output of is_dir(): ". is_dir($rootDir));
		if(($rootDir) AND (is_dir($rootDir))) {
			// yup... use it.
			$this->root = $rootDir;
		} elseif(($GLOBALS['SITE_ROOT']) AND (is_dir($GLOBALS['SITE_ROOT']))) {
			//not set, but SITE_ROOT is... use it.
			$this->root = $GLOBALS['SITE_ROOT'];
		} else {
			//nothing useable... die.
			exit("UNUSEABLE ROOT: $rootDir");
		}
		
		//set the CURRENT working directory... this should be a RELATIVE path to $this->root.
		if(($cwd) AND (is_dir($rootDir .'/'. $cwd)) AND (!ereg($this->root, $cwd))) {
			//looks good.  Use it.
			$this->cwd = $cwd;
			$this->realcwd = $this->root .'/'. $cwd;
		} else {
			//no dice.  Use the root.
			$this->cwd = '/';
			$this->realcwd = $this->root ;
		}
		chdir($this->realcwd);
		
		//check for the initialMode...
		$useableModes = array('r', 'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+');
		if(($initialMode) AND (in_array($initialMode, $useableModes))) {
			//
			$this->mode = $initialMode;
		} else {
			//define the DEFAULT mode.
			$this->mode = "r+";
		}
		
		//remove trailing slashes from $this->realcwd and $this->cwd, to avoid... issues.
		$this->realcwd = preg_replace('/\/$/', '', $this->realcwd);
		$this->cwd = preg_replace('/\/$/', '', $this->cwd);
		
	}//end fileSystemClass()
	//========================================================================================
	
	
	
	//========================================================================================
	function cdup() {
		//easy way to go "up" a directory (just like doing "cd .." in linux)
		return($this->cd(".."));
		
	}//end cdup()
	//========================================================================================
	
	
	
	//========================================================================================
	function cd($newDir) {
		//////////////////////////////////////////////////////////////////////////
		// Just like the linux version of "cd", we're changing the directory 	//
		//	to look in for files.						//
		//									//
		// INPUTS:::								//
		//	newDir		(str) New directory to use, either absolute	//
		//			  (starts with '/') or relative.		//
		// OUTPUTS:::								//
		//	0		FAIL: unable to change directory.		//
		//	1		PASS: Directory changed.			//
		//////////////////////////////////////////////////////////////////////////
		
		//remove trailing slashes from $this->realcwd and $this->cwd, to avoid... issues.
		$this->realcwd = preg_replace('/\/$/', '', $this->realcwd);
		$this->cwd = preg_replace('/\/$/', '', $this->cwd);
		if(strlen($newDir) > 1 && preg_match('/\/$/', $newDir)) {
			$newDir = preg_replace('/\/$/', '', $newDir);
		}
		
		//check to see if it's a relative path or not...
		//NOTE: non-relative paths should NOT include $this->root.
		if(strlen($newDir) == 0 || is_null($newDir)) {
			//no data provided for $newDir: leave everything as it is.
			$retval = 1;
		} elseif($newDir == '/') {
			//just wanna go to the root dir.
			$this->cwd = "/";
			$this->realcwd = $this->root;
			$retval = 1;
		} elseif((preg_match('/^\//', $newDir)) AND (is_dir($this->root .'/'. $newDir))) 
		{
			//absolute path.
			$this->cwd = $newDir;
			$this->realcwd = $this->root . $newDir;
			$retval = 1;
		}
	  	elseif(is_dir($this->realcwd .'/'. $newDir)) 
		{
			//relative path.
			if((preg_match('/^\.\./', $newDir) && strlen($newDir) == 2) || (preg_match('/^\.\.\//', $newDir) && strlen($newDir) == 3)) {
				$myArr = explode('/', $this->cwd);
				$count = count($myArr);
				
				//drop the last element of the array.
				array_pop($myArr);
				$tempStr = string_from_array($myArr,NULL, "/");
				$tempStr = preg_replace('/\/$/', '', $tempStr);
				if(strlen($tempStr) == 0) {
					$tempStr = "/";
				} elseif(!preg_match('/^\//', $tempStr)) {
					$tempStr = "/". $tempStr;
				}
				$this->cwd = $tempStr;
				$this->realcwd = $this->root . $tempStr;
			} elseif(preg_match('/^\.\./', $newDir)) {
				debug_print("back a dir, plus... um, something [$newDir], is NOT supported.  Even a LITTLE.");
				$retval = 0;
			} else {
				//relative path...
				if($this->cwd == "/") {
					//we're at the allowed root: can't add a slash, or we'd get "//newDir".
					$this->cwd .= $newDir;
				} else {
					//not the first directory, so feel free to append a slash & the newDir.
					$this->cwd .= '/'. $newDir;
				}
				//now update the REAL cwd properly.
				$this->realcwd .= '/'. $newDir;
				$retval = 1;
			}
		}
	  	else
	  	{
			//bad.
			$retval = 0;
		}
		
		return($retval);
	}//end cd()
	//========================================================================================
	
	
	//========================================================================================
	function ls($filename=NULL, $args=NULL) {
		//////////////////////////////////////////////////////////////////////////
		// Yep, you guessed it.  Just like the linux version of the 'ls'
		//	command.
		//
		// INPUTS:::
		//
		
		$hidethedots = TRUE;
		$onlyShowDirs = FALSE;
		if(strlen($args) > 0) {
			#foreach($args as $myArg) {
			for($x=0; $x < strlen($args); $x++) {
				$myArg = $args[$x];
				
				switch($myArg) {
					case "a":
						//show directories that begin with "."
						$hidethedots=FALSE;
						break;
					
					case "l":
						//show perms, owners, etc.
						$showExtraInfo = TRUE;
						break;
					
					case "d":
						//this is to only show directories.
						$onlyShowDirs  = TRUE;
						break;
				}
			}
		}

		//open the directory for reading.
		$this->dh = opendir($this->realcwd);
		clearstatcache();
		if(is_string($filename))
		{
			//check to make sure the file exists.
			$tFile=$this->filename2absolute($filename);
			#debug_print($this->ls());
			if(file_exists($tFile))
			{
				//it's there... get info about it.
				$tempRealCwd = $this->realcwd;
				if(filetype($filename) == "dir") {
					$this->cd($filename);
					$retval = $this->ls(NULL,$args);
				} elseif(preg_match('/\//', $filename)) {
				} else {
				}
				#$this->cwd = $tempCurrentCwd;
				$this->cd($this->realcwd);
			}
			else
			{
				//stupid!
				$retval[$filename] = "FILE NOT FOUND.";
			}
		}
		else
		{
			while (($file = readdir($this->dh)) !== false)
			{	
				$useFileName = NULL;
				
				//get the files real location: 
				$tFile = $this->realcwd .'/'. $file;
				$tType = filetype($tFile);
				
				//see if we should hide ".", "..", and files/directories that start with "."
				if(($hidethedots) && ($file == "." || preg_match('/^\./', $file))) {
					$useFileName = 0;
				} else {
					$useFileName = 1;
				}
				
				//check to see if all they want is DIRECTORIES.
				if($onlyShowDirs && $useFileName == 1) {
					$useFileName = 0;
					if($tType == "dir") {
						$useFileName = 1;
					}
				}
				
				//do we still have a filename to use?
				if($useFileName) {
					$infoArr = NULL;
					if($showExtraInfo) {
						$infoArr = $this->get_fileinfo($tFile);
						if(!$tType)
						{
							debug_print("FILE: $tFile || TYPE: $tType || is_file(): ". is_file($tFile) ."is_dir(): ". is_dir($tFile));
							exit;
						}
						unset($tType);
						
					} else {
						$infoArr = filetype($tFile);
					}
					if(!is_null($infoArr)) {
							$retval[$file] = $infoArr;
					}
				}
			}
		}
		return($retval);
	}//end ls()
	//========================================================================================
	
	
	//========================================================================================
	function get_fileinfo($tFile) {
		//////////////////////////////////////////////////////////////////////////
		// grabs an array of information for a given file.			//
		//									//
		// INPUTS:::								//
		//	$tFile		(string) absolute path to the filename we're	//
		//			  doing checks on.				//
		// OUTPUTS:::								//
		//////////////////////////////////////////////////////////////////////////
		
		$retval = array(
			"size"		=> filesize($tFile),
			"type"		=> @filetype($tFile),
			"accessed"	=> fileatime($tFile),
			"modified"	=> filemtime($tFile),
			"owner"		=> $this->my_getuser_group(fileowner($tFile), 'uid'),
			"uid"		=> fileowner($tFile),
			"group"		=> $this->my_getuser_group(filegroup($tFile), 'gid'),
			"gid"		=> filegroup($tFile),
			"perms"		=> $this->translate_perms(fileperms($tFile))
		);
		
		return($retval);
	}//end get_fileinfo()
	//========================================================================================
	
	
	
	//========================================================================================
	function my_getuser_group($int, $type='uid') {
		//////////////////////////////////////////////////////////////////////////
		// gets the username/groupname of the given uid/gid ($int).		//
		//									//
		// INPUTS:::								//
		//	int		(int) uid/gid to check.				//
		//	type		(str) is it a "uid" or a "gid"?			//
		// OUTPUTS:::								//
		//	<string>	groupname/username.				//
		//////////////////////////////////////////////////////////////////////////
			
		if($type == 'uid') {
			$func = 'posix_getpwuid';
		} elseif($type == 'gid') {
			$func = 'posix_getgrgid';
		} else {
			$retval = $int;
		}
		$t = $func($int);
		return($t['name']);
	
	}//end my_getpwuid()
	//========================================================================================
	
	
	//========================================================================================
	function translate_perms($in_Perms) {
		//////////////////////////////////////////////////////////////////////////
		// Translates the permissions string (like "0700") into a *nix-style	//
		//	permissions string (like "rwx------").				//
		//									//
		// INPUTS:::								//
		//	in_Perms	(int) permission number string.			//
		// OUTPUTS:::								//
		//	<string>	Permissions string.				//
		//////////////////////////////////////////////////////////////////////////
		//stole this from php.net... 
		// owner
		$sP .= (($in_Perms & 0x0100) ? 'r' : '&minus;') .
			(($in_Perms & 0x0080) ? 'w' : '&minus;') .
			(($in_Perms & 0x0040) ? (($in_Perms & 0x0800) ? 's' : 'x' ) :
						(($in_Perms & 0x0800) ? 'S' : '&minus;'));
		// group
		$sP .= (($in_Perms & 0x0020) ? 'r' : '&minus;') .
			(($in_Perms & 0x0010) ? 'w' : '&minus;') .
			 (($in_Perms & 0x0008) ? (($in_Perms & 0x0400) ? 's' : 'x' ) :
						(($in_Perms & 0x0400) ? 'S' : '&minus;'));
		
		// world
		$sP .= (($in_Perms & 0x0004) ? 'r' : '&minus;') .
			(($in_Perms & 0x0002) ? 'w' : '&minus;') .
			(($in_Perms & 0x0001) ? (($in_Perms & 0x0200) ? 't' : 'x' ) :
						(($in_Perms & 0x0200) ? 'T' : '&minus;'));
		return($sP);
	}//end translate_perms()
	//========================================================================================
	
	
	//========================================================================================
	function create_file($filename) {
		//////////////////////////////////////////////////////////////////////////
		// Creates an empty file.						//
		//									//
		// INPUTS:::								//
		//	filename	(string) filename to create.			//
		// OUTPUTS:::								//
		//	0		FAIL: unable to create file.			//
		//	1		PASS: file created successfully.		//
		//////////////////////////////////////////////////////////////////////////
		
		//check to see if the file exists...
		$retval = 0;

		if(!file_exists($filename)) {
			//no file.  Create it.
			//What if touch fails?
			if (touch($this->realcwd .'/'. $filename))
			{
				$retval = 1;
			}
		}
		else
		{
			//File exists already, should we say it was created successfully?
			$retval = 0;
		}
		return($retval);
	}//end create_file()
	//========================================================================================
	
	
	
	//========================================================================================
	function openFile($filename=NULL, $mode="r+") {
		//////////////////////////////////////////////////////////////////////////
		// Opens a stream/resource handle to use for doing file i/o.		//
		//									//
		// INPUTS:::								//
		//	filename	(string) filename to open.  This should be a	//
		//			  *relative* path to the current directory.	//
		//	mode		(string) mode to use; consult PHP.net's info	//
		//			  on fopen().					//
		// OUTPUTS:::								//
		//	0		FAIL: unable to open file.			//
		//	1		PASS: file opened successfully.			//
		//////////////////////////////////////////////////////////////////////////
	
		//make sure we've got a mode to use.
		if(!$filename) {
			$filename = $this->filename;
		}
		
		//make sure the file exists...
		$this->create_file($filename);
		
		//make sure $filename is absolute...
		$filename = $this->filename2absolute($filename);
		
		if(!is_string($mode)) {
			$mode = "r+";
		}
		$this->mode = $mode;
	
		//attempt to open a stream to a file...
		$this->fh = fopen($this->filename, $this->mode);

		//Fopen should return false if it fails...Should maybe just use this functionality instead of resource check below?

		if(is_resource($this->fh)) {
			//looks like we opened successfully.
			$retval = 1;
		} else {
			//something bad happened.
			$retval = 0;
		}
		
		return($retval);
	}//end openFile()
	//========================================================================================
	
	
	//========================================================================================
	function write($content, $filename=NULL) {
		//////////////////////////////////////////////////////////////////////////
		// Write the given contents into the current file or the filename given.//
		//									//
		// INPUTS:::								//
		// 	content		(varies) Content to write into the file.	//
		//	filename	(string; optional) filename to use.  If none	//
		//			  specified, will use the current filename.	//
		// OUTPUTS:::								//
		//	0		FAIL: unable to write content to the file.
		// FALSE	FAIL: unable to use fwrite content to file
		//	<n>		PASS: written successfully; <n> is the number	//
		//			  of bytes written.				//
		//////////////////////////////////////////////////////////////////////////
		
		//open the file for writing.
		if(!$filename) {
			$filename= $this->filename;
		}
		$this->filename = $filename;
		
		//open the file...
		$openResult = $this->openFile($this->filename, $this->mode);
		
		//looks like we made it... 
		$retval = fwrite($this->fh, $content, strlen($content));
	
		//done... return the result.
		return($retval);
	}//end write()
	//========================================================================================
	
	
	//========================================================================================
	function filename2absolute($filename=NULL) {
		//////////////////////////////////////////////////////////////////////////
		// Takes the given filename & returns the ABSOLUTE pathname; checks to	//
		//	see if the given string already has the absolute path in it.	//
		//
		
		if(!$filename) {
			$filename = $this->filename;
		}
		
		//see if it starts with a "/"...
		if(preg_match("/^\//", $filename)) {
			//it's an absolute path... see if it's one we can use.
			#if() {
			#
			#} else {
			#
			#}
		} else {
			//not absolute... see if it's a valid file; if it is, return proper string.
			if(file_exists($this->realcwd .'/'. $filename)) {
				//looks good.
				$this->filename=$this->realcwd .'/'. $filename;
			} else {
				//bad filename... die.
				print "filename2absolute(): INVALID FILENAME: $filename<BR>\n
				CURRENT CWD: ". $this->cwd ."<BR>\n
				REAL CWD: ". $this->realcwd;
				debug_print($this->ls(),1);
				exit;
			}
		}
		
		return($this->filename);
		
	}//end filename2absolute()
	//========================================================================================
	
	//========================================================================================
	////////////////////////////////////////////////////////////////////////////////////////////////////
	//	takes a csv (or tab) and parses it into a two dimensional array
	//	if the isHeader is set the array is indexed as [int][columnname]
	//	INPUT:	$filename - the path of the file to be parsed
	//		$delimiter - what the file is delimited by
	//		$isHeader - a flag on whether there is a header row (defaults to true)
	//	OUTPUT: if no error (isHeader=1) - a two dimensional array indexed by the column name
	//		if no error (isHeader=0) - a two dimensional array indexed from 0-number of columns
	//		if error - A string with the error
	/////////////////////////////////////////////////////////////////////////////////////////////////////
	function parse_csv_to_array($filename,$delimiter,$isHeader=1)
	{
		//Should have a check to get the number of lines in a file, then see if count(newArr) is the same
		$file = fopen($filename,"r");
		if($file)
		{
			$count=0;
			$newArr = array();
			$myLine = null;
			if($isHeader)
			{
				//Gets the first line of the file and returns it as an array (of headers)
				$headers=fgetcsv($file,10000,$delimiter);
			}
			while(($myLine=fgetcsv($file,10000,$delimiter)) !== false )
			{
				$innerCount=0;
				foreach($myLine as $myCell)
				{
					//Loop through each column in the array (for each line of the file)
	 				if($isHeader)
					{
						$newArr[$count][$headers[$innerCount]]=$myCell;
					}
					else
					{
						$newArr[$count][$innerCount]=$myCell;
					}
					$innerCount++;
				}
				$count++;
			}
			return $newArr;
		}
		else
		{
			return "Could not open the file $filename\n";
		}
	}
	//========================================================================================

	//////////////////////////////////////////////////////////////////////////////////////////
	//	parses a USERTRAN file into a tab delimited file
	//	INPUT:	$filename - the name of the file to parse
	//	OUTPUT:	if no error - the name of the new file
	//		if error - an array with array[0] = int and array[1]=the errors description
	//////////////////////////////////////////////////////////////////////////////////////////
	function parse_USERTRAN_to_tab($filename){
		$realFileName = $this->filename2absolute($filename);
		$fileString = $this->read($realFileName);
		if ($fileString !== false)
		{
			$utf = new userTranFile($fileString);
		}
		else
		{
			//What do do here...NO error checking before except $utf->errorMsg from getFile
		}
		$newFilename="newFilename.txt";
		if($this->create_file($newFilename)){
			if($this->openfile($newFilename,"w")){
				$firstLine=1;
				while($row = $utf->farray()){
					if($firstLine){
						$names = array_keys($row);
						foreach($names as $myName){
							fwrite($this->fh,$myName."\t");
						}
						fwrite($this->fh,"\n");
						$firstLine=0;
					}
					foreach($row as $cell){
						fwrite($this->fh,$cell."\t");
					}
					fwrite($this->fh,"\n");
				}
			}
			else{
				return array(1,"Could not open the new file");
			}	
		}
		else{
			return array(0,"Could not create the new file");
		}
		return $newFilename;
	}
	//========================================================================================

	//////////////////////////////////////////////////////////////////////////////////////////
	//	parses an Excel file into a tab delimited file
	//	INPUT:	$filename - the name of the file to parse
	//	OUTPUT:	the name of the new file
	//////////////////////////////////////////////////////////////////////////////////////////
	function parse_XLS_to_tab($filename){
		$data = new Spreadsheet_Excel_Reader();
		// Set output Encoding.
		$data->setOutputEncoding('CP1251');
		$realFileName = $this->filename2absolute($filename);
		$data->read($realFileName);
		$nameParts = explode(".",$filename);
		$newFilename=$nameParts[0].".txt";
		if($this->create_file($newFilename)){
			if($this->openfile($newFilename,"w")){
				for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
					for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
						fwrite($this->fh,$data->sheets[0]['cells'][$i][$j]."\t");
					}
					fwrite($this->fh,"\n");
				}
			}
			else{
				return array(1,"Could not open the new file");
			}
		}
		else{
			return array(0,"Could not create the new file");
		}
		return $newFilename;
	}
	//========================================================================================

	function read($filename=NULL,$returnType=0)
	{
		//This function may start to use the internal $fh sometime... We'll see.

		//Return type is 0 string, 1 array, 2...other format to be decided later
		$returnValue = false;

		//Opens a file for reading and returns the filecontents as type $returnType 
		if ($filename)
		{
			$this->filename = $filename;
		}
		
		//Maybe check if file exists/string is > 0 length. Following functions should check, but they might produce errors.
		switch ($returnType)
		{
			case 0:
				$returnValue = file_get_contents($this->filename);
			break;
			
			case 1:
				$returnValue = file($this->filename);
			break;

			default:
				$returnValue = false;
			break;
		}

		//Should have the contents in the format specified, or false.
		//Can add some checks later if the array has no elements or the string is blank...
		return $returnValue;
	}
}//end filesystemClass{}
?>
