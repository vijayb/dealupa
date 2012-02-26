var page = new WebPage(), testindex = 0, loadInProgress = false;
var url = phantom.args[0];

page.onConsoleMessage = function(msg) {
    console.log(msg);
};

page.onLoadStarted = function() {
    loadInProgress = true;
    //console.log("load started");
};

page.onLoadFinished = function() {
    loadInProgress = false;
    //console.log("load finished");
};
var steps = [
    function(){
        //Load Login Page
	page.open(url);
    }, function(){
        // Enter Credentials
        page.evaluate(function(){
            var arr = document.getElementsByClassName("login-form");
	    var i;
	    
	    for (i=0; i < arr.length; i++) {
		if (arr[i].getAttribute('method') == "POST") {
		    arr[i].elements["email"].value="thewire1978@hotmail.com";
		    arr[i].elements["password"].value="daewoo";
		    return;
		}
	    }
        });
    }, function(){
        //Login
        page.evaluate(function(){
	    var arr = document.getElementsByClassName("login-form");
	    var i;
	    
	    for (i=0; i < arr.length; i++) {
		if (arr[i].getAttribute('method') == "POST") {
		    arr[i].submit();
		    return;
		}
	    }
        });
    }, function(){
        page.evaluate(function(){
            console.log(document.querySelectorAll('html')[0].outerHTML);
        });
    }
];

interval = setInterval(function() {
    if (!loadInProgress && typeof steps[testindex] == "function")
    {
        //console.log("step " + (testindex + 1));
        steps[testindex]();
        testindex++;
    }
    if (typeof steps[testindex] != "function")
    {
        //console.log("test complete!");
        phantom.exit();
    }
}, 30);