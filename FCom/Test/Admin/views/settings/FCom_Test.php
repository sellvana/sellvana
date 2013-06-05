<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Area Settings</a></h3>
        <div>
            <table>
                <tr><td>IP: Mode</td><td><textarea name="config[mode_by_ip][FCom_Test]" style="width:400px; height:100px"><?php echo $this->q($c->get('mode_by_ip/FCom_Test')) ?></textarea></td></tr>
            </table>
        </div>
    </div>
</div>
</fieldset>