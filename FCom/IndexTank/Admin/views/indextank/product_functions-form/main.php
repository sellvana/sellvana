<?php $m = $this->model; ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h2 class="label">Name</h2>
            <input type="text" name="model[name]" value="<?php echo $this->q($m->name) ?>"/>
        </li>
        <li>
            <h4 class="label">Number</h4>
            <input type="text" size="3" id="main-content" name="model[number]" value="<?php echo $this->q($m->number) ?>">
            (Start from 0)
        </li>
        <li>
            <h2 class="label">Definition</h2>
            <input type="text" size="50" name="model[definition]" value="<?php echo $this->q($m->definition) ?>"/>
            (See <a href="http://www.indexden.com/documentation/function-definition">Scoring Function Syntax</a>)
        </li>
    </ul>
</fieldset>
<script>
head(function() {
adminForm.wysiwygCreate('main-content');
});
</script>