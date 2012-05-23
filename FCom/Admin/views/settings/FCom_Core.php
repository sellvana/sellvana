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
        <h3><a href="#">Website</a></h3>
        <div>
            <table>
                <tr><td>Store Name</td><td><input type="text" name="config[modules][FCom_Core][store_name]" value="<?php echo $this->q($c->get('modules/FCom_Core/store_name'))?>"/></td></tr>
                <tr><td>Admin Email</td><td><input type="text" name="config[modules][FCom_Core][admin_email]" value="<?php echo $this->q($c->get('modules/FCom_Core/admin_email'))?>"/></td></tr>
                <tr><td>Copyright message</td><td><input type="text" name="config[modules][FCom_Core][copyright]" value="<?php echo $this->q($c->get('modules/FCom_Core/copyright'))?>"/></td></tr>
            </table>
        </div>
    </div>
    <div class="group">
        <h3><a href="#">System</a></h3>
        <div>
            <table>

            </table>
        </div>
    </div>
</div>
</fieldset>