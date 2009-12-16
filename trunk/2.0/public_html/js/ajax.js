//Use this for compatibility with Internet Explorer, so elements can actually be found.  Done this
//way instead of via JQuery because JQuery doesn't actually support parsing XML
//(see http://dev.jquery.com/ticket/3143)
//
//SOURCE:::
//http://groups.google.com/group/jquery-en/browse_frm/thread/95718c9aab2c7483/af37adcb54b816c3?lnk=gst&q=parsexml&pli=1
function parseXML( xml ) {
	if( window.ActiveXObject && window.GetObject ) {
		var dom = new ActiveXObject( 'Microsoft.XMLDOM' );
		dom.loadXML( xml );
		return dom;
	}
	if( window.DOMParser )
		return new DOMParser().parseFromString( xml, 'text/xml' );
	throw new Error( 'No XML parser available' );
}


function ajax_getRequest(type, isAsync, url) {
	if(isAsync != false && isAsync != true) {
		isAsync = true;
	}
	
	myUrl='/ajax/';
	if(url != undefined) {
		myUrl = url;
	}
	
	$.ajax ({
		url			: myUrl + type,
		cache		: false,
		async		: isAsync,
		dataType	: 'text/xml',
		success: function (returnXml) {
			var xml = parseXML(returnXml);
			if($(xml).find('type').text() == 'auth') {
				updateLoginBox(xml);
			}
		},
		error: function (returnXml) {
			alert("Call to " + type + " failed::: " + returnXml);
		}
	});
}



function handle_ajaxLoginResult(xml) {
	if(typeof xml == "object") {
		updateLoginBox(xml);
		
		//they're logged in.  Redirect.
		if($(xml).find('status').text() == 1 && getURLVar('loginDestination')) {
			var dest = Url.decode(getURLVar('loginDestination'));
			document.location=dest;
		}
	}
}



function ajax_successCallback(xmlData) {
	var xmlObj = parseXML(xmlData);
	var $xmlObj = $(xmlObj);
	if($xmlObj.find('callback_success').text()) {
		//call the callback function...
		var funcname = $xmlObj.find('callback_success').text();
		//TODO: figure out how to AVOID using eval() here...
		eval(funcname + '(xmlObj)');
	}
}



function ajax_doPost(formName, postData, msgTitle, msgBody, isAsync) {
	if(msgTitle != undefined && msgTitle != null && msgTitle.length) {
		$.growlUI(msgTitle, msgBody);
	}
	
	if(isAsync == undefined) {
		isAsync = true;
	}
	
	var myUrl = "/ajax/" + formName;
	
	$.ajax({
		url: myUrl,
		type: "POST",	
		data: postData,
		success: ajax_successCallback
	});
}




//Code to pull _GET vars, from http://techfeed.net/blog/index.cfm/2007/2/6/JavaScript-URL-variables
function getURLVar(urlVarName) {
	//divide the URL in half at the '?'
	var urlHalves = String(document.location).split('?');
	var urlVarValue = '';
	if(urlHalves[1]){
		//load all the name/value pairs into an array
		var urlVars = urlHalves[1].split('&');
		//loop over the list, and find the specified url variable
		for(i=0; i<=(urlVars.length); i++){
			if(urlVars[i]){
				//load the name/value pair into an array
				var urlVarPair = urlVars[i].split('=');
				if (urlVarPair[0] && urlVarPair[0] == urlVarName) {
					//I found a variable that matches, load it's value into the return variable
					urlVarValue = urlVarPair[1];
				}
			}
		}
	}
	return urlVarValue;   
}
