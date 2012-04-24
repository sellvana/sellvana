<?php $m = $this->model ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h2 class="label">Field</h2>
            <input type="text" readonly="true" name="model[field_name]" value="<?php echo $this->q($m->field_name) ?>"/>
        </li>

        <?php if ($m->facets):?>
        <li>
            <h4 class="label">Label</h4>
            <input type="text" name="model[field_nice_name]" value="<?php echo $this->q($m->field_nice_name) ?>"/>
        </li>
        <?php endif; ?>

        <li>
            <h4 class="label">Search</h4>
            <input type="checkbox" name="model[search]" value="1" <?= $m->search ?'checked' : '' ?>/>
        </li>
        <li>
            <h4 class="label">Facets</h4>
            <input type="checkbox" name="model[facets]" value="1" <?= $m->facets ?'checked' : '' ?>/>
        </li>

        <?php if ($m->search):?>
        <li>
            <h4 class="label">Priority</h4>
            <input type="text" size="3" id="main-content" name="model[priority]" value="<?php echo $this->q($m->priority) ?>">
            (Default 1)
        </li>
        <?php endif; ?>

        <?php if ($m->facets):?>
        <li>
            <h4 class="label">Display as</h4>
            <select name="model[show]">
                <option <?=('checkbox' == $m->show)?'selected':''?> value="checkbox">Checkbox</option>
                <option <?=('link' == $m->show || '' == $m->show)?'selected':''?> value="link">Link</option>
            </select>
        </li>

        <li>
            <h4 class="label">Filter type</h4>
            <select name="model[filter]">
                <option <?=('exclusive' == $m->filter)?'selected':''?> value="exclusive">Exclusive</option>
                <option <?=('inclusive' == $m->filter || '' == $m->filter)?'selected':''?> value="inclusive">Inclusive</option>
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