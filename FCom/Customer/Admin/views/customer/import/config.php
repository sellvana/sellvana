<?php
$hlp = FCom_Customer_Import::i();
$info = $hlp->getFileInfo($this->dir.'/'.$this->file);
?>
<form method="post" id="import-columns-form">
    <input type="hidden" name="config[filename]" value="<?=$this->q($this->file)?>"/>
    <table>
    <tr>
        <td>
            <table>
            <thead><tr><th>Column Content</th><th>DB Field</th></tr></thead><tbody>
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
        <td>
            Field Delimiter: <input type="text" name="config[delim]" value="<?=$this->q($info['delim'])?>"/><br/>
            Skip First Lines: <input type="text" name="config[skip_first]" value="<?=$this->q($info['skip_first'])?>"/>
        </td>
    </tr>
    </table>
    <button type="button" class="btw st1 sz1" id="step2-next">Save configuation and go to next step</button>
</form>