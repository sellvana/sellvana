
<script type='text/javascript'>
var publicApiUrl = "<?=$this->public_api_url?>";
var indexName = "<?=$this->index_name?>";
var elementId = "#query";
var remoteSource = publicApiUrl + "/v1/indexes/" + indexName + "/autocomplete";
</script>

<script src='https://www.google.com/jsapi' type='text/javascript'></script>

<script type='text/javascript'>
    var theme = "flick";
    google.load("jqueryui", "1.8.17");
    google.loader.writeLoadTag("css", "https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/themes/" + theme + "/jquery-ui.css");
</script>

<script type='text/javascript'>
    google.setOnLoadCallback(function() {
    $(function() {

    var sourceCallback = function( request, responseCallback ) {
      $.ajax( {
        url: remoteSource,
        dataType: "jsonp",
        data: { query: request.term, field: 'description' },
        success: function( data ) { responseCallback( data.suggestions ); }
      } );
    };

    var selectCallback = function( event, ui ) {
      event.target.value = ui.item.value;
      event.target.form.submit();
    };

    $( elementId ).autocomplete( {
      source: sourceCallback,
      minLength: 2,
      delay: 100,
      select: selectCallback
    } );

    }); // $ fun
    }); // g callback

</script>