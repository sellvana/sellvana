

<div id="control_index_dialog" style="width: 400px; display: none;">
    <div id="progressbar"></div>
    <div id="indexing_message"></div>
    <div id="indexing_info">
        Index name: <b><?=$this->status['name']?></b><br/>
        Index created at: <b><?=date("Y-m-d", strtotime($this->status['date']))?></b>
    </div>
</div>

<script type="text/javascript">
    //timer interval
    var interval = -1;

    function control_index_dialog()
    {
        updateIndexingStatus();
        return;
    }

    function updateIndexingStatus()
    {
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/indexing-status')?>"
        }).done(function( json ) {
            data = JSON.parse(json);
            $('#progressbar').progressbar({value: parseInt(data.percent)});
            $('#progressbar').show();

            if (data.percent != 100) {
                updateDialogMessage(data.status, data);
                updateDialogButtons(data.status);
            } else {
                updateDialogMessage('idle', data);
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
            interval = setInterval('updateIndexingStatus()', 1000*60); // 1 minute
        } else if ('stop' == status) {
            clearInterval(interval);
            interval = -1;
        }
    }

    function updateDialogMessage(status, data)
    {
        var html = '';
        if ( status == 'start' || status == 'resume'  ) {
            html = "Indexing status: <b>RUNNING</b><br/> ";
        } else if ( status == 'pause' ) {
            html = "Indexing status: <b>PAUSED</b><br/> ";
        } else if (status == 'idle') {
            html = "Indexing status: <b>IDLE</b> (waiting for updates)<br/> ";
        }
        html += "Indexed: <b>" + data.percent + "%</b> products<br/>";
        html += "Left to index: <b>" + data.to_index + "</b> products<br/>";
        html += "Index size: <b>" + data.index_size + "</b> products<br/>";
        $('#indexing_message').html(html);
    }

    function updateDialogButtons(status)
    {
        var options = {};
        options['close'] = function(event, ui) { manageInterval('stop'); }
        options['title'] = "Indexing control panel";
        options['width'] = 420;

        if ( status == 'start' || status == 'resume' ) {
            options['buttons'] =
            {
                "Restart indexing":controlIndexStart,
                Pause:controlIndexPause
            };
        } else if ( status == 'pause' ) {
            options['buttons'] =
            {
                "Restart indexing":controlIndexStart,
                Resume:controlIndexResume
            };
        }

        $('#control_index_dialog').dialog(options);
    }

    function controlIndexStart()
    {
        updateDialogButtons('start');
        $("#progressbar").progressbar({ value: 0 });
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/index')?>"
            }
        ).done(function() {
            manageInterval('start');
            updateIndexingStatus();
        });
    }


    function controlIndexResume()
    {
        updateDialogButtons('resume');
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/index-resume')?>"
            }
        ).done(function() {
            manageInterval('start');
            updateIndexingStatus();
        });

    }
    function controlIndexPause()
    {
        updateDialogButtons('pause');
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('indextank/products/index-pause')?>"
            }
        ).done(function() {
            manageInterval('stop');
            updateIndexingStatus();
        });

    }
</script>