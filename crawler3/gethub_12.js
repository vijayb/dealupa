var page = new WebPage();
var count = 0;
var maxScrolls = 7;
// If this value is too low, then the scroll will not have
// executed, and we won't collect the new links. Don't
// want to set it too high either because otherwise hub
// crawling for Amazon would be super slow.
var expectedScrollTime = 1500; 
var url = phantom.args[0];

page.onConsoleMessage = function(msg) {
    console.log(msg);
};

page.onLoadFinished = function() {
    console.log("<html><body>");
    page.evaluate(function() {
	console.log($("a.deal_title").parent().html());
    });

    scroll();
};


function scroll() {
    page.evaluate(function() {
	$('div.scroller_deal_image').each(function(index) {
	    console.log($(this).html());
	});

	$(".scroller_next").click();
    });

    if (count >= maxScrolls) {
	console.log("</body></html>");
	phantom.exit();
    }

    var t=setTimeout("scroll()", 1500);
    count++;
}


page.open(url);
