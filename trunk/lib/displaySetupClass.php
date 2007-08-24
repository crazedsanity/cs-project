<?php

/*
 * Created on July 31, 2007
 * 
 * SVN INFORMATION:::
 * SVN Signature: $Id$
 * Last Committted Date: $Date$
 * Last Committed Path: $HeadURL$
 */

class displaySetup extends initialSetup {



	//=========================================================================
	public function __construct(cs_genericPage $page) {
		parent::__construct();
		$this->get_current_step();
		$this->pageObj = $page;
	}//end __construct();
	//=========================================================================
	
	
	
	//=========================================================================
	public function get_current_step() {
		$currentStep = $_SESSION['step'];
		
		if(is_null($currentStep)) {
			//use the first item in the step data.
			$stepKeys = array_keys($this->stepData);
			$this->gfObj->debug_print($stepKeys,1);
			$currentStep = $stepKeys[0];
			$_SESSION['step'] = $currentStep;
		}
		
		if(is_null($currentStep)) {
			throw new exception(__METHOD__ .": failed to retrieve current step info");
		}
		elseif(!is_null($currentStep) && !isset($this->stepData[$currentStep])) {
			throw new exception(__METHOD__ .": invalid step (". $currentStep .")");
		}
		else {
			if(!is_null($this->stepData[$currentStep]['result']) || $this->stepData[$currentStep]['isComplete'] !== FALSE) {
				throw new exception(__METHOD__ .": step isn't null, possibly complete (". $this->stepData[$currentStep]['result'] .")");
			}
			else {
				$retval = $currentStep;
			}
		}
		
		return($currentStep);
	}//end get_current_step()
	//=========================================================================
	
	
	
	//=========================================================================
	public function display_step() {
		$currentStep = $this->get_current_step();
		
		$myStepData = $this->stepData[$currentStep];
		
		$this->pageObj->rip_all_block_rows();
		if(isset($myStepData['requiredFields']) && is_array($myStepData['requiredFields'])) {
			$baseRow = $this->pageObj->templateRows['step_form_row'];
			foreach($myStepData['requiredFields'] as $properName=>$subArr) {
				if(!is_array($subArr) || !isset($subArr['name']) || !isset($subArr['desc'])) {
					throw new exception(__METHOD__ .": incomplete data for displaying (". $properName .") step (". $currentStep .")");
				}
				$parseArr = array(
					'properFieldName'	=> $properName,
					'fieldName'			=> $subArr['name'],
					'fieldDescription'	=> $subArr['desc'],
					'fieldValue'		=> $subArr['default']
				);
				$myRow .= $this->gfObj->mini_parser($baseRow, $parseArr, '%%', '%%');
			}
			$this->pageObj->add_template_var('step_form_row', $myRow);
		}
		unset($myStepData['requiredFields']);
	}//end display_step()
	//=========================================================================

}
?>