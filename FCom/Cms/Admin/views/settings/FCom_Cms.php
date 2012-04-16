<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">CMS Frontend Configuration</a></h3>
        <div>
            <table>
                <tr><td>Enable CMS Pages</td><td><select name="config[modules][FCom_Cms][page_enable]">
                    <?php echo $this->optionsHtml(array(0=>'No',1=>'Yes'), $c->get('modules/FCom_Cms/page_enable')) ?>
                </select></td></tr>
                <tr><td>CMS Page URL Prefix</td><td><input type="text" name="config[modules][FCom_Cms][page_url_prefix]" value="<?php echo $this->q($c->get('modules/FCom_Cms/page_url_prefix'))?>"/></td></tr>

                <tr><td>Enable CMS Nav</td><td><select name="config[modules][FCom_Cms][nav_enable]">
                    <?php echo $this->optionsHtml(array(0=>'No',1=>'Yes'), $c->get('modules/FCom_Cms/nav_enable')) ?>
                </select></td></tr>
                <tr><td>CMS Nav URL Prefix</td><td><input type="text" name="config[modules][FCom_Cms][nav_url_prefix]" value="<?php echo $this->q($c->get('modules/FCom_Cms/nav_url_prefix'))?>"/></td></tr>
            </table>
        </div>
    </div>
</div>
</fieldset>