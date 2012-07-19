<?php
$c = $this->model;
?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Disqus settings</a></h3>
        <div>
            <table>
                <tr>
                    <td>Show on all pages</td>
                    <td>
                        <input type="checkbox" value="1"  name="config[modules][FCom_Disqus][show_on_all_pages]"
                               <?= $c->get('modules/FCom_Disqus/show_on_all_pages') == 1? 'checked': ''?> >
                    </td>
                </tr>
                <tr>
                    <td>Javascript code</td>
                    <td>
                        <textarea style="width:600px; height:200px"  name="config[modules][FCom_Disqus][code]"><?php echo $this->q($c->get('modules/FCom_Disqus/code'))?></textarea>
                    </td>
                </tr>

            </table>
        </div>
    </div>
</div>
</fieldset>