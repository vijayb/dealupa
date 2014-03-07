var page = new WebPage();
var url = phantom.args[0];

page.open(url, function (status) {
    if (status !== 'success') {
        console.log('Unable to access network');
    } else {
                console.log(page.evaluate(function () {
                    var bought = document.querySelectorAll('html')[0].outerHTML;
                    //console.log(bought);
                    return bought;
                }));
    }
    phantom.exit();
});
