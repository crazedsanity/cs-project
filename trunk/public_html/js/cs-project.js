


function toggleDisplay(obj, oldDisplay) {
	var el = document.getElementById(obj);
	var newDisplay = oldDisplay;
	
	if(el.style.display != 'none') {
		el.style.display = 'none';
	} else {
		el.style.display = newDisplay;
	}
}


/**
 * Works simply by having 2 divs and an input with standardized 
 * prefixes, and having special suffixes so it's easy to call 
 * without having to pass the names of the three elements.
**/
function enableInput(prefixName) {
	//set the names of the items.
	var inputObjName = prefixName + '_input';
	var textDivName  = prefixName + '_text';
	var inputDivName = prefixName + '_inputDiv';
	
	
	//now enable the input.
	var inputObj = document.getElementById(inputObjName);
	inputObj.disabled = false;
	inputObj.style.display = 'inline';
	
	//make the text disappear.
	toggleDisplay(textDivName, 'inline');
	
	//make the input div appear.
	toggleDisplay(inputDivName, 'inline');
}

/**
 * Hide the text div & enable one of two divs.
**/
function setup__enableInput(prefixName, selectedOption) {

	//EXAMPLE: if "prefixName"=="example" and "selectedOption"=="option1"...
	
	//example_text
	var textDivName			= prefixName + '_text';
	
	//example_option1_div
	var optionDivName		= prefixName + '_' + selectedOption + '_div';
	
	//example_option1_submitButton
	var submitButtonName	= prefixName + '_' + selectedOption +'_submitButton';
	
	//example_selectedOption
	var selectedOptionName	= prefixName + '_selectedOption';
	
	//hide the text.
	var textDiv = document.getElementById(textDivName);
	textDiv.style.display = 'none';
	
	//display the option.
	var optionDiv = document.getElementById(optionDivName);
	optionDiv.style.display = 'inline';
	
	//set the value of the option input.
	var selectedOptionInput = document.getElementById(selectedOptionName);
	selectedOptionInput.value = selectedOption;
	
	//display the submit button.
	var submitButton = document.getElementById(submitButtonName);
	submitButton.style.display = 'inline';
}


function cs_addAttribute(myName) {
	var attribValObj	= document.getElementById('addAttribute_value');
	var attribNameObj	= document.getElementById('addAttribute_name');
	var newAttribObj	= document.getElementByName('addAttribute_new');
	
	alert(newAttribObj);
	
	if(myName.length > 0) {
		if(myName == '**new**') {
			attribNameObj.type = 'text';
			
			newAttribObj.type='text';
		}
		else {
			attribNameObj.innerHTML = myName;
			newAttribObj.type='HIDDEN';
			newAttribObj.value=null;
		}
		attribValObj.disabled = false;
	}
	else {
		attribValObj.disabled = true;
		attribNameObj.innerHTML = '<b>Choose one...</b>';
		newAttribObj.type="HIDDEN";
		newAttribObj.value=null;
	}
	//document.getElementById('addAttribute_value').disabled = false;
	//document.getElementById('addAttribute_name').innerHTML = myName;
}


function cs_contactDelAttrib(attribName) {
	var inputObj = document.getElementById('contactData_' + attribName);
	var checkObj = document.getElementById('contactData_del_' + attribName);
	
	if(checkObj.checked == true) {
		inputObj.disabled = true;
	}
	else {
		inputObj.disabled = false;
	}
	
}//end cs_contactDelAttrib()


function cs_contactEdit() {
	//form appears, static data disappears.
	toggleDisplay('mainContact_static', 'inline');
	toggleDisplay('mainContact_form', 'inline');
	
	//now enable some elements.
	document.getElementById('contactData_company').disabled=false;
	document.getElementById('contactData_fname').disabled=false;
	document.getElementById('contactData_lname').disabled=false;
	document.getElementById('contactData_email').disabled=false;
}//end cs_contactEdit()


