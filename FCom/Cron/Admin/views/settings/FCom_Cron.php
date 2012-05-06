<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Area Settings</a></h3>
        <div>
            <table>
                <tr><td>IP: Mode</td><td><textarea name="config[modules][FCom_Cron][mode_by_ip]" style="width:400px; height:100px"><?php echo $this->q($c->get('modules/FCom_Cron/mode_by_ip')) ?></textarea></td></tr>
            </table>
        </div>
    </div>
    <div class="group">
        <h3><a href="#">Cron Dispatch</a></h3>
        <div>
            <table>
                <tr><td>Leeway Minutes</td><td><input type="text" name="config[modules][FCom_Cron][leeway_mins]" value="<?php echo $this->q($c->get('modules/FCom_Cron/leeway_mins'))?>"/></td></tr>
                <tr><td>Timeout Minutes</td><td><input type="text" name="config[modules][FCom_Cron][timeout_mins]" value="<?php echo $this->q($c->get('modules/FCom_Cron/timeout_mins'))?>"/></td></tr>
            </table>
        </div>
    </div>
</div>
</fieldset>