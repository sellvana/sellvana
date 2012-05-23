<?php $m = $this->model; ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h2 class="label">Field</h2>
            <?php if($m->id()):?>
                <input type="text" readonly="true" name="model[field_name]" value="<?php echo $this->q($m->field_name) ?>"/>
            <?php else:?>
                <input type="text" name="model[field_name]" value="<?php echo $this->q($m->field_name) ?>"/>
            <?php endif; ?>
        </li>

        <?php if ($m->facets || !$m->id()):?>
        <li>
            <h4 class="label">Label</h4>
            <input type="text" name="model[field_nice_name]" value="<?php echo $this->q($m->field_nice_name) ?>"/>
            (only for facets)
        </li>
        <?php endif; ?>

        <li>
            <h4 class="label">Search</h4>
            <input type="hidden" name="model[search]" value="0" checked />
            <input type="checkbox" name="model[search]" value="1" <?= $m->search ?'checked' : '' ?>/>
        </li>
        <li>
            <h4 class="label">Facets</h4>
            <input type="hidden" name="model[facets]" value="0" checked />
            <input type="checkbox" name="model[facets]" value="1" <?= $m->facets ?'checked' : '' ?>/>
        </li>
        <li>
            <h4 class="label">Scoring variable</h4>
            <input type="hidden" name="model[scoring]" value="0" checked />
            <input type="checkbox" name="model[scoring]" value="1" <?= $m->scoring ?'checked' : '' ?>/>
            (Scoring accept only float or integer type of variables. Text fields couldn't be used as scoring variable without transformation.)
        </li>

        <?php if ($m->search || !$m->id()):?>
        <li>
            <h4 class="label">Priority</h4>
            <input type="text" size="3" id="main-content" name="model[priority]" value="<?php echo $this->q($m->priority) ?>">
            (Default 1)
        </li>
        <?php endif; ?>

        <?php if ($m->scoring || !$m->id()):?>
        <li>
            <h4 class="label">Variable number</h4>
            <input type="text" size="3" name="model[var_number]" value="<?php echo $m->var_number ?>"/>
            (Start from 0)
        </li>
        <?php endif; ?>

        <li>
            <hr/>
            <h3>Additional fields (advanced)</h3>

        </li>
        <li>
            <h4 class="label">Source type</h4>
            <select name="model[source_type]">
                <option <?=('product' == $m->source_type)?'selected':''?> value="product">Product field</option>
                <option <?=('function' == $m->source_type)?'selected':''?> value="function">Function</option>
            </select>
            <?php if ('function' == $m->source_type):?>
                of FCom_IndexTank_Index_Product class
            <?php elseif('product' == $m->source_type):?>
                of fcom_product table
            <?php endif; ?>
        </li>
        <li>
            <h4 class="label">Source name</h4>
            <input type="text" name="model[source_value]" value="<?php echo $this->q($m->source_value) ?>"/>
        </li>


    </ul>

    <ul class="form-list">
       <?php if ($m->facets || !$m->id()):?>
        <li>
            <hr/>
            <h3>Display settings</h3>
        </li>
        <li>
            <h4 class="label">Filter type</h4>
            <select name="model[filter]">
                <option <?=('' == $m->filter)?'selected':''?> value="">---</option>
                <option <?=('exclusive' == $m->filter)?'selected':''?> value="exclusive">Exclusive</option>
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