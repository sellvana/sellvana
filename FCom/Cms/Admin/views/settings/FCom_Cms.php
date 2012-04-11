<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">CMS</a></h3>
        <div>
            <table>
                <tr><td>CMS URL Prefix</td><td><input type="text" name="config[modules][FCom_Cms][url_prefix]" value="<?php echo $this->q($c->get('modules/FCom_Cms/url_prefix'))?>"/></td></tr>
            </table>
        </div>
    </div>
</div>
</fieldset>