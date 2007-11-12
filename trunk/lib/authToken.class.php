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

//TODO: log everything!

class authToken extends dbAbstract {
	
	protected $gfObj;
	protected $tokenDuration = NULL;
	protected $logsObj;
	
	//=========================================================================
	public function __construct(cs_phpDB $db) {
		$this->db = $db;
		$this->gfObj = new cs_globalFunctions;
		$this->gfObj->debugPrintOpt = DEBUGPRINTOPT;
		$this->logsObj = new logsClass($this->db, 'Authentication Token');
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
			
			//set token duration if non-standard duration set...
			if(!is_null($this->tokenDuration)) {
				$insertArr['duration'] = $this->tokenDuration;
			}
			
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
		//TODO: log each destroyed token individually
		$sql = "DELETE FROM auth_token_table WHERE (creation + duration) < CURRENT_DATE;";
		$this->run_sql($sql);
		
		return($this->lastNumrows);
	}//end expire_tokens()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Determine if a token is authentic: the id is used to make the search as 
	 * fast as possible, while the hash & checksum are given to compare against.
	 * Failure results in FALSE, while success returns the contact_id for the
	 * given token.
	 * 
	 * NOTE: the calling program can leave it to this method to say if the 
	 * token is authentic, or use a checksum which can in turn be used to get 
	 * a specific contact_id; when they authenticate, the return of this 
	 * method must then match the contact_id retrieved from the checksum...
	 * 
	 * EXAMPLE:
	 * $tokenContactId = authToken::authenticate_token($tokenId, $hash, $checksum);
	 * $realContactId = contactClass::get_contact_id_from_email($checksum);
	 * if($tokenContactId == $realContactId) {
	 * 		//token is truly authentic
	 * }
	 */
	public function authenticate_token($tokenId, $hash, $checksum) {
		$retval = FALSE;
		
		//pull the record for this token.
		$sql = "SELECT at.* FROM auth_token_table AS at INNER JOIN contact_table " .
				"AS c ON (c.contact_id=at.contact_id) WHERE auth_token_id=". $tokenId ." AND " .
				"(creation + duration)::date >= CURRENT_DATE";
		if($this->run_sql($sql)) {
			if($this->lastNumrows == 1) {
				//we've got the record information.
				$record = $this->db->farray_fieldnames();
				
				if($hash == $record['token'] && $checksum == $record['checksum']) {
					$retval = $record['contact_id'];
					debug_print(__METHOD__ .": returning (". $retval .")");
				}
			}
			else {
				throw new exception(__METHOD__ .": too many tokens retrieved: database is insane!");
			}
		}
		
		return($retval);
	}//end authenticate_token()
	//=========================================================================
	
	
	
	//=========================================================================
	public function set_token_duration($string=NULL) {
		
		if(!is_null($string) && preg_match('/ /', $string)) {
			$retval = FALSE;
			
			//ensure the string is valid.
			$sql = "SELECT '". $this->gfObj->cleanString('sql') ."'::interval";
			
			//start transaction, then roll it back (just in case).
			$this->db->beginTrans(__METHOD__);
			if($this->run_sql($sql)) {
				$retval = $this->db->farray();
				$retval = $retval[0];
			}
			$this->db->rollbackTrans(__METHOD__);
			
			$this->tokenDuration = $retval;
		}
		else {
			$this->tokenDuration = NULL;
		}
		
		return($retval);
	}//end set_token_duration()
	//=========================================================================
	
	
	
	//=========================================================================
	public function destroy_token($tokenId) {
		
	}//end destroy_token()
	//=========================================================================
}
?>
