<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Area Settings</a></h3>
        <div>
            <table>
                <tr>
                    <td>Store location (zip)</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_Checkout][store_zip]"
                               value="<?php echo $this->q($c->get('modules/FCom_Checkout/store_zip'))?>"/>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</fieldset>