define(["jquery"], function ($) {
    FCom.add_review_rating = function (url, rid, helpful) {
        $.ajax({
            type: "POST",
            url: url,
            data: { rid: rid, review_helpful: helpful }
        }).done(function (msg) {
                if (msg['redirect']) {
                    window.location.replace(msg['redirect']);
                    return false;
                }
                if (msg['error']) {
                    $('#block_review_helpful_' + rid).hide();
                    $('#block_review_helpful_done_' + rid).css('color', 'red');
                    $('#block_review_helpful_done_' + rid).html(msg['error']);
                } else {
                    $('#block_review_helpful_' + rid).hide();
                    $('#block_review_helpful_done_' + rid).html("Thank you for your feedback!");
                }
            });
    }
});
