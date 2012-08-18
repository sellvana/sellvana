

<div id="control_index_dialog">
    <div id="progressbar"></div>
    <div id="indexing_message"></div>
</div>

<script type="text/javascript">
    function control_index_dialog() {

        var options;

        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/indexing-status')?>"
        }).done(function( json ) {
            data = JSON.parse(json);
            if ( data.percent == 100 ) {
                options = {buttons:
                {
                    "Start indexing":control_index_start
                }
                };
            } else {
                setInterval('updateIndexingProgressBar()', 3000);
                options = {buttons:
                {
                    Stop:control_index_stop,
                    Pause:control_index_pause
                }
                };
            }
            $('#control_index_dialog').dialog(options);
        });

    }

    function updateIndexingProgressBar()
    {
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/indexing-status')?>"
        }).done(function( json ) {
            data = JSON.parse(json);
            $('#progressbar').progressbar({value: parseInt(data.percent)});
            if (100 == data.percent) {
                $('#progressbar').hide();
                $('#indexing_message').html("Indexing done. Total "+data.indexed+" products indexed.");
            }
        });

    }

    function control_index_start()
    {
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/index')?>"
            }
        );

       //var r = Math.random()*100;
       $("#progressbar").progressbar({ value: 0 });
    }


    function control_index_stop()
    {

    }
    function control_index_resume()
    {

    }
    function control_index_pause()
    {

    }
</script>