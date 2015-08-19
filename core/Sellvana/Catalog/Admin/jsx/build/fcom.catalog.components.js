define(['react', 'jquery', 'fcom.locale', 'bootstrap', 'underscore'], function (React, $, Locale) {   
    FCom.PriceMixin = {
        validateBasePrice: function(option) {
            return function(value, elem) {
                var valid = false;
                if(typeof elem !== 'undefined') {
                    var $parent = $(elem).parents('.'+option+'-price-item');
                    var $curFields = $parent.find('.customer-group, .site, .currency');
                    var currHash = value;
                    var matchingHashes = {};
                    var siteId = '*', currCode = '*', groupId = '*';
                    $curFields.each(function () {
                        var $el = $(this);
                        var val = $el.val();

                        if($el.hasClass('site')) {
                            siteId = val;
                        } else if($el.hasClass('currency')) {
                            currCode = val;
                        } else if($el.hasClass('customer-group')) {
                            groupId = val;
                        }
                    });

                    matchingHashes[value + '***'] = 1;

                    matchingHashes[value + groupId +'**'] = 1;
                    matchingHashes[value + '*' + siteId + '*'] = 1;
                    matchingHashes[value + '**' + currCode] = 1;
                    matchingHashes[value + groupId + '*' + currCode] = 1;
                    matchingHashes[value + '*' + siteId + currCode] = 1;
                    matchingHashes[value + groupId + siteId + '*'] = 1;

                    matchingHashes[currHash] = 1;

                    var $items = $('.'+option+'-price-item');
                    $items.each(function () {
                        if(valid) {
                            // valid price already found, return
                            return;
                        }

                        var $item = $(this);
                        if($item[0] === $parent[0]) {
                            // same as validated element, return
                            return;
                        }
                        var $priceType = $item.find('.price-type');
                        if($priceType.length === 0) {
                            // no price type return
                            return;
                        }
                        var itemHash = $priceType.val();

                        var $itemFields = $item.find('.customer-group, .site, .currency');
                        var siteId = '*', currCode = '*', groupId = '*', defaultHash = '';
                        $itemFields.each(function () {
                            var $el = $(this);
                            if ($el.hasClass('site')) {
                                siteId = $el.val();
                            } else if ($el.hasClass('currency')) {
                                currCode = $el.val();
                            } else if ($el.hasClass('customer-group')) {
                                groupId = $el.val();
                            }
                        });
                        itemHash += groupId + siteId + currCode;
                        valid = (matchingHashes[itemHash] == 1);
                    });
                }
                return valid;
            };
        },
        validateUniquePrice: function(option) {
            return function(value, elem) {
                var valid = true;
                if (typeof (elem) !== 'undefined') {
                    var parent = $(elem).parents('.'+option+'-price-item');
                    var curFields = parent.find('input.'+option+'PriceUnique, select.'+option+'PriceUnique');
                    var currHash = '';
                    curFields.each(function () {
                        currHash += $(this).val();
                    });

                    var items = $('.'+option+'-price-item');
                    items.each(function () {
                        if (!valid) {
                            return;
                        }

                        var $item = $(this);
                        if (this === parent[0]) {
                            return;
                        }
                        var fields = $item.find('input.'+option+'PriceUnique, select.'+option+'PriceUnique');
                        var checkHash = '';
                        fields.each(function (idx) {
                            checkHash += $(this).val();
                        });
                        valid = currHash != checkHash;
                    });
                    return valid;
                }
            };
        }
    };
});
