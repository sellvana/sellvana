<?php $m = $this->model ?>
<fieldset>
    <ul class="form-list">
        <li class="label-l"><label for="main-node_name">Node Name</label>
            <input type="text" id="main-node_name" name="model[node_name]" value="<?php echo $this->q($m->node_name) ?>"></li>

        <li class="label-l"><label for="main-node_name">URL Key</label>
            <input type="text" id="main-url_key" name="model[url_key]" value="<?php echo $this->q($m->url_key) ?>"></li>
    </ul>
</fieldset>