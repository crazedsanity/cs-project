<?php

//Email To Fax Gateway Class
include_once("class.html.mime.mail.inc");
include_once("class.smtp.inc");

define('CRLF', "\r\n", TRUE);

class emailFax {
	//error_reporting(E_ALL);
	var $mail;  		// The Mail object
	var $background;	// Room for a image
	var $attachment;	// The attachment to a mail
	var $text;		// The text part of an email
	var $html;		// The html part of an email
	var $to;		// Who the email is to
	var $from;		// Who the email is from -- Reg Email Only
	var $fname;		// The first name of the sender -- Fax only
	var $lname;		// The last name of the sender -- Fax only
	var $poc;		// The poc of the company receiving and rfq -- Fax only
	var $stamp;		// The email to fax gateway stamp -- Fax only
	var $to_header_address; // The TO: in the email header
	var $fr_header_address; // The From: in the email header
	var $sender_header_address; //The mailbox who's sending this message
	var $smtp;		// The SMTP connection to MA
	var $smtp_params;	// The SMTP parameters used for the email Header
	var $params;		// The SMTP parameters used for connecting to the mail server
	var $mailType;		// The type of mail object (FAX || Reg. Email)
	var $subject;		// The subject of the email -- Reg Email Only
	
	// The fabulous constructor
	function emailFax($attachment = NULL,$html_file = NULL,$text_file = NULL,$type = NULL,$plain = NULL,$gold = NULL){
		$rfc_date = date("r");
     	$this->mail = new html_mime_mail(array('X-Mailer:Project Mailer',"Date: $rfc_date"));

		if($attachment){
			// Try to fetch the attachment
			$attachRes = $this->attachment = $this->mail->get_file($attachment);
			
			if(!$attachRes && strlen($attachment) > 10) {
				//If we fail to attach and we have some length to the attachment, assume it's content
				$this->attachment = $attachment;
				$fileName = "attachment.txt";
			} else {
				//get the file name
				$fileName = basename($attachment);
			}

			// Add the attachment to the mail object 
     		$this->mail->add_attachment($this->attachment, $fileName, "text/html");
		}
		// Stupid Stupid Goldmine emails
		if($gold){
			//echo "GOLD\n";
			$this->mail->add_gold($text_file);
		}
		// Straight Text Emails
		if($text_file && !$gold){
			// Fetch the text file (aka fax cover sheet)
			if(!$html_file){
			//echo "TEXT :: !HTML\n";
				if($type){
					//echo " TYPE\n";
					$this->text = $text_file;
					//echo "<HR>T: $this->text<HR>";
					$this->mail->add_text($this->text);
				}else{
					//echo " ! TYPE\n";
					$this->text = $this->mail->get_file($text_file);
					$this->mail->add_text($this->text);
				}
			}
		}
		// Pretty HTML emails, Pretty.
		if($html_file){
			// Fetch the HTML file (for multi-part mime email)
			//$this->mail->add_html($this->text);
			//echo "HTML\n";
			if($plain){
				//echo "PLAIN\n";
				$this->html = $html_file;
				if($type){
					//echo "TYPE\n";
					$this->mailType = $type;
        				$this->mail->add_html($this->html,NULL,NULL,$this->mailType);
					//echo "<HR>H 1<HR>";
				} else {
					//echo "! TYPE\n";
        				$this->mail->add_html($this->html);
					//$this->mail->add_text($text_file);
					//echo "<HR>H 2 $text_file<HR>";
				}
			} else {
				//echo "! PLAIN\n";
				$this->html = $this->mail->get_file($html_file);
				if($type){
					//echo "TYPE\n";
					$this->mailType = $type;
        				$this->mail->add_html($this->html,NULL,NULL,$this->mailType);
					//echo "<HR>H 3<HR>";
				} else {
					//echo "! TYPE\n";
        				$this->mail->add_html($this->html);
					//echo "<HR>H 4<HR>";
				}
				if($text_file){
					//echo "TEXT && TYPE\n";
					$this->text = $this->mail->get_file($text_file);
					$this->mail->add_text($this->text);
				}
			}
		}
		//echo "<HR>$this->html<HR>";
		//echo "<HR>$this->text<HR>";
	
		/* 
			Stuff that Is implemented but not being used!
			// Read the image background.gif into $background
        		// $background = $mail->get_file('background.gif');
        		// Add the text, html and embedded images. The name of the image should match exactly
			// (case-sensitive) to the name in the html.
        		// $mail->add_html_image($background, 'background.gif', 'image/gif');
		*/

		// Builds the Message
		//We needed to comment this out in case we want to add a manual attachment.
		#if(!$this->mail->build_message())
      #		die('Failed to build email');
	}
	
	
	function add_manual_attachment($attachment, $fileName="attachment.txt", $type="text/html") {
		///////////////////////////////////////////////////////////////////
		// Manually adds an attachment using the given string.
		//
		// INPUTS:::
		//    attachment    (str) Attachment contents.
		//    fileName      (str,optional) Filename to use for attachment.
		//    type          (str,optional) Content-type of attachment.
		// OUTPUTS:::
		//    <none>
		///////////////////////////////////////////////////////////////////
		
		$this->attachment = $attachment;
		// Add the attachment to the mail object 
     	$this->mail->add_attachment($this->attachment, $fileName, $type);

	}//end add_manual_attachment()


