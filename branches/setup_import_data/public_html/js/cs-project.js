


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
