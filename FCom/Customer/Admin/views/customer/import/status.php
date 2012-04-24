<?php print_r($this->config); ?>

<?php switch($this->config['status']): case ''; case 'idle': case 'stopped': ?>

<button type="button" class="btw st1 sz1" id="step3-start">Start Import with selected configuration</button>

<?php break; case 'running': ?>

<button type="button" class="btw st1 sz1" id="step3-stop">Stop Import</button>

<script>
setTimeout(function() {
    $('#import-status').load('<?=BApp::href('customers/import/status')?>');
}, 2000);
</script>

<?php endswitch ?>