	function sendMail($to,$from,$subject,$use_reply_to=0) {
		
		if((!$this->mail->built_message) AND (!$this->mail->build_message()))
    		die('Failed to build email -- sendMail');

		// Set up the SMTP parameters
		$this->params = array(
			'host' => CONFIG_EMAIL_SERVER_IP,      // Mail server address
			'port' => 25,           // Mail server port
			'helo' => CONFIG_EMAIL_DOMAIN,    // Use your domain here.
			'auth' => FALSE,        // Whether to use authentication or not.
			'user' => '',           // Authentication username
			'pass' => ''            // Authentication password
		);

		//Set up the fields that don't change if w're reply-toing or not
		$this->to = $to;
		$this->to_header_address = 'To: ' . $this->to;
		$this->subject = 'Subject: ' . $subject;

		//CHECK THIS FOR ERRORS!!
		$this->smtp =& smtp::connect($this->params);

		if (!$use_reply_to)
		{
			//Send it as the to and from address, exactly.
			$this->from = $from;
			$this->fr_header_address = 'From: ' . $this->from;
			$this->send_params = array(
					'from'       => $this->from,
					'recipients' => $this->to,
					'headers'    => array($this->fr_header_address,$this->to_header_address,$this->subject));
		}
		else
		{
			//We're sending rfqs or something from us between two parties. Set the from as us... 
			$this->from=$from;
			$this->sender_header_address = "Sender: ". CONFIG_EMAIL_SENDER_ADDRESS;
			$this->reply_to=$from;
			$this->fr_header_address = 'From: '. $from;
			$this->reply_to_header_address = 'Reply-To: ' . $this->reply_to;
	
			$this->send_params = array(
				'from'       => $this->reply_to,
				'recipients' => $this->to,
				'headers'    => array($this->fr_header_address,$this->to_header_address,$this->reply_to_header_address,$this->sender_header_address,$this->subject));
		}
		if(!$this->mail->smtp_send($this->smtp,$this->send_params))
		{
			return 0;
			//Error
		
			//Removed the line below because it will freak out and print Array if sendMail errors. Should tell the person
			//Registering that there is a problem, not just blank page with Array printed on it.
			//	die($this->smtp->errors);
		}
		else
		{
			return 1;
			//Success!!
		}	
	}

	function viewMail(){
		echo '<PRE>'.htmlentities($this->mail->get_rfc822('',$this->to,'',$this->from,$this->subject)).'</PRE>';
	}
}
?>
