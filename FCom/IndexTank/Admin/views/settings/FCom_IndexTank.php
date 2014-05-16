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
                        <input size="50" type="text" name="config[modules][FCom_IndexTank][api_url]" value="<?php echo $this->q($c->get('modules/FCom_IndexTank/api_url'))?>"/> <br/>
                        <input type="button" onclick="location.href='http://indexden.com/pricing'" value="Get one" />
                    </td>
                </tr>
                <tr>
                    <td>Index name</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_IndexTank][index_name]" value="<?php echo $this->q($c->get('modules/FCom_IndexTank/index_name'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Save current filters on new search query</td>
                    <td>
                        <input type="hidden" name="config[modules][FCom_IndexTank][save_filter]" value="0" />
                        <input size="50" type="checkbox" name="config[modules][FCom_IndexTank][save_filter]"
                               <?=($c->get('modules/FCom_IndexTank/save_filter') == 1) ? 'checked' : ''?> value="1"/>
                    </td>
                </tr>
                <tr>
                    <td>How many product index per minute via cron (optimal 1000):</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_IndexTank][index_products_limit]" value="<?php echo $this->q($c->get('modules/FCom_IndexTank/index_products_limit'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Disable auto-indexing</td>
                    <td>
                        <input type="hidden" name="config[modules][FCom_IndexTank][disable_auto_indexing]" value="0" />
                        <input size="50" type="checkbox" name="config[modules][FCom_IndexTank][disable_auto_indexing]"
                               <?=($c->get('modules/FCom_IndexTank/disable_auto_indexing') == 1) ? 'checked' : ''?> value="1"/>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</fieldset>