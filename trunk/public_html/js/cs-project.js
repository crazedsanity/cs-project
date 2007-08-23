


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
	var inputDiv = document.getElementById('titleInput');
	var inputObj = document.getElementById('newTitleInput');
	var editLink = document.getElementById('editTitle');
	
	inputObj.disabled = false;
	//editLink.style.display = 'none';
	inputObj.style.display = '';
	
	toggleDisplay('title_text', '');
	toggleDisplay('titleInput', 'inline');
	
}
