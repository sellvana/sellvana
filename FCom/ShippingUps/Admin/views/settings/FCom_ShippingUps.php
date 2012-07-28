<?php
$c = $this->model;
$services = FCom_ShippingUps_Ups::i()->getServices();
?>
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
                <tr>
                    <td>Rate API URL</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_ShippingUps][rate_api_url]"
                               value="<?php echo $this->q($c->get('modules/FCom_ShippingUps/rate_api_url'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Shipping services</td>
                    <td>
                        <?php foreach($services as $sId => $sName): ?>
                            <input type="hidden" name="config[modules][FCom_ShippingUps][services][s<?=$sId?>]" value="0">
                            <input type="checkbox" name="config[modules][FCom_ShippingUps][services][s<?=$sId?>]"
                                   <?=$c->get('modules/FCom_ShippingUps/services/s'.$sId) == 1 ? 'checked': ''?> value="1">
                            <?=$sName?>
                            <br/>
                        <?php endforeach; ?>

                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</fieldset>