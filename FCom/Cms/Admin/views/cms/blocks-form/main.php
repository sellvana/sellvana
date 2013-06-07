<?php $m = $this->model ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Handle</h4>
            <input type="text" name="model[handle]" value="<?php echo $this->q($m->handle) ?>"/>
        </li>
        <li>
            <h4 class="label">Description</h4>
            <input type="text" name="model[description]" value="<?php echo $this->q($m->description) ?>"/>
        </li>
        <li>
            <h4 class="label">Content</h4>
            <textarea id="main-content" name="model[content]"><?php echo $this->q($m->content) ?></textarea>
        </li>
    </ul>
</fieldset>
<script>
$(function() {
    adminForm.wysiwygCreate('main-content');
});
</script>