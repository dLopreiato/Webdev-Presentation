var ROOT_DIRECTORY = 'localhost';
var LINK_URL = 'localhost/link.html?id=';
var SCORE_URL = 'localhost/score.html?id=';

$(document).ready(function() {
    // we use a predefined expression to pull the parameters given in the url
    var senderId = $.urlParam('id');

    $.ajax({
        // where the call is going
        url: 'http://' + ROOT_DIRECTORY + '/api/SpreadBlaze.php',
        // what format we expect a response in
        dataType: 'json',
        // what data we are sending
        data: {uid:senderId},
        // how we are sending the data
        method: 'GET',
        // what we do in the event that the response sent back was an HTTP 200 OK response
        success: function(data) {
            // TODO: Handle the case in which data[name] is null
            
            // update the name section with the score
            $('#parent').html(data['name'] + ' (' + data['score'] + ')');
            // create the sending link
            $('#linkSelector').val('http://' + LINK_URL + data['visitorUid']);
            // create the scoring link
            $('#scoreSelector').html('<a href="http://' + SCORE_URL + data['visitorUid'] + '">' + SCORE_URL + data['visitorUid'] + '</a>');
        },
        // what we do in the event that the response sent back was NOT an HTTP 200 OK response
        error: function(xhr, ajaxOptions, thrownError) {
            // parse the response out of JSON (not automatically done because jquery)
            var serverErrorInfo = JSON.parse(unescape(xhr.responseText));
            // send that error to the console
            console.error('AJAX Error: ' + serverErrorInfo['error'] + "\n" + thrownError);
            $('#error').html("Oh no! Something went wrong!");
        }
    });
});


$.urlParam = function(name){
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    return results[1] || 0;
}