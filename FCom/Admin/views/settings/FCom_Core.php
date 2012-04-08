<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">DB Settings</a></h3>
        <div>
            <table>
                <tr><td>Host</td><td><input type="text" name="config[db][host]" value="<?php echo $this->q($c->get('db/host'))?>"/></td></tr>
                <tr><td>DB Name</td><td><input type="text" name="config[db][dbname]" value="<?php echo $this->q($c->get('db/dbname'))?>"/></td></tr>
                <tr><td>User</td><td><input type="text" name="config[db][username]" value="<?php echo $this->q($c->get('db/username'))?>"/></td></tr>
                <tr><td>Password</td><td><input type="text" name="config[db][password]" value="*****"/></td></tr>
                <tr><td>Enable Logging</td><td><select name="config[db][logging]">
                    <?php echo $this->optionsHtml(array(0=>'No',1=>'Yes'), $c->get('db/logging')) ?>
                </select></td></tr>
                <tr><td>Implicit Migration</td><td><select name="config[db][implicit_migration]">
                    <?php echo $this->optionsHtml(array(0=>'No',1=>'Yes'), $c->get('db/implicit_migration')) ?>
                </select></td></tr>
            </table>
        </div>
    </div>
    <div class="group">
        <h3><a href="#">System</a></h3>
        <div>
            <table>
                <tr><td>Application Mode</td><td><select name="config[debug][mode]">
                    <?php echo $this->optionsHtml(array(
                        'DEBUG'       => 'DEBUG',
                        'DEVELOPMENT' => 'DEVELOPMENT',
                        'STAGING'     => 'STAGING',
                        'PRODUCTION'  => 'PRODUCTION',
                        //'MIGRATION'   => 'MIGRATION',
                        'RECOVERY'    => 'RECOVERY',
                    ), $c->get('debug/mode')) ?>
                </select></td></tr>
                <tr><td>Modules to run in RECOVERY mode</td><td><input type="text" name="config[modules][FCom_Core][recovery_modules]" value="<?php echo $this->q($c->get('modules/FCom_Core/recovery_modules'))?>"/></td></tr>
            </table>
        </div>
    </div>
</div>
</fieldset>