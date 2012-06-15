<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Area Settings</a></h3>
        <div>
            <table>
                <tr>
                    <td>Access Key</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_ShippingUps][access_key]"
                               value="<?php echo $this->q($c->get('modules/FCom_ShippingUps/access_key'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>UPS Account</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_ShippingUps][account]"
                               value="<?php echo $this->q($c->get('modules/FCom_ShippingUps/account'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Password</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_ShippingUps][password]"
                               value="<?php echo $this->q($c->get('modules/FCom_ShippingUps/password'))?>"/>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</fieldset>