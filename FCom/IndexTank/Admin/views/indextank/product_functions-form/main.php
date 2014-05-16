<?php $m = $this->model; ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h2 class="label">Frontend label</h2>
            <input type="text" name="model[label]" value="<?php echo $this->q($m->label) ?>"/>
        </li>
        <li>
            <h2 class="label">Sorting field</h2>
            <?php if (!empty($m->scoring_fields)): ?>
                <select name="model[field_name]">
                    <option value="">--</option>
                    <?php foreach ($m->scoring_fields as $f):?>
                        <option value="<?=$f->field_name?>" <?=$f->field_name == $m->field_name ? 'selected' : ''?> > <?=$f->field_nice_name?></option>
                    <?php endforeach; ?>
                </select>
            <?php else :?>
             No sorting fields exist. Please open IndexTank Product fields and choose necessary sorting fields.
            <?php endif;?>
        </li>
        <li>
            <h2 class="label">Sort order</h2>
            <select name="model[sort_order]">
                <option value="asc" <?=$m->sort_order == 'asc' ? 'selected' : ''?>>Ascending</option>
                <option value="desc" <?=$m->sort_order == 'desc' ? 'selected' : ''?>>Descending</option>
            </select>
        </li>

        <li>
            <hr/>
        </li>
        <li>
            <h2 class="label">Use custom formula</h2>
            <input type="hidden" name="model[use_custom_formula]" value="0"/>
            <input type="checkbox" name="model[use_custom_formula]" value="1" <?=1 == $m->use_custom_formula ? 'checked' : ''?>/> Yes
        </li>
        <li>
            <h2 class="label">Custom formula (only for experienced users)</h2>
            <input type="text" size="50" name="model[definition]" value="<?php echo $this->q($m->definition) ?>"/>
            (See <a href="http://www.indexden.com/documentation/function-definition">Scoring Function Syntax</a>)
        </li>
         <li>
            <h2 class="label">Custom name</h2>
            <input type="text" name="model[name]" value="<?php echo $this->q($m->name) ?>"/>
        </li>
    </ul>
</fieldset>
<script>
require(['jquery', 'fcom.admin.form'], function($) {
    $(function() {
        //adminForm.wysiwygCreate('main-content');
    });
})
</script>
