define(["jquery", "select2", "fcom.core"], function($) {
    $(function() {
        $('.select2').select2({width:'other values', minimumResultsForSearch:20, dropdownAutoWidth:true});
        $.cookie.options = FCom.cookie_options;
    });
})
