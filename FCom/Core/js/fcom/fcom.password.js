define(['jquery', 'jquery.validate'], function($) {

    $.validator.addMethod('passwordSecurity', validatePasswordSecurity, 'Password must be at least 7 characters in length and must include at least one letter, one capital letter, one number, and one special character.');
    function validatePasswordSecurity (value, element, param) {
        var regex = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[~!@#$%^&*()_+=}{><;:\]\[?]).{7,}/;
        if (value.length > 0 && !regex.test(value)) {
            return false;
        }
        return true;
    }
    $.fn.strengthLevelPassword = function () {
        var html = '<div class="progress" style="margin-bottom: 0; margin-top: 5px">' +
                '<div id="progress-bar-password" class="progress-bar bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div></div>';
        $(this).parent().append(html);
        $(this).keyup(function () {
            var regex = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[~!@#$%^&*()_+=}{><;:\]\[?]).{7,}/;
            if (regex.test($(this).val())) {
                $('#progress-bar-password').css('width', '70%');
                if ($(this).val().length > 12) {
                    $('#progress-bar-password').css('width', '70%');
                }
                $('#progress-bar-password').parent().removeClass('progress-warning');
            } else {

                if ($(this).val().length > 7) {
                    $('#progress-bar-password').css('width', '50%');
                } else if ($(this).val().length == 0) {
                    $('#progress-bar-password').css('width', '0%');
                } else {
                    $('#progress-bar-password').css('width', '30%');
                }
                $('#progress-bar-password').parent().addClass('progress-warning');
            }
        })
    }
    $('.has-progress-bar').strengthLevelPassword();
})