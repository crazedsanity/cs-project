
var ajaxLoadingImage = '<img src="/images/ajax-loader.gif" border="0"/>';


/* START AJAX TEST FUNCTIONS */
	
	//-------------------------------------------------------------------------
	function updatePageLoadData(newData) {
		if(newData.length > 0) {
			$("#pageLoadData").html(newData);
		}
		else {
			$("#pageLoadData").text("");
		}
	}//end updatePageLoadData()
	//-------------------------------------------------------------------------
	
	

	//-------------------------------------------------------------------------
	function show_ajaxLoading(myText) {
		if(!myText.length || myText == null || myText == undefined) {
			myText = "loading....";
		}
		updatePageLoadData(ajaxLoadingImage + myText);
	}//end show_ajaxLoading()
	//-------------------------------------------------------------------------
	
	

	//-------------------------------------------------------------------------
	function ajax_postTestResponse() {
		
		
		myPostData = {
			"firstItem"		: "myTest",
			"secondItem"	: $("#pageLoadData").html()
		}
		show_ajaxLoading("Checking response...");
		ajax_doPost("test", myPostData, "WAIT", "Doing a test POST via AJAX, be patient.");
		
	}//end ajax_postTestResponse()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function ajax_getTestResponse() {
		show_ajaxLoading("Sending GET request..");
		ajax_getRequest("test");
	}//end ajax_getTestResponse()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function callback_test(xmlObj) {
		$xmlObj = $(xmlObj);
		updatePageLoadData("Response complete... time was (" + $xmlObj.find("time").text() +")");
		$("#response").html(Base64.decode($xmlObj.find("post_data").text()));
	}//end callback_test()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function callback_get(xmlObj) {
		$xmlObj = $(xmlObj);
		updatePageLoadData("GET response complete...");
		$("#getData").html(Base64.decode($xmlObj.find("all_get_tags").text()));
	}//end callback_get()
	//-------------------------------------------------------------------------

/* END AJAX TEST FUNCTIONS */



//-------------------------------------------------------------------------
$(document).ready(function() {
	updatePageLoadData("page loaded!");
	$("#button1").click(function() {
		ajax_postTestResponse();
	});
	$("#button2").click(function() {
		ajax_getTestResponse();
	});
});
//-------------------------------------------------------------------------
