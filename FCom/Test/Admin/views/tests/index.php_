<form>
    <button onclick="ajax_tests_run(); return false;">Run tests cgi</button>
    <button onclick="ajax_tests_run2(); return false;">Run tests web</button>
</form>

<div id="results" style="width: 640px; height: 480px; display: none; max-height:480px;overflow:auto;">
hello
</div>

<script type="text/javascript">
    function ajax_tests_run() {
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('tests/run')?>"
        }).done(function( msg ) {
                    $('#results').show().html(msg);
        });
        return false;
    }
    function ajax_tests_run2() {
        $.ajax({
            type: "GET",
            url: "<?=BApp::href('tests/run2')?>"
        }).done(function( msg ) {
                    $('#results').show().html(msg);
        });
        return false;
    }
</script>