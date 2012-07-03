<?php foreach($this->addresses as $address): ?>
    <?php if ('s' == $this->type) :?>
        <?php if ($address->id == $this->customer->default_shipping_id): ?>
            <b>Default shipping address</b><br/>
        <?php endif; ?>
    <?php else :?>
        <?php if ($address->id == $this->customer->default_billing_id): ?>
            <b>Default billing address</b><br/>
        <?php endif; ?>
    <?php endif; ?>

    <?=FCom_Customer_Model_Address::as_html($address)?>
        <a href="<?=Bapp::href("customer/address/choose")?>?id=<?=$address->id?>&t=<?=$this->type?>">Select</a>
    <hr/>
<?php endforeach; ?>
