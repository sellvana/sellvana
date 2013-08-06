<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">API information</a></h3>
        <div>
            <table>
                <tr>
                    <td>API URL</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_MarketClient][market_url]"
                               value="<?php echo $this->q($c->get('modules/FCom_MarketClient/market_url'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>ID</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_MarketClient][id]"
                               value="<?php echo $this->q($c->get('modules/FCom_MarketClient/id'))?>"/> <br/>
                        <input type="button" onclick="location.href='http://fulleron.com/market/account'" value="Get one" />
                    </td>
                </tr>
                <tr>
                    <td>Salt</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_MarketClient][secret]"
                               value="<?php echo $this->q($c->get('modules/FCom_MarketClient/secret'))?>"/> <br/>
                        <span style="color:red">Keep ID and SECRET in secret</span>
                    </td>
                </tr>
            </table>
        </div>
        <h3><a href="#">FTP/SFTP settings: connection Information</a></h3>
        <div>
            <table>
                <tr>
                    <td></td>
                    <td>
                        To perform the requested action, Fulleron needs to access your web server.
                        Please enter your FTP credentials to proceed. If you do not remember your credentials, you should contact your web host.
                    </td>
                </tr>
                <tr>
                    <td>Enable FTP</td>
                    <td>
                        <input type="hidden" name="config[modules][FCom_MarketClient][ftp][enabled]" value="0"/>
                        <input type="checkbox" name="config[modules][FCom_MarketClient][ftp][enabled]" value="1"
                               <?= $c->get('modules/FCom_MarketClient/ftp/enabled') == 1 ? 'checked': ''?>/>
                    </td>
                </tr>
                <tr>
                    <td>Hostname</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_MarketClient][ftp][hostname]"
                               value="<?php echo $this->q($c->get('modules/FCom_MarketClient/ftp/hostname'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>FTP Username</td>
                    <td>
                        <input size="50" type="text" name="config[modules][FCom_MarketClient][ftp][username]"
                               value="<?php echo $this->q($c->get('modules/FCom_MarketClient/ftp/username'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>FTP Password</td>
                    <td>
                        <input size="50" type="password" name="config[modules][FCom_MarketClient][ftp][password]"
                               value="<?php echo $this->q($c->get('modules/FCom_MarketClient/ftp/password'))?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Connection Type</td>
                    <td>
                        <input type="radio" name="config[modules][FCom_MarketClient][ftp][type]"
                               value ="ftp"
                               <?=$c->get('modules/FCom_MarketClient/ftp/type') == 'ftp' ? 'checked': ''?>/> FTP
                        <input type="radio" name="config[modules][FCom_MarketClient][ftp][type]"
                               value ="ftps"
                               <?=$c->get('modules/FCom_MarketClient/ftp/type') == 'ftps' ||
                                       $c->get('modules/FCom_MarketClient/ftp/type') == '' ? 'checked': ''?>/> FTPS (SSL)
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</fieldset>
