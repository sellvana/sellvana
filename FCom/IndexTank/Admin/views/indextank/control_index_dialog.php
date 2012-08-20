

<div id="control_index_dialog">
    <div id="progressbar"></div>
    <div id="indexing_message"></div>
</div>

<script type="text/javascript">
    //timer interval
    var interval = -1;

    function control_index_dialog()
    {
        updateIndexingProgressBar();
        return;
    }

    function updateIndexingProgressBar()
    {
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/indexing-status')?>"
        }).done(function( json ) {
            data = JSON.parse(json);
            $('#progressbar').progressbar({value: parseInt(data.percent)});
            $('#progressbar').show();

            if (data.percent != 100) {
                updateDialogMessage(data.status, data.indexed);
                updateDialogButtons(data.status);
            } else {
                updateDialogMessage('idle', data.indexed);
                updateDialogButtons(data.status);
            }

            if ( -1 == interval && data.status == 'start') {
                manageInterval('start');
            }
        });
    }

    function manageInterval(status)
    {
        if ('start' == status) {
            interval = setInterval('updateIndexingProgressBar()', 1000*60); // 1 minute
        }
        if ('stop' == status) {
            clearInterval(interval);
            interval = -1;
        }
        if ('pause' == status) {
            clearInterval(interval);
        }
    }

    function updateDialogMessage(status, indexed)
    {
        if ( status == 'start'  ) {
            $('#indexing_message').html("Indexing STARTED: "+indexed+" products indexed.");
        } else if ( status == 'pause' ) {
            $('#indexing_message').html("Indexing PAUSED: "+indexed+" products indexed.");
        } else if ( status == 'resume' ) {
            $('#indexing_message').html("Indexing STARTED: "+indexed+" products indexed.");
        } else if (status == 'idle') {
            $('#indexing_message').html("Indexing IDLE: "+indexed+" products indexed.");
        }
    }

    function updateDialogButtons(status)
    {
        var options = {};
        options['close'] = function(event, ui) { manageInterval('stop'); }
        options['title'] = "Indexing control panel: ";

        if ( status == 'start'  ) {
            options['title'] += "STARTED";
            options['buttons'] =
            {
                "Restart indexing":control_index_start,
                Pause:control_index_pause
            };
        } else if ( status == 'pause' ) {
            options['title'] += "PAUSED";
            options['buttons'] =
            {
                "Restart indexing":control_index_start,
                Resume:control_index_resume
            };
        } else if ( status == 'resume' ) {
            options['title'] += "STARTED";
            options['buttons'] =
            {
                "Restart indexing":control_index_start,
                Pause:control_index_pause
            };
        }



        $('#control_index_dialog').dialog(options);
    }

    function control_index_start()
    {
        updateDialogButtons('start');
        $("#progressbar").progressbar({ value: 0 });
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/index')?>"
            }
        ).done(function( ) {
            manageInterval('start');
            updateIndexingProgressBar();
        });
    }


    function control_index_resume()
    {
        updateDialogButtons('resume');
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/index-resume')?>"
            }
        ).done(function( ) {
            manageInterval('start');
            updateIndexingProgressBar();
        });

    }
    function control_index_pause()
    {
        updateDialogButtons('pause');
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/index-pause')?>"
            }
        ).done(function( ) {
            manageInterval('pause');
            updateIndexingProgressBar();
        });

    }
</script>