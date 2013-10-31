<?php $m = $this->model; ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h2 class="label">Label</h2>
            <input type="text" name="model[name]" value="<?php echo $this->q($m->name) ?>"/>
        </li>
         <li>
            <h2 class="label">Code</h2>
            <input type="text" name="model[code]" value="<?php echo $this->q($m->code) ?>"/>
        </li>
    </ul>
</fieldset>
<script>
require(['jquery', 'fcom.admin.form'], function($) {
    $(function() {
        //adminForm.wysiwygCreate('main-content');
    });
});
</script>
