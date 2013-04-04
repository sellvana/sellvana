<?php
/**
 * Created by pp
 * @project fulleron
 */
$m = $this->model;
$multiGroup = BModuleRegistry::i()->module('FCom_CustomerGroups')->runStatus(BNULL) == BModule::LOADED;
$tierCtrl = FCom_CustomerGroups_Admin_Controller_TierPrices::i();
// todo: add tier prices table, when you do that, populate price from each existing product
//
?>
<h2><?php echo BLocale::_("Price options");?></h2>
<div class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label"><?=BLocale::_("Cost")?></h4>
            <input value="0" type="text" name="cost">
        </li>
        <li>
            <h4 class="label"><?=BLocale::_("MSRP")?></h4>
            <input value="0" type="text" name="msrp">
        </li>
        <li>
            <h4 class="label"><?=BLocale::_("MAP")?></h4>
            <input value="0" type="text" name="map">
        </li>
        <?php if (!$multiGroup): ?>
        <li>
            <h4 class="label"><?=BLocale::_("Price")?></h4>
            <input type="text" name="model[base_price]" value="<?php echo $this->q($m->base_price) ?>"/>
        </li>
        <li>
            <h4 class="label"><?=BLocale::_("Sale Price")?></h4>
            <input value="0" type="text" name="sale_price">
        </li>
        <li>
            <h4 class="label"><?=BLocale::_("Markup")?></h4>
            <input value="0" type="text" name="markup">
        </li>
        <?php else:?>
            <!-- Show tier price table -->
            <div class="ui-layout-center">
                <?=$this->view('jqgrid')->set('config', $tierCtrl->getTierPricesGrid($m)) ?>
            </div>
            <!-- End tier price table -->
        <?php endif;?>
    </ul>
</div>