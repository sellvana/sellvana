<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Area Settings</a></h3>
        <div>
            <table>
                <tr>
                    <td>IndexDen API URL</td>
                    <td>
                        <input type="text" name="config[modules][FCom_IndexTank][api_url]" value="<?php echo $this->q($c->get('modules/FCom_IndexTank/api_url'))?>"/>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</fieldset>