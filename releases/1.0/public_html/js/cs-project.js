


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
