<a href="<?=Bapp::href("customer/address/edit")?>"><?= BLocale::_("Add new address") ?></a>
<br/>

<?php foreach($this->addresses as $address): ?>
    <?php if ($address->id == $this->customer->default_shipping_id): ?>
        </trong><?= BLocale::_("Default shipping address") ?></strong><br/>
    <?php endif; ?>
    <?php if ($address->id == $this->customer->default_billing_id): ?>
        </trong><?= BLocale::_("Default billing address") ?></strong><br/>
    <?php endif; ?>

    <?=FCom_Customer_Model_Address::i()->as_html($address)?>
        <a href="<?=Bapp::href("customer/address/edit")?>?id=<?=$address->id?>"><?= BLocale::_("Edit") ?></a>
    <hr/>
<?php endforeach; ?>
