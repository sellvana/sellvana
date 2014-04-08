<!DOCTYPE html>
<html>
<head>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script>
        (function($) {
            var baseUrl = '<?= BApp::href('marketclient/download') ?>',
                redirectUrl = '<?= $this->q($this->redirect_to) ?>',
                modules = <?= BUtil::toJson($this->modules) ?>,
                csrfToken = '<?= BSession::i()->csrfToken() ?>';

            function start() {
                $('#progress-stop').click(stop);
                $('#progress-restart').click(restart);
                $.post(baseUrl + '/start', { modules: modules, 'X-CSRF-TOKEN': csrfToken });
                setTimeout(progress, 2000);
            }

            function progress() {
                $.get(baseUrl + '/progress', function(response, status, xhr) {
                    switch (response.progress.status) {
                        case 'ACTIVE':
                            setTimeout(progress, 2000);
                            break;

                        case 'DONE':
                            if (redirectUrl) {
                                location.href = redirectUrl;
                            }
                            break;
                    }
                    $('#progress-container').html(response.html);
                })
            }

            function stop() {
                $.post(baseUrl + '/stop', { 'X-CSRF-TOKEN': csrfToken });
            }

            function restart() {
                $.post(baseUrl + '/start', { modules: modules, 'X-CSRF-TOKEN': csrfToken, force: true });
            }

            $(start);

        })(jQuery);
    </script>
</head>
<body>
    <h1><?= BLocale::_('Downloading and installing packages...') ?></h1>
    <!--
    <button id="progress-stop" type="button"><?= $this->q('STOP') ?></button>
    <button id="progress-restart" type="button"><?= $this->q('RESTART') ?></button>
    -->
    <div id="progress-container"></div>
</body>

