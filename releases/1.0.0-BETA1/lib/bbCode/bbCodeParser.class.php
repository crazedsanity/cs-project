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
 * the code within (such as the reference to "$this->bbCodeData" indicated it was from a class... so it has 
 * been converted.
 */

require_once(dirname(__FILE__) .'/../cs-content/cs_bbCodeParser.class.php');

class bbCodeParser extends cs_bbCodeParser {
	
	/** Array containing all the codes & how to parse them. */
	private $bbCodeData = NULL;
	
	//=========================================================================
	/**
	 * Setup internal structures.
	 */
	function __construct(projectClass $proj, helpdeskClass $helpdesk) {
		$this->projectObj = $proj;
		$this->helpdeskObj = $helpdesk;
		
		parent::__construct();
		
		//register some extra parsing things.
		$this->register_code_with_callback('project_id', 'get_project_bbcode');
		$this->register_code_with_callback('project', 'get_project_bbcode');
		$this->register_code_with_callback('helpdesk_id', 'get_helpdesk_bbcode');
		$this->register_code_with_callback('heldpesk_id', 'get_helpdesk_bbcode');
		
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	protected function get_project_bbcode($projectId) {
		$retval = '&#91;project_id='. $projectId .'&#93;';
		if(is_numeric($projectId)) {
			try {
				$linkList = $this->projectObj->get_ancestry_link_list($projectId, TRUE, TRUE);
				$retval = '&#91;<div style="display:inline;">'. $linkList .'</div>&#93;';
			}
			catch(exception $e) {
				debug_print($e->getMessage());
			}
		}
		return($retval);
	}//end get_project_bbcode();
	//=========================================================================
	
	
	
	//=========================================================================
	protected function get_helpdesk_bbcode($helpdeskId) {
		$retval = '&#91;helpdesk_id='. $helpdeskId .'&#93;';
		if(is_numeric($helpdeskId)) {
			try {
			$data = $this->helpdeskObj->get_record($helpdeskId);
			$retval = '&#91;<a href="/content/helpdesk/view?ID='. $helpdeskId .'">'. $data['name'] .'</a>&#93;';
			}
			catch(exception $e) {
				debug_print($e->getMessage);
			}
		}
		return($retval);
	}//end get_helpdesk_bbcode()
	//=========================================================================
	
}
?>
