<?php $config = FCom_Customer_Import::i()->config(); $start = BRequest::i()->get('start'); ?>

<?php if ($start || $config['status']==='running'): ?>

<button type="button" class="btn st1 sz2" id="step3-stop">Stop Import</button>

<script>
<?php if ($start): ?>
$.post('<?=BApp::href('customers/import/start')?>');
<?php endif ?>
setTimeout(function() {
    $('#import-status').load('<?=BApp::href('customers/import/status')?>');
}, 2000);
</script>

<?php else: ?>

<button type="button" class="btn st1 sz2" id="step3-start">Start Import with selected configuration</button>

<?php endif ?>
<pre><?php print_r($config); ?></pre>

<table class="data-table">
	<tr>
		<th>Status</th>
		<td style="background:#bbf4a5;">Done</td>
	</tr>
	<tr>
		<th>Start Time</th>
		<td>1337753864</td>
	</tr>
	<tr>
		<th>Rows Total</th>
		<td>3076</td>
	</tr>
	<tr>
		<th>Rows Processed</th>
		<td>3075</td>
	</tr>
	<tr>
		<th>Rows Skipped</th>
		<td>1</td>
	</tr>
	<tr>
		<th>Rows Warning</th>
		<td>0</td>
	</tr>
	<tr>
		<th>Rows Error</th>
		<td>0</td>
	</tr>
	<tr>
		<th>Rows No Change</th>
		<td style="background:#fe9696;">0</td>
	</tr>
	<tr>
		<th>Rows Created</th>
		<td>0</td>
	</tr>
	<tr>
		<th>Rows Updated</th>
		<td>115</td>
	</tr>
	<tr>
		<th>Memory Usage</th>
		<td>166471312</td>
	</tr>
	<tr>
		<th>Run Time</th>
		<td>14.808820009232</td>
	</tr>
</table>