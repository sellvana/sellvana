<?php $m = $this->model ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Handle</h4>
            <input type="text" name="model[handle]" value="<?php echo $this->q($m->handle) ?>"/>
        </li>
        <li>
            <h4 class="label">Title</h4>
            <input type="text" name="model[title]" value="<?php echo $this->q($m->title) ?>"/>
        </li>
        <li>
            <h4 class="label">Content</h4>
            <textarea id="main-content" name="model[content]"><?php echo $this->q($m->content) ?></textarea>
        </li>
        <li>
            <h4 class="label">Layout Update</h4>
            <textarea id="main-layout_update" name="model[layout_update]"><?php echo $this->q($m->layout_update) ?></textarea>
        </li>
        <li>
            <h4 class="label">Meta Title</h4>
            <input type="text" name="model[meta_title]" value="<?php echo $this->q($m->meta_title) ?>"/>
        </li>
        <li>
            <h4 class="label">Meta Description</h4>
            <textarea id="main-content" name="model[meta_description]"><?php echo $this->q($m->meta_description) ?></textarea>
        </li>
        <li>
            <h4 class="label">Meta Keywords</h4>
            <textarea id="main-content" name="model[meta_keywords]"><?php echo $this->q($m->meta_keywords) ?></textarea>
        </li>
    </ul>
</fieldset>
<script>
head(function() {
adminForm.wysiwygCreate('main-content');
});
</script>