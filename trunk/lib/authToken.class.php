<?php
/*
 * 
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */

class authToken extends dbAbstract {
	
	
	
    //=========================================================================
    public function __construct(cs_phpDB $db) {
    	$this->db = $db;
    }//end __construct()
    //=========================================================================
    
    
    
    //=========================================================================
    /**
     * Using the checksum and the stringToHash, an md5 sum is created.  The 
     * stringToHash should be nothing more than a string that will create a 
     * unique string.
     * 
     * HASH TEMPLATE:
     *  {auth_token_id}_{$contactId}_{$checksum}_{$stringToHash}
     */
    public function create_token($contactId, $checksum, $stringToHash) {
    	
    	//get the authTokenId we'll be inserting.
    	$sql = "SELECT nextval('auth_token_table_auth_token_id_seq'::text)";
    	if($this->run_sql($sql)) {
    		$data = $this->db->farray();
    		$authTokenId = $data[0];
    		
    		$tokenValue = md5($authTokenId ."_". $contactId ."_". $checksum ."_". $stringToHash);
    		
    		$insertArr = array(
    			'auth_token_id'		=> $authTokenId,
    			'contact_id'		=> $contactId,
    			'checksum'			=> $checksum,
    			'token'				=> $tokenValue
    		);
    		
    		$sql = "INSERT INTO auth_token_table ". $this->gfObj->string_from_array($insertArr, 'insert');
    		if($this->run_sql($sql)) {
    			$retval = $authTokenId;
    		}
    		else {
    			throw new exception(__METHOD__ .": failed to insert new auth token");
    		}
    	}
    	else {
    		throw new exception(__METHOD__ .": failed to retrieve next auth_token_id");
    	}
    }//end create_token()
    //=========================================================================
}
?>