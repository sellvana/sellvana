<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Area Settings</a></h3>
        <div>
            <table>
                <tr><td>IP: Mode</td><td><textarea name="config[modules][FCom_Frontend][mode_by_ip]" style="width:400px; height:100px"><?php echo $this->q($c->get('modules/FCom_Frontend/mode_by_ip')) ?></textarea></td></tr>
                <tr><td>Modules to run in RECOVERY mode</td><td><input type="text" name="config[modules][FCom_Frontend][recovery_modules]" value="<?php echo $this->q($c->get('modules/FCom_Frontend/recovery_modules'))?>"/></td></tr>
            </table>
        </div>
    </div>
    <div class="group">
        <h3><a href="#">HTML</a></h3>
        <div>
            <table>
                <tr><td>Theme</td><td><select name="config[modules][FCom_Frontend][theme]">
<?php echo $this->optionsHtml(BLayout::i()->getThemes('FCom_Frontend', true), $c->get('modules/FCom_Frontend/theme')) ?>
                </select></td></tr>
                <tr><td>Additional JS</td><td><textarea name="config[modules][FCom_Frontend][add_js]" style="width:400px; height:100px"><?php echo $this->q($c->get('modules/FCom_Frontend/add_js')) ?></textarea></td></tr>
                <tr><td>Additional CSS</td><td><textarea name="config[modules][FCom_Frontend][add_css]" style="width:400px; height:100px"><?php echo $this->q($c->get('modules/FCom_Frontend/add_css')) ?></textarea></td></tr>
            </table>
        </div>
    </div>
    <div class="group">
        <h3><a href="#">Navigation</a></h3>
        <div>
            <table>
                <tr><td>Select top menu</td>
                    <td>
                        <select name="config[modules][FCom_Frontend][nav_top][type]">
                            <option value="">Select an option</option>
                            <option value="cms" <?=$c->get('modules/FCom_Frontend/nav_top/type') == 'cms'?'selected':''?>>CMS menu</option>
                            <option value="categories" <?=$c->get('modules/FCom_Frontend/nav_top/type') == 'categories'?'selected':''?>>Categories menu</option>
                        </select>
                    </td></tr>
                <tr><td>Root id cms</td><td><input type="text" name="config[modules][FCom_Frontend][nav_top][root_cms]" value="<?php echo $this->q($c->get('modules/FCom_Frontend/nav_top/root_cms'))?>"/></td></tr>
                <tr><td>Root id categories</td><td><input type="text" name="config[modules][FCom_Frontend][nav_top][root_category]" value="<?php echo $this->q($c->get('modules/FCom_Frontend/nav_top/root_category'))?>"/></td></tr>
            </table>
        </div>
    </div>
</div>
</fieldset>