


function toggleDisplay(obj, oldDisplay) {
	var el = document.getElementById(obj);
	var newDisplay = oldDisplay;
	
	if(el.style.display != 'none') {
		el.style.display = 'none';
	} else {
		el.style.display = newDisplay;
	}
}


function enableTodoEditTitle() {
	//call a less-specific function to do the work.
	enableInput('title_text', 'titleInput', 'newTitleInput');
}


function enableInput(textDivName, inputDivName, inputId) {
	var inputObj = document.getElementById(inputId);
	
	//enable the input.
	inputObj.disabled = false;
	inputObj.style.display = '';
	
	//make the text disappear.
	toggleDisplay(textDivName, 'inline');
	
	//make the input div appear.
	toggleDisplay(inputDivName, 'inline');
}