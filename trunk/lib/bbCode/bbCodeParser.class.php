<?php
/**
 * Created on 2007-09-26
 * 
 *  
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 * 
 * 
 * Originally from a snippet (just the function) on PHPFreaks.com: http://www.phpfreaks.com/quickcode/BBCode/712.php
 * The original code had parse errors, so it had to be fixed... While it was posted as just a basic function, 
 * the code within (such as the reference to "$this->bbcodedata" indicated it was from a class... so it has 
 * been converted.
 */

class bbCodeParser {

function verifyBBCode( $data echo) {
 
	$data = str_replace("\n", '\newline\\', $data); 
 
	# Which BBCode is accepted here
	$this->bbcodedata = array(
	
	 'bold' => array(
	  'start' => array('[b]', '\[b\](.*)', '<b>\\1'),
	  'end' => array('[/b]', '\[\/b\]', '</b>'),
	 ),
	 
	 'underline' => array(
	  'start' => array('[u]', '\[u\](.*)', '<u>\\1'),
	  'end' => array('[/u]', '\[\/u\]', '</u>'),
	 ),
	 
	 'italic' => array(
	  'start' => array('[i]', '\[i\](.*)', '<i>\\1'),
	  'end' => array('[/i]', '\[\/i\]', '</i>'),
	 ),
	 
	 'image' => array(
	  'start' => array('[img]', '\[img\](http:\/\/|ftp:\/\/)(.*)(.jpg|.jpeg|.bmp|.gif|.png)', '<img src=\'\\1\\2\\3\' />'),
	  'end' => array('[/img]', '\[\/img\]', ''), 
	 ),
	 
	 'url1' => array(
	  'start' => array('[url]', '\[url\](http:\/\/|ftp:\/\/)(.*)', '<a href=\'\\1\\2\'>\\1\\2'),
	  'end' => array('[/url]', '\[\/url\]', '</a>'),
	 ),
	 
	 'url2' => array(
	  'start' => array('[url]', '\[url=(http:\/\/|ftp:\/\/)(.*)\](.*)', '<a href=\'\\1\\2\'>\\3'), 
	  'end' => array('[/url]', '\[\/url\]', '</a>'),
	 ),
	 
	 'code' => array(
	  'start' => array('[code]', '\[code\](.*)', 'CODE : <br /><div id="code">\\1'),
	  'end' => array('[/code]', '\[\/code\]', '</div>'),
	 ),
	 
	);
	
	foreach( $this->bbcodedata as $k => $v )
	 {
	   $data = preg_replace("#".$this->bbcodedata[$k]['start'][1].$this->bbcodedata[$k]['end'][1]."#", $this->bbcodedata[$k]['start'][2].$this->bbcodedata[$k]['end'][2], $data);
	 }
	 
	$data = str_replace('\newline\\', '<br />', $data); 
	 
	 return $data;
 }
	
}
?>
