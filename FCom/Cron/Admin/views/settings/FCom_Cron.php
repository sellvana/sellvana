<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Area Settings</a></h3>
        <div>
            <table>
                <tr><td>IP: Mode</td><td><textarea name="config[mode_by_ip][FCom_Cron]" style="width:400px; height:100px"><?php echo $this->q($c->get('mode_by_ip/FCom_Cron')) ?></textarea></td></tr>
            </table>
        </div>
    </div>
    <div class="group">
        <h3><a href="#">Cron Dispatch</a></h3>
        <div>
            <table>
                <tr><td>Leeway Minutes</td><td><input type="text" name="config[modules][FCom_Cron][leeway_mins]" value="<?php echo $this->q($c->get('modules/FCom_Cron/leeway_mins'))?>"/></td></tr>
                <tr><td>Timeout Minutes</td><td><input type="text" name="config[modules][FCom_Cron][timeout_mins]" value="<?php echo $this->q($c->get('modules/FCom_Cron/timeout_mins'))?>"/></td></tr>
                <tr><td>Wait Seconds</td><td><input type="text" name="config[modules][FCom_Cron][wait_secs]" value="<?php echo $this->q($c->get('modules/FCom_Cron/wait_secs'))?>"/></td></tr>
            </table>
        </div>
    </div>
</div>
</fieldset>