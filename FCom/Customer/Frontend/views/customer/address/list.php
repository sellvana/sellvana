<a href="<?=Bapp::href("customer/address/edit")?>">Add new address</a>
<br/>

<?php foreach($this->addresses as $address): ?>
    <?php if ($address->id == $this->customer->default_shipping_id): ?>
        <b>Default shipping address</b><br/>
    <?php endif; ?>
    <?php if ($address->id == $this->customer->default_billing_id): ?>
        <b>Default billing address</b><br/>
    <?php endif; ?>

    <?=FCom_Customer_Model_Address::as_html($address)?>
        <a href="<?=Bapp::href("customer/address/edit")?>?id=<?=$address->id?>">Edit</a>
    <hr/>
<?php endforeach; ?>
