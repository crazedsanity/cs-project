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
 * 
 * TODO: test methods to make sure they work!
 */

class authToken extends dbAbstract {
	
	protected $gfObj;
	
	//=========================================================================
	public function __construct(cs_phpDB $db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
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
			
			$tokenValue = $this->create_hash_string($authTokenId, $contactId, $checksum, $stringToHash);
			
			$insertArr = array(
				'auth_token_id'		=> $authTokenId,
				'contact_id'		=> $contactId,
				'checksum'			=> $checksum,
				'token'				=> $tokenValue
			);
			
			$sql = "INSERT INTO auth_token_table ". $this->gfObj->string_from_array($insertArr, 'insert', NULL, 'sql');
			if($this->run_sql($sql)) {
				$retval = array(
					'id'	=> $authTokenId,
					'hash'	=> $tokenValue
				);
			}
			else {
				throw new exception(__METHOD__ .": failed to insert new auth token");
			}
		}
		else {
			throw new exception(__METHOD__ .": failed to retrieve next auth_token_id");
		}
		
		return($retval);
	}//end create_token()
	//=========================================================================
	
	
	
	//=========================================================================
	private function create_hash_string($tokenId, $contactId, $checksum, $stringToHash=NULL) {
		$retval = md5($tokenId ."_". $contactId ."_". $checksum ."_". $stringToHash);
		return($retval);
	}//end create_hash_string()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Destroy tokens that have gone past their expiration.
	 */
	public function expire_tokens() {
		$sql = "DELETE FROM auth_token_table WHERE (creation + duration) < CURRENT_DATE;";
		$this->run_sql($sql);
		
		return($this->lastNumrows);
	}//end expire_tokens()
	//=========================================================================
	
	
	
	//=========================================================================
	public function authenticate_token($tokenId, $hash, $checksum, $stringToHash=NULL) {
		$retval = FALSE;
		
		//pull the record for this token.
		$sql = "SELECT at.* FROM auth_token_table AS at INNER JOIN contact_table " .
				"AS c ON (c.contact_id=at.contact_id) WHERE token_id=". $tokenId ." AND " .
				"(creation + duration)::date >= CURRENT_DATE";
		if($this->run_sql($sql)) {
			if($this->lastNumrows == 1) {
				//we've got the record information.
				$record = $this->db->farray();
				$data = $record[0];
				
				//create a hash from the data, see if it matches the record data.
				$derivedHash = $this->create_hash_string($tokenId, $data['contact_id'], $data['checksum'], $stringToHash);
				if($derivedHash == $data['token']) {
					$retval = TRUE;
				}
			}
			else {
				throw new exception(__METHOD__ .": too many tokens retrieved: database is insane!");
			}
		}
		
		return($retval);
	}//end authenticate_token()
	//=========================================================================
}
?>
