<a href="<?=Bapp::href("customer/address/edit")?>"><?= BLocale::_("Add new address") ?></a>
<br/>
<?php foreach($this->addresses as $address): ?>
    <?php if ('s' == $this->type) :?>
        <?php if ($address->id == $this->customer->default_shipping_id): ?>
            <strong><?= BLocale::_("Default shipping address") ?></strong><br/>
        <?php endif; ?>
    <?php else :?>
        <?php if ($address->id == $this->customer->default_billing_id): ?>
            <strong><?= BLocale::_("Default billing address") ?></strong><br/>
        <?php endif; ?>
    <?php endif; ?>

    <?=FCom_Customer_Model_Address::i()->as_html($address)?>
        <a href="<?=Bapp::href("customer/address/choose")?>?id=<?=$address->id?>&t=<?=$this->type?>"><?= BLocale::_("Select") ?></a>
    <hr/>
<?php endforeach; ?>
<br/>

