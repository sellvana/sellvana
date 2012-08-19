

<div id="control_index_dialog">
    <div id="progressbar"></div>
    <div id="indexing_message"></div>
</div>

<script type="text/javascript">
    function control_index_dialog() {

        var options = {};
        options['title'] = "Indexing control panel";

        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/indexing-status')?>"
        }).done(function( json ) {
            data = JSON.parse(json);
            console.log(data.status);
            if ( data.status == 'stop' ) {
                options['buttons'] =
                {
                    "Start indexing":control_index_start
                };
            } else if ( data.status == 'start' ) {
                options['buttons'] =
                {
                    Stop:control_index_stop,
                    Pause:control_index_pause
                };
            } else if ( data.status == 'pause' ) {
                options['buttons'] =
                {
                    Stop:control_index_stop,
                    Resume:control_index_resume
                };
            }

            $('#control_index_dialog').dialog(options);
        });

       updateIndexingProgressBar();
       setInterval('updateIndexingProgressBar()', 5000);
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
                $('#indexing_message').html("Indexing done: "+data.indexed+" products indexed.");
                updateDialogButtons('stop');
            } else {
                $('#progressbar').show();
                $('#indexing_message').html("Indexing in progress: "+data.indexed+" products indexed.");
                updateDialogButtons(data.status);
            }

        });
    }

    function updateDialogButtons(status)
    {
        console.log(status);
        var options = {};
        options['title'] = "Indexing control panel";
        if (status == 'stop' ) {
                options['buttons'] =
                {
                    "Start indexing":control_index_start
                };
        } else if ( status == 'start'  ) {
                options['buttons'] =
                {
                    Stop:control_index_stop,
                    Pause:control_index_pause
                };
        } else if ( status == 'pause' ) {
                options['buttons'] =
                {
                    Stop:control_index_stop,
                    Resume:control_index_resume
                };
        } else if ( status == 'resume' ) {
                options['buttons'] =
                {
                    Stop:control_index_stop,
                    Pause:control_index_pause
                };
        }

        $('#control_index_dialog').dialog(options);
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
       updateDialogButtons('start');
    }


    function control_index_stop()
    {
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/index-stop')?>"
            }
        );
        updateDialogButtons('stop');
    }
    function control_index_resume()
    {
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/index-resume')?>"
            }
        );
        updateDialogButtons('resume');
    }
    function control_index_pause()
    {
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/index-pause')?>"
            }
        );
        updateDialogButtons('pause');
    }
</script>