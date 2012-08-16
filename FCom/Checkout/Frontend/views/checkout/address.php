<script type="text/javascript" src="http://fulleron.home/FCom/Core/js/lib/less.min.js?1333539941" ></script>

<form action="<?=BApp::href('checkout/address')?>" method="post">
    <input type="hidden" name="address_equal" value="0">
    <input type="hidden" name="t" value="<?=$this->address_type?>">
    <?= BLocale::_("First name") ?>: <input type="text" name="firstname" value="<?=$this->address->firstname?>"><br/>
    <?= BLocale::_("Last name") ?>: <input type="text" name="lastname" value="<?=$this->address->firstname?>"><br/>
    <?= BLocale::_("Street 1") ?>: <input type="text" name="street1" value="<?=$this->address->street1?>"><br/>
    <?= BLocale::_("Street 2") ?>: <input type="text" name="street2" value="<?=$this->address->street2?>"><br/>
    <?= BLocale::_("City") ?>: <input type="text" name="city" value="<?=$this->address->city?>"><br/>
    E-mail: <input type="text" name="email" value="<?=$this->address->email?>"><br/>

    <?=$this->view('geo/embed')?>
    <script>
        head(function() {
        $('.geo-country').geoCountryRegion({country:'<?=$this->address->country?>', region:'<?=$this->address->state?>'});
        })
    </script>
    <label for="#"><?= BLocale::_("Country") ?><em class="required">*</em></label>
    <select class="geo-country" name="country" id="country">
        <option value=""><?= BLocale::_("Select an option") ?></option>
    </select>

    <select class="geo-region required" name="state" >
        <option value=""><?= BLocale::_("Select an option") ?></option>
    </select>
    <input type="text" class="geo-region" name="state" />

    <br/>
    <?= BLocale::_("Zip") ?>: <input type="text" name="zip" value="<?=$this->address->zip?>"><br/>


    <?php if ('s' == $this->address_type): ?>
        <?= BLocale::_("Billing address as shipping") ?>: <input type="checkbox" name="address_equal" value="1" <?=($this->address_equal?'checked':'')?>> Yes
    <?php endif; ?>
        <br/>

    <input type="submit" value="<?= BLocale::_("Save address") ?>">

</form>