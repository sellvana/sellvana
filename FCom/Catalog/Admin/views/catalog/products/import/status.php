<?php $c = FCom_Catalog_ProductsImport::i()->config(); $start = BRequest::i()->get('start') ?>

<?php if ($start || $c['status']==='running'): ?>

<button type="button" class="btn st1 sz2" id="step3-stop">Stop Import</button>

<script>
<?php if ($start): ?>
$.post('<?=BApp::href('catalog/products/import/start')?>');
<?php endif ?>
setTimeout(function() {
    $('#import-status').load('<?=BApp::href('catalog/products/import/status')?>');
}, 2000);
</script>

<?php else: ?>

<button type="button" class="btn st1 sz2" id="step3-start">Start Import with selected configuration</button>
<hr>

<?php endif ?>

<?php if (!empty($c['status']) && isset($c['rows_processed'])): ?>
<?php $pct = intval($c['rows_processed']/$c['rows_total']*100) ?>
<table class="data-table">
    <tr>
        <th>Status</th>
        <td><?=$c['status']?></td>
    </tr>
    <tr>
        <th>Progress</th>
        <td>
            <div style="background:#bbf4a5; overflow:visible; width:<?=$pct?>%; font-weight:bold"><?=$pct.'%'?></div>
        </td>
    </tr>
	<tr>
		<th>Start Time</th>
		<td><?=date('Y-m-d H:i:s', $c['start_time'])?></td>
	</tr>
    <tr>
        <th>Crunch Rate</th>
        <td><?=$c['run_time'] ? number_format($c['rows_processed']/$c['run_time'], 2) : 0 ?> rows/sec</td>
    </tr>
    <tr>
        <th>Rows Total</th>
        <td><?=$c['rows_total']?></td>
    </tr>
	<tr>
		<th>Rows Processed</th>
		<td><?=$c['rows_processed']?></td>
	</tr>
	<tr>
		<th>Rows Skipped</th>
		<td><?=$c['rows_skipped']?></td>
	</tr>
	<tr>
		<th>Rows Warning</th>
		<td <?=$c['rows_warning']?'style="background:#FFFFC0"':''?>><?=$c['rows_warning']?></td>
	</tr>
	<tr>
		<th>Rows Error</th>
		<td <?=$c['rows_error']?'style="background:#fe9696"':''?>><?=$c['rows_error']?></td>
	</tr>
	<tr>
		<th>Rows No Change</th>
		<td><?=$c['rows_nochange']?></td>
	</tr>
	<tr>
		<th>Rows Created</th>
		<td <?=$c['rows_created']?'style="background:#bbf4a5"':''?>><?=$c['rows_created']?></td>
	</tr>
	<tr>
		<th>Rows Updated</th>
		<td <?=$c['rows_updated']?'style="background:#bbf4a5"':''?>><?=$c['rows_updated']?></td>
	</tr>
	<tr>
		<th>Memory Usage</th>
		<td><?=number_format($c['memory_usage'], 0)?></td>
	</tr>
	<tr>
		<th>Run Time</th>
		<td><?=number_format($c['run_time'], 4)?> sec</td>
	</tr>
</table>
<?php endif ?>