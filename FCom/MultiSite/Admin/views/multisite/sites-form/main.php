<?php $m = $this->model ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Name</h4>
            <input type="text" name="model[name]" value="<?php echo $this->q($m->name) ?>"/>
        </li>
        <li>
            <h4 class="label">Domains</h4>
            <textarea name="model[domains]"><?php echo $this->q($m->domains) ?></textarea>
        </li>
        <li>
            <h4 class="label">Default Theme</h4>
            <input type="text" name="model[default_theme]" value="<?php echo $this->q($m->default_theme) ?>"/>
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