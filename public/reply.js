
$.fn.goValidate = function() {
    var $form = this,
        $inputs = $form.find('input:text');

    var validators = {
        name: {
            regex: /^[A-Za-z]{3,}$/
        },
        pass: {
            regex: /(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}/
        },
        email: {
            regex: /^[\w\-\.\+]+\@[a-zA-Z0-9\.\-]+\.[a-zA-z0-9]{2,4}$/
        },
        phone: {
            regex: /^[2-9]\d{2}-\d{3}-\d{4}$/
        }
    };
    var validate = function(klass, value) {
        var isValid = true,
            error = '';

        if (!value && /required/.test(klass)) {
            error = 'This field is required';
            isValid = false;
        } else {
            klass = klass.split(/\s/);
            $.each(klass, function(i, k){
                if (validators[k]) {
                    if (value && !validators[k].regex.test(value)) {
                        isValid = false;
                        error = validators[k].error;
                    }
                }
            });
        }
        return {
            isValid: isValid,
            error: error
        }
    };
    var showError = function($input) {
        var klass = $input.attr('class'),
            value = $input.val(),
            test = validate(klass, value);

        $input.removeClass('invalid');
        $('#form-error').addClass('hide');

        if (!test.isValid) {
            $input.addClass('invalid');

            if(typeof $input.data("shown") == "undefined" || $input.data("shown") == false){
                $input.popover('show');
            }

        }
        else {
            $input.popover('hide');
        }
    };

    $inputs.keyup(function() {
        showError($(this));
    });

    $inputs.on('shown.bs.popover', function () {
        $(this).data("shown",true);
    });

    $inputs.on('hidden.bs.popover', function () {
        $(this).data("shown",false);
    });

    $form.submit(function(e) {

        $inputs.each(function() { /* test each input */
            if ($(this).is('.required') || $(this).hasClass('invalid')) {
                showError($(this));
            }
        });
        if ($form.find('input.invalid').length) { /* form is not valid */
            e.preventDefault();
            $('#form-error').toggleClass('hide');
        }
    });
    return this;
};
$('form').goValidate();

var translations = {
    en : {
        reply_title: 'Reply',
        forward_title: 'Forward',
        compose_title: 'Compose'
    },
    hr: {
        reply_title: 'Odgovori',
        forward_title: 'Šalji dalje',
        compose_title: 'Nova poruka'
    }
};

var reply = function(msgId){
    var locale = getCookie('golublocale');
    console.log(locale);

    $('#modal-title').html("Reply");
    $('#tofield').val($('#mailsender' + msgId).html());
    $('#subjectfield').val('[re] ' + $('#mailsubject' + msgId).html());

    var message = $('#mailcontent' + msgId).html().replace(/<br>/g, '>');
    $('#messagefield').val('\n>' + message);


    $('#modal_reply').modal('show');

};

var forward = function(msgId){
    $('#modal-title').html("Forward email");

    $('#tofield').val('');
    $('#subjectfield').val('[fwd] ' + $('#mailsubject' + msgId).html());

    var message = $('#mailcontent' + msgId).html().replace(/<br>/g, '>');
    $('#messagefield').val('\n>' + message);

    $('#modal_reply').modal('show');

};

var compose = function(){
    $('#modal-title').html("Compose new message");

    $('#tofield').val('');
    $('#subjectfield').val('');
    $('#messagefield').val('');


    $('#modal_reply').modal('show');
};

var fav = function(msgId){
    console.log("ajax");
    $.ajax({
        type: "POST",
        url: '/fav',
        dataType: 'json',
        data: {messageId: msgId}
    }).done(function(msg){
        console.log("returned: ", msg);
    });
};

$(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': $('meta[name="_token"]').attr('content')
        }
    });
});

function getCookie(c_name) {
    var c_value = " " + document.cookie;
    var c_start = c_value.indexOf(" " + c_name + "=");
    if (c_start == -1) {
        c_value = null;
    }
    else {
        c_start = c_value.indexOf("=", c_start) + 1;
        var c_end = c_value.indexOf(";", c_start);
        if (c_end == -1) {
            c_end = c_value.length;
        }
        c_value = unescape(c_value.substring(c_start,c_end));
    }
    return c_value;
}