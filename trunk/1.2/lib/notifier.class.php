<?php

/*
 * Created on Jul 21, 2008
 * 
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */


class notifier {
	
	private $obj=NULL;
	private $userList=array();
	private $userListMethod=NULL;
	private $recordId=NULL;
	
	//=========================================================================
	public function __construct($obj, $recordId, $userListMethod=NULL) {
		if(!is_numeric($recordId) || $recordId < 1) {
			throw new exception(__METHOD__ .": invalid record id (". $recordId .")");
		}
		if(!strlen($userListMethod)) {
			$userListMethod = 'get_user_list';
		}
		$this->userListMethod = $userListMethod;
		if(is_object($obj)) {
			if(!method_exists($obj, $this->userListMethod)) {
				throw new exception(__METHOD__ .": method to pull list of users (". $this->userListMethod .") missing");
			}
			$this->obj = $obj;
		}
		else {
			throw new exception(__METHOD__ .": invalid data for object (". $obj .")");
		}
	}//end __construct()
	//=========================================================================
	
	
	//=========================================================================
	public function send_notice($subject, $bodyTemplate, array $parseArr=NULL) {
		$userListMethod = $this->userListMethod;
		$userList = $this->obj->$userListMethod($this->recordId);
		
		$retval = send_email($userList, $subject, $bodyTemplate, $parseArr);
		
		return($retval);
	}//end send_notice()
	//=========================================================================
	
}//end notifier{}

?>
