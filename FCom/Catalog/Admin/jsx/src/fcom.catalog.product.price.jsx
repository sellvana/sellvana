/**
 * Created by pp on 02-26-2015.
 */
define(['jquery', 'underscore', 'react'], function ($, _, React) {
    var productPrice = {
        init: function (options) {
            this.options = _.extend({}, this.options, options);
        }
    };
    return productPrice;
});
