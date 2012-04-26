<?php $config = FCom_Customer_Import::i()->config(); $start = BRequest::i()->get('start'); ?>

<?php if ($start || $config['status']==='running'): ?>

<button type="button" class="btw st1 sz1" id="step3-stop">Stop Import</button>

<script>
<?php if ($start): ?>
$.post('<?=BApp::href('customers/import/start')?>');
<?php endif ?>
setTimeout(function() {
    $('#import-status').load('<?=BApp::href('customers/import/status')?>');
}, 2000);
</script>

<?php else: ?>

<button type="button" class="btw st1 sz1" id="step3-start">Start Import with selected configuration</button>

<?php endif ?>
<pre><?php print_r($config); ?></pre>