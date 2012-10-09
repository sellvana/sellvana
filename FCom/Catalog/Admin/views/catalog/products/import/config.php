<?php
$hlp = FCom_Catalog_ProductsImport::i();
$info = $hlp->getFileInfo($this->dir.'/'.$this->file);

?>
<form method="post" id="import-columns-form">
    <input type="hidden" name="config[filename]" value="<?=$this->q($this->file)?>"/>

    <table>
    	<tr><td>Field Delimiter:</td><td><input type="text" name="config[delim]" value="<?=$this->q($info['delim'])?>"/></td></tr>
     	<tr><td>Skip First Lines:</td><td><input type="text" name="config[skip_first]" value="<?=$this->q($info['skip_first'])?>"/></td></tr>
        <tr><td>Batch size:</td><td><input type="text" name="config[batch_size]" value="100"/></td></tr>
	</table><br/>
    <table>
	    <tr>
	        <td class="col">
	            <table class="data-table"><thead><tr><th>Column Content</th><th>DB Field</th></tr></thead><tbody>
	            <?php foreach ($info['first_row'] as $i=>$v): ?>
	            <tr>
	                <td><?=$this->q($v) ?></td>
	                <td><select name="config[columns][<?=$i?>]">
	                    <option></option>
	                    <?php echo $this->optionsHtml($hlp->getFieldOptions(), !empty($info['columns'][$i]) ? $info['columns'][$i] : '') ?>
	                </select></td>
	            </tr>
	            <?php endforeach ?>
	            </tbody></table>
	        </td>
	        <td class="col">
	            <table class="data-table"><thead><tr><th>DB Field</th><th>Default</th></tr></thead><tbody>
	            <?php foreach ($hlp->getFieldData() as $k=>$f): ?>
	            <tr>
	                <td><?=$this->q($k) ?></td>
	                <td><?php if (!empty($f['options'])): ?>
	                    <select name="config[defaults][<?=$k?>]">
	                    <option></option>
	                    <?php echo $this->optionsHtml($f['options'], !empty($info['defaults'][$k]) ? $info['defaults'][$k] : '') ?>
	                </select></td>
	                <?php else: ?>
	                    <?php switch (!empty($f['input']) ? $f['input'] : 'text'): case 'text': ?>
	                        <input type="text" name="config[defaults][<?=$k?>]" value="<?=!empty($info['defaults'][$k]) ? $this->q($info['defaults'][$k]) : ''?>"/>
	                    <?php break; case 'textarea': ?>
	                        <textarea name="config[defaults][<?=$k?>]" style="width:400px; height:100px"><?=!empty($info['defaults'][$k]) ? $this->q($info['defaults'][$k]) : ''?></textarea>
	                    <?php endswitch ?>
	                <?php endif ?>
	            </tr>
	            <?php endforeach ?>
	            </tbody></table>

	        </td>
	    </tr>
    </table>
    <button type="button" class="btn st1 sz2" id="step2-next">Save configuration and go to next step</button>
</form>