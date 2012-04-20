<?php $m = $this->model ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Field</h4>
            <input type="text" name="model[field_name]" value="<?php echo $this->q($m->field_name) ?>"/>
        </li>
        <?php if ($m->type == 'fulltext'):?>
        <li>
            <h4 class="label">Priority</h4>
            <input type="text" id="main-content" name="model[priority]" value="<?php echo $this->q($m->priority) ?>">
        </li>
        <?php endif; ?>
        <?php if ($m->type == 'category'):?>
        <li>
            <h4 class="label">Display as</h4>
            <select name="model[show]">
                <option <?=('checkbox' == $m->show || '' == $m->show)?'selected':''?> value="checkbox">Checkbox</option>
                <option <?=('link' == $m->show)?'selected':''?> value="link">Link</option>
            </select>
        </li>

        <li>
            <h4 class="label">Filter type</h4>
            <select name="model[filter]">
                <option <?=('exclusive' == $m->filter || '' == $m->filter)?'selected':''?> value="exclusive">Exclusive</option>
                <option <?=('inclusive' == $m->filter)?'selected':''?> value="inclusive">Inclusive</option>
            </select>
        </li>
        <?php endif; ?>
    </ul>
</fieldset>
<script>
head(function() {
adminForm.wysiwygCreate('main-content');
});
</script>