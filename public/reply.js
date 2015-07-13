
var reply = function(msgId){
    $('#modal-title').html("Reply");
    $('#tofield').val($('#mailsender' + msgId).html());
    $('#subjectfield').val('[re] ' + $('#mailsubject' + msgId).html());

    var message = $('#mailcontent' + msgId).html().replace(/<br>/g, '>');
    $('#messagefield').val('\n>' + message);


    $('#modal_reply').modal('show');

};

var forward = function(msgId){
    $('#modal-title').html("Forward email");

    $('#tofield').val();
    $('#subjectfield').val('[fwd] ' + $('#mailsubject' + msgId).html());

    var message = $('#mailcontent' + msgId).html().replace(/<br>/g, '>');
    $('#messagefield').val('\n>' + message);

    $('#modal_reply').modal('show');

};

var compose = function(msgId){
    $('#modal-title').html("Compose new message");

    $('#tofield').val();
    $('#subjectfield').val();
    $('#messagefield').val();


    $('#modal_reply').modal('show');
};