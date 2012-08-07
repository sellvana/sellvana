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
        <h3><a href="#">Session</a></h3>
        <div>
            <table>
                <tr><td>Session Handler</td><td><select name="config[cookie][session_handler]">
                    <option value="">Default</option>
                    <?php echo $this->optionsHtml(BSession::i()->getHandlers(), $c->get('cookie/session_handler')) ?>
                </select></td></tr>
                <tr><td>Cookie Timeout</td><td><input type="text" name="config[cookie][timeout]" value="<?php echo $this->q($c->get('cookie/timeout'))?>"/></td></tr>
                <tr><td>Cookie Domain</td><td><input type="text" name="config[cookie][domain]" value="<?php echo $this->q($c->get('cookie/domain'))?>"/></td></tr>
                <tr><td>Cookie Path</td><td><input type="text" name="config[cookie][path]" value="<?php echo $this->q($c->get('cookie/path'))?>"/></td></tr>
                <tr><td>Session Namespace</td><td><input type="text" name="config[cookie][session_namespace]" value="<?php echo $this->q($c->get('cookie/session_namespace'))?>"/></td></tr>
                <tr><td>Verify Session IP</td><td><select name="config[cookie][session_check_ip]">
                    <?php echo $this->optionsHtml(array(0=>'No',1=>'Yes'), $c->get('cookie/session_check_ip')) ?>
                </select></td></tr>
            </table>
        </div>
    </div>
    <div class="group">
        <h3><a href="#">System</a></h3>
        <div>
            <table>
                <tr><td>Hide script file name in URL</td><td><select name="config[web][hide_script_name]">
                    <?php echo $this->optionsHtml(array(0=>'No',1=>'Yes'), $c->get('web/hide_script_name')) ?>
                </select></td></tr>
            </table>
        </div>
    </div>
</div>
</fieldset>