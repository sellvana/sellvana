<?php $m = $this->model; ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h2 class="label">Frontend label</h2>
            <input type="text" name="model[label]" value="<?php echo $this->q($m->label) ?>"/>
        </li>
        <li>
            <h2 class="label">Name</h2>
            <input type="text" name="model[name]" value="<?php echo $this->q($m->name) ?>"/>
        </li>
        <li>
            <h2 class="label">Definition</h2>
            <input type="text" size="50" name="model[definition]" value="<?php echo $this->q($m->definition) ?>"/>
            (See <a href="http://www.indexden.com/documentation/function-definition">Scoring Function Syntax</a>)
        </li>

        <li>
            <?php if (!empty($m->scoring_fields)): ?>
             <i>You have following variables:</i><br/>
                <?php foreach($m->scoring_fields as $f):?>
                    d[<?=$f->var_number?>] - <?=$f->field_name?><br/>
                <?php endforeach; ?>
            <?php endif; ?>
        </li>
    </ul>
</fieldset>
<script>
head(function() {
adminForm.wysiwygCreate('main-content');
});
</script>