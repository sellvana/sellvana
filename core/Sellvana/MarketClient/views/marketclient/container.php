<!DOCTYPE html>
<html>
<head>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
  <script>
      (function($) {
          var baseUrl = '<?= $this->BApp->href('marketclient/download') ?>',
              redirectUrl = '<?= $this->q($this->redirect_to) ?>',
              modules = <?= $this->BUtil->toJson($this->modules) ?>,
              csrfToken = '<?= $this->BSession->csrfToken() ?>';

          function start() {
              $('#progress-stop').click(stop);
              $('#progress-restart').click(restart);
              $.post(baseUrl + '/start', { modules: modules, 'X-CSRF-TOKEN': csrfToken }, onStartSuccess);
              progress();
          }

          function progress() {
              $.get(baseUrl + '/progress', function(response, status, xhr) {
                  switch (response.progress ? response.progress.status : null) {
                      case 'DONE':
                          if (redirectUrl) {
                              window.top.location.href = redirectUrl;
                          }
                          break;

                      default:
                          setTimeout(progress, 2000);
                  }
                  $('#progress-container').html(response.html);
              })
          }

          function stop() {
              $.post(baseUrl + '/stop', { 'X-CSRF-TOKEN': csrfToken });
          }

          function restart() {
              $.post(baseUrl + '/start', { modules: modules, 'X-CSRF-TOKEN': csrfToken, force: true }, onStartSuccess);
          }

          function onStartSuccess(response, status, xhr) {
              if (response.error) {
                  $('#status-container').html('ERROR: ' + response.message);
              }
          }

          $(start);

      })(jQuery);
  </script>
  <style>
  body {
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 14px;
    line-height: 1.42857;
    color: #444;
    background-color: white;
    padding:0 5px;
    }

  </style>
</head>
<body>
    <h1><?= $this->BLocale->_('Downloading and installing packages...') ?></h1>
    <!--
    <button id="progress-stop" type="button"><?= $this->q('STOP') ?></button>
    <button id="progress-restart" type="button"><?= $this->q('RESTART') ?></button>
    -->
    <div id="progress-container"></div>
    <div id="status-container"></div>
</body>

