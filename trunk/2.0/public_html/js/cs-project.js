
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
	function ajax_getTestResponse() {
		
		
		myPostData = {
			"firstItem"		: "myTest",
			"secondItem"	: $("#pageLoadData").html()
		}
		show_ajaxLoading("Checking response...");
		ajax_doPost("test", myPostData, "WAIT", "Doing a test POST via AJAX, be patient.");
		
	}//end ajax_getTestResponse()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function callback_test(xmlObj) {
		$xmlObj = $(xmlObj);
		updatePageLoadData("Response complete... time was (" + $xmlObj.find("time").text() +")");
		$("#response").html($xmlObj.find("post_data").text());
	}//end callback_test()
	//-------------------------------------------------------------------------

/* END AJAX TEST FUNCTIONS */



//-------------------------------------------------------------------------
$(document).ready(function() {
	updatePageLoadData("page loaded!");
	$("#button1").click(function() {
		ajax_getTestResponse();
	});
});
//-------------------------------------------------------------------------
