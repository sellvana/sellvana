<?php $m = $this->model; ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Item quantity </h4>
            <input type="text" name="model[item_qty]" value="<?php echo $m->item_qty ?>"/>
        </li>
        <li>
            <h4 class="label">Shipping method</h4>
            <input type="text" name="model[shipping_method]" value="<?php echo $this->q($m->shipping_method) ?>"/>
        </li>
        <li>
            <h4 class="label">Payment method </h4>
            <input type="text" name="model[payment_method]" value="<?php echo $this->q($m->payment_method) ?>"/>
        </li>
        <li>
            <h4 class="label">Status</h4>
            <select name="model[status]">
                <option value="new" <?='new' == $m->status?'selected':''?>>New</option>
                <option value="paid" <?='paid' == $m->status?'selected':''?>>Paid</option>
            </select>
        </li>
    </ul>
</fieldset>
