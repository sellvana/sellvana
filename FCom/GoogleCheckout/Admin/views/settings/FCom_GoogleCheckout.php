<?php
/* @var $this FCom_Admin_View_Default */
/* @var $c BConfig */
$c = $this->model;
$merchantId = $c->get('modules/FCom_GoogleCheckout/sandbox/merchant_id') ? rawurlencode($c->get('modules/FCom_GoogleCheckout/sandbox/merchant_id')) :'1234567890';
$btnSandBoxUrl = $c->get('modules/FCom_GoogleCheckout/sandbox/button_url')?:'sandbox.google.com/checkout/buttons/checkout.gif';
$btnSandBoxUrl = "http://" . $btnSandBoxUrl;
?>
<h2><?php echo BLocale::_("Google Checkout Settings");?></h2>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Production</a></h3>
        <div>
            <table>
                <tr>
                    <td>Google merchant ID</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_GoogleCheckout][production][merchant_id]"
                               value="<?php echo $this->q($c->get('modules/FCom_GoogleCheckout/production/merchant_id'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Google merchant key</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_GoogleCheckout][production][merchant_key]"
                               value="<?php echo $this->q($c->get('modules/FCom_GoogleCheckout/production/merchant_key'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>API Url</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_GoogleCheckout][production][url]"
                               value="<?php echo $this->q($c->get('modules/FCom_GoogleCheckout/production/url'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Button URL</td>
                    <td><input size="50" type="text" name="config[modules][FCom_GoogleCheckout][production][button_url]"
                            value="<?php echo $this->q($c->get('modules/FCom_GoogleCheckout/production/button_url')); ?>"></td>
                </tr>
            </table>
        </div>
        <h3><a href="#">Sandbox</a></h3>
        <div>
            <table>
                <tr>
                    <td>Sandbox mode</td>
                    <td>
                        <input type="radio" name="config[modules][FCom_GoogleCheckout][sandbox][mode]"
                              value="on" <?= 'on' == $c->get('modules/FCom_GoogleCheckout/sandbox/mode') ? 'checked':''?>/> Yes
                        <input type="radio" name="config[modules][FCom_GoogleCheckout][sandbox][mode]"
                              value="off" <?= 'off' == $c->get('modules/FCom_GoogleCheckout/sandbox/mode') ? 'checked':''?>/> No
                    </td>
                </tr>
                <tr>
                    <td>Google merchant ID</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_GoogleCheckout][sandbox][merchant_id]"
                               value="<?php echo $this->q($c->get('modules/FCom_GoogleCheckout/sandbox/merchant_id'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Google merchant key</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_GoogleCheckout][sandbox][merchant_key]"
                               value="<?php echo $this->q($c->get('modules/FCom_GoogleCheckout/sandbox/merchant_key'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>API Url</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_GoogleCheckout][sandbox][url]"
                               value="<?php echo $this->q($c->get('modules/FCom_GoogleCheckout/sandbox/url'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Button URL</td>
                    <td><input size="50" type="text" name="config[modules][FCom_GoogleCheckout][sandbox][button_url]"
                            value="<?php echo $this->q($c->get('modules/FCom_GoogleCheckout/sandbox/button_url')); ?>"></td>
                </tr>
            </table>
        </div>
        <h3><a href="#">Buttons</a></h3>
        <div>
            <table>
                <tr>
                    <td>Size</td>
                    <td>
                        <label><input type="radio" name="config[modules][FCom_GoogleCheckout][button][size]"
                               value="180x46" <?= '180x46' == $c->get('modules/FCom_GoogleCheckout/button/size') ? 'checked' : '' ?>>&nbsp;
                            <img src="<?=$btnSandBoxUrl?>?merchant_id=<?=$merchantId?>&w=180&h=46&style=white&variant=text&loc=en_US">
                            Large: 180 by 46
                        </label><br>
                        <label><input type="radio" name="config[modules][FCom_GoogleCheckout][button][size]"
                                      value="168x44" <?= '168x44' == $c->get('modules/FCom_GoogleCheckout/button/size') ? 'checked' : '' ?>>&nbsp;
                            <img src="<?=$btnSandBoxUrl?>?merchant_id=<?=$merchantId?>&w=168&h=44&style=white&variant=text&loc=en_US">
                            Medium: 168 by 44
                        </label><br>

                        <label><input type="radio" name="config[modules][FCom_GoogleCheckout][button][size]"
                                      value="160x43" <?= '160x43' == $c->get('modules/FCom_GoogleCheckout/button/size') ? 'checked' : '' ?>>&nbsp;
                            <img src="<?=$btnSandBoxUrl?>?merchant_id=<?=$merchantId?>&w=160&h=43&style=white&variant=text&loc=en_US">
                            Small: 160 by 43
                        </label>
                    </td>
                </tr>
                <tr>
                    <td>Style</td>
                    <td>
                        <label><input type="radio" name="config[modules][FCom_GoogleCheckout][button][style]"
                                      value="white" <?= 'white' == $c->get('modules/FCom_GoogleCheckout/button/style') ? 'checked' : '' ?>>&nbsp;
                            <img src="<?=$btnSandBoxUrl?>?merchant_id=<?=$merchantId?>&w=180&h=46&style=white&variant=text&loc=en_US">
                            White</label><br>
                        <label><input type="radio" name="config[modules][FCom_GoogleCheckout][button][style]"
                                      value="trans" <?= 'trans' == $c->get('modules/FCom_GoogleCheckout/button/style') ? 'checked' : '' ?>>&nbsp;
                            <img src="<?=$btnSandBoxUrl?>?merchant_id=<?=$merchantId?>&w=180&h=46&style=trans&variant=text&loc=en_US">
                            Transparent</label>
                    </td>
                </tr>
                <tr>
                    <td>Location</td>
                    <td>
                        <label><input type="radio" name="config[modules][FCom_GoogleCheckout][button][loc]"
                                      value="en_US" <?= 'en_US' == $c->get('modules/FCom_GoogleCheckout/button/loc') ? 'checked' : '' ?>>&nbsp;
                            <img src="<?=$btnSandBoxUrl?>?merchant_id=<?=$merchantId?>&w=180&h=46&style=white&variant=text&loc=en_US">
                            USA</label><br>
                        <label><input type="radio" name="config[modules][FCom_GoogleCheckout][button][loc]"
                                      value="en_GB" <?= 'en_GB' == $c->get('modules/FCom_GoogleCheckout/button/loc') ? 'checked' : '' ?>>&nbsp;
                            <img src="<?=$btnSandBoxUrl?>?merchant_id=<?=$merchantId?>&w=180&h=46&style=white&variant=text&loc=en_GB">
                            UK</label>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</fieldset>