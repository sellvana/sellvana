<?php $m = $this->model; ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Title </h4>
            <input type="text" name="model[title]" value="<?php echo $this->q($m->title) ?>"/>
        </li>
        <li>
            <h4 class="label">Text </h4>
            <textarea name="model[text]" rows="5" cols="35"><?php echo $this->q($m->text) ?></textarea>
        </li>
        <li>
            <h4 class="label">Rating of product</h4>
            <input type="text" name="model[rating]" value="<?php echo $this->q($m->rating) ?>"/>
        </li>
        <li>
            <h4 class="label">Helpful points </h4>
            <input type="text" name="model[helpful]" value="<?php echo $this->q($m->helpful) ?>"/>
        </li>
        <li>
            <h4 class="label">Number of voices for helpful mark</h4>
            <input type="text" name="model[helpful_voices]" value="<?php echo $this->q($m->helpful_voices) ?>"/>
        </li>
        <li>
            <h4 class="label">Approved</h4>
            <input type="hidden" name="model[approved]" value="0">
            <input type="checkbox" name="model[approved]" value="1" <?= $m->approved == 1 ?'checked': '' ?>/>
        </li>
    </ul>
</fieldset>
