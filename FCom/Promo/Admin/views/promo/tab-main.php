<?php
    $m = $this->model;
    $promo = FCom_Promo_Model_Promo::i();
?>

<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Description</h4>
            <input type="text" name="model[description]" value="<?php echo $this->q($m->description) ?>" style="width:90%"/>
        </li>
        <li>
            <h4 class="label">Date Range</h4>
            Start:
            <input type="text" id="model-from_date" name="model[from_date]" value="<?php echo BLocale::i()->datetimeDbToLocal($m->from_date) ?>" class="datepicker"/>
            End:
            <input type="text" id="model-to_date" name="model[to_date]" value="<?php echo BLocale::i()->datetimeDbToLocal($m->to_date) ?>" class="datepicker"/>
        </li>
        <li>
            <h4 class="label">Promotion Structure</h4>
            <table class="adm-form-subtable">
                <tr>
                    <td>BUY</td>
                    <td><select name="model[buy_type]"><?php echo $this->optionsHtml($promo->fieldOptions('buy_type'), $m->buy_type) ?></select></td>
                    <td>FROM</td>
                    <td><select name="model[buy_group]"><?php echo $this->optionsHtml($promo->fieldOptions('buy_group'), $m->buy_group) ?></select></td>
                    <td>GET</td>
                    <td><select name="model[get_type]"><?php echo $this->optionsHtml($promo->fieldOptions('get_type'), $m->get_type) ?></select></td>
                    <td>OF</td>
                    <td><select name="model[get_group]"><?php echo $this->optionsHtml($promo->fieldOptions('get_group'), $m->get_group) ?></select></td>
                </tr>
            </table>
        <li>
        <li>
            <h4 class="label">Details</h4>

            <textarea id="model-details" name="model[details]"><?php echo $this->q($m->details) ?></textarea>
        </li>
    </ul>
</fieldset>
<script>
$(function() {
    adminForm.wysiwygCreate('model-details');
    $('#model-from_date,#model-to_date').datepicker();
})
</script>