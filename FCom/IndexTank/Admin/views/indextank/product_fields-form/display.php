<?php $m = $this->model; ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
       <?php if ($m->facets || !$m->id()):?>
        <li>
            <h3>Display settings</h3>
            <hr/>
        </li>
        <li>
            <h4 class="label">Display as</h4>
            <select name="model[show]">
                <option <?=('' == $m->show)?'selected':''?> value="">---</option>
                <option <?=('checkbox' == $m->show)?'selected':''?> value="checkbox">Checkbox</option>
                <option <?=('link' == $m->show)?'selected':''?> value="link">Link</option>
            </select>
        </li>

        <li>
            <h4 class="label">Filter type</h4>
            <select name="model[filter]">
                <option <?=('' == $m->filter)?'selected':''?> value="">---</option>
                <option <?=('exclusive' == $m->filter)?'selected':''?> value="exclusive">Exclusive</option>
                <option <?=('inclusive' == $m->filter)?'selected':''?> value="inclusive">Inclusive</option>
            </select>
        </li>
        <?php else:?>
        <li>
            <h4 class="label">Required only for facets</h4>
        </li>
        <?php endif; ?>
    </ul>
</fieldset>
<script>
head(function() {
adminForm.wysiwygCreate('main-content');
});
</script>