<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Area Settings</a></h3>
        <div>
            <table>
                <tr><td>IP: Mode</td><td><textarea name="config[modules][FCom_Test][mode_by_ip]" style="width:400px; height:100px"><?php echo $this->q($c->get('modules/FCom_Test/mode_by_ip')) ?></textarea></td></tr>
            </table>
        </div>
    </div>
</div>
</fieldset>