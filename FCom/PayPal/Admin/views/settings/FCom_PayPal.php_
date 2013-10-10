<?php
$c = $this->model;
?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Production</a></h3>
        <div>
            <table>
                <tr>
                    <td>Username</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_PayPal][production][username]"
                               value="<?php echo $this->q($c->get('modules/FCom_PayPal/production/username'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Password</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_PayPal][production][password]"
                               value="<?php echo $this->q($c->get('modules/FCom_PayPal/production/password'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Signature</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_PayPal][production][signature]"
                               value="<?php echo $this->q($c->get('modules/FCom_PayPal/production/signature'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Show shipping address on PayPal</td>
                    <td>
                        <input type="radio" name="config[modules][FCom_PayPal][show_shipping]"
                              value="on" <?= 'on' == $c->get('modules/FCom_PayPal/show_shipping') ? 'checked':''?>/> Yes
                        <input type="radio" name="config[modules][FCom_PayPal][show_shipping]"
                              value="off" <?= 'off' == $c->get('modules/FCom_PayPal/show_shipping') ? 'checked':''?>/> No
                    </td>
                </tr>
            </table>
        </div>
        <h3><a href="#">Sandbox</a></h3>
        <div>
            <table>
                <tr>
                    <td>Sandbox mode</td>
                    <td>
                        <input type="radio" name="config[modules][FCom_PayPal][sandbox][mode]"
                              value="on" <?= 'on' == $c->get('modules/FCom_PayPal/sandbox/mode') ? 'checked':''?>/> Yes
                        <input type="radio" name="config[modules][FCom_PayPal][sandbox][mode]"
                              value="off" <?= 'off' == $c->get('modules/FCom_PayPal/sandbox/mode') ? 'checked':''?>/> No
                    </td>
                </tr>
                <tr>
                    <td>Username</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_PayPal][sandbox][username]"
                               value="<?php echo $this->q($c->get('modules/FCom_PayPal/sandbox/username'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Password</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_PayPal][sandbox][password]"
                               value="<?php echo $this->q($c->get('modules/FCom_PayPal/sandbox/password'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Signature</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_PayPal][sandbox][signature]"
                               value="<?php echo $this->q($c->get('modules/FCom_PayPal/sandbox/signature'))?>"/>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</fieldset>