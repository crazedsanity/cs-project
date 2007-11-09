


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
	
	
	
	if(document.getElementById(inputDivName)) {
		//make the text disappear.
		toggleDisplay(textDivName, 'inline');
	}
	
	if(document.getElementById(inputDivName)) {
		//make the input div appear.
		//toggleDisplay(inputDivName, 'inline');
		new Effect.Appear(inputDivName);
	}
	
	//now enable the input.
	if(document.getElementById(inputObjName) != null) {
		var inputObj = document.getElementById(inputObjName);
		inputObj.disabled = false;
		inputObj.style.display = 'inline';
	}
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


function cs_addAttribute(selectObj) {
	if(selectObj != null && selectObj.selectedIndex > 0) {
		//okay, get the value...
		var myValue = selectObj.options[selectObj.selectedIndex].value;
		var valueInputObj = document.getElementById('addAttribute_value');
		
		if(myValue.length > 0) {
			//okay...
			if(myValue == '**new**') {
				toggleDisplay('addAttribute_select', 'inline');
				toggleDisplay('addAttribute_new', 'inline');
				document.getElementById('addAttribute_list').disabled=true;
				document.getElementById('addAttribute_new_input').disabled=false;
			}
			else {
				document.getElementById('addAttribute_new_input').value = myValue;
				document.getElementById('addAttribute_new_input').disabled=false;
			}
			valueInputObj.disabled = false;
			cs_enableSubmitButton();
		}
		else {
			valueInputObj.disabled = true;
		}
	}
}//end cs_addAttribute()


function cs_contactDelAttrib(checkBoxObj) {
	if(checkBoxObj != null) {
		var myName = checkBoxObj.value;
		var inputName = 'editAttribute_' + myName;
		var enableInputObj = document.getElementById(inputName);
		
		if(enableInputObj != null) {
			cs_enableSubmitButton();
			enableInputObj.disabled = true;
		}
		else {
			alert("Cannot find input with id=(" + inputName + ")");
		}
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
	cs_enableSubmitButton();
}//end cs_contactEdit()


function cs_enableSubmitButton(buttonName, disVal) {
	if(buttonName != null) {
		var buttonObj = document.getElementById(buttonName);
	}
	else {
		var buttonObj = document.getElementById('submitButton');
	}
	
	if(disVal == null || (disVal != true && disVal != false)) {
		disVal = false;
	}
	
	if(buttonObj != null && buttonObj.type == 'submit') {
		buttonObj.disabled = disVal;
	}
}//end cs_enableSubmitButton()


function cs_attributeEdit(myName) {
	var linkDivObj		= document.getElementById('link_editAttribute_' + myName);
	var inputDivObj		= document.getElementById('input_editAttribute_' + myName);
	var inputObj		= document.getElementById('editAttribute_' + myName);
	
	if(linkDivObj != null && inputDivObj != null && inputObj != null) {
		linkDivObj.style.display = 'none';
		inputDivObj.style.display = 'inline';
		inputObj.disabled = false;
		cs_enableSubmitButton();
	}
	
}//end cs_attributeEdit()


function cs_setContactEmailId(newValue) {
	var inputObj	= document.getElementById('contactData_email');
	
	if(inputObj != null && newValue != null) {
		inputObj.value = newValue;
		
		//reset the old fontWeight...
		myObj = null;
		var checkBoxes = updateContactForm.garbage_contactEmailId;
		for(counter = 0; counter < checkBoxes.length; counter++) {
			curValue = checkBoxes[counter].value;
			myName = 'display_ceid_' + curValue;
			myObj = document.getElementById(myName);
			
			if(myObj != null) {
				if(curValue == newValue) {
					myObj.style.fontWeight = 'bold';
				}
				else {
					myObj.style.fontWeight = 'normal';
				}
			}
		}
	}
}//end cs_setContactEmailId()


function cs_contactAddEmail(obj) {
	var newEmailObj = document.getElementById('contactData_newContactEmail');
	
	newEmailObj.disabled = false;

	if(obj != null) {
		//passed the object: they want the value updated.
		newEmailObj.value = obj.value;
		cs_setContactEmailId('new');
	}
	else {
		var linkDivObj		= document.getElementById('contactAddEmail_link');
		var radioDivObj		= document.getElementById('contactAddEmail_radioDiv');
		var radioInputObj	= document.getElementById('contactAddEmail_radio');
		var textDivObj		= document.getElementById('contactAddEmail_text');
		var textInputObj	= document.getElementById('contactAddEmail_input');
		
		linkDivObj.style.display = 'none';
		radioDivObj.style.display = 'inline';
		textDivObj.style.display = 'inline';
		
		textInputObj.disabled = false;
		radioInputObj.disabled = false;
		radioInputObj.checked = true;
	}
	
	
}//end cs_contactAddEmail()


/**
 * The data inside the given div is replaced...
 */
function cs_submitButton_processing(buttonDivName) {
	if(document.getElementById(buttonDivName)) {
		var buttonObj = document.getElementById(buttonDivName);
		
		//remove the data...
		buttonObj.innerHTML = "<img src='/images/processing.gif' border='0'>";
	}
	else {
		alert("can't find " + buttonDivName + "!");
	}
}//end cs_submitButton_processing()

