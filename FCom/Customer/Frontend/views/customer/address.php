<script type="text/javascript" src="http://fulleron.home/FCom/Core/js/lib/less.min.js?1333539941" ></script>

<form action="<?=BApp::href('customer/address')?>" method="post">
    <input type="hidden" name="address_equal" value="0">
    <input type="hidden" name="address_type" value="<?=$this->address_type?>">
    First name: <input type="text" name="firstname" value="<?=$this->address->firstname?>"><br/>
    Last name: <input type="text" name="lastname" value="<?=$this->address->firstname?>"><br/>
    Street 1: <input type="text" name="street1" value="<?=$this->address->street1?>"><br/>
    Street 2: <input type="text" name="street2" value="<?=$this->address->street2?>"><br/>
    City: <input type="text" name="city" value="<?=$this->address->city?>"><br/>

    <?=$this->view('geo/embed')?>
    <script>
        head(function() {
        $('.geo-country').geoCountryRegion({country:'<?=$this->address->country?>', region:'<?=$this->address->state?>'});
        })
    </script>
    <label for="#">Country<em class="required">*</em></label>
    <select class="geo-country" name="country" id="country">
        <option value="">Select an option</option>
    </select>

    <select class="geo-region required" name="state" >
        <option value="">Select an option</option>
    </select>
    <input type="text" class="geo-region" name="state" />

    <br/>
    Zip: <input type="text" name="zip" value="<?=$this->address->zip?>"><br/>


    <?php if ('shipping' == $this->address_type): ?>
        Billing address as shipping: <input type="checkbox" name="address_equal" value="1" <?=($this->address_equal?'checked':'')?>> Yes
    <?php elseif ('billing' == $this->address_type): ?>
        Shipping address as billing: <input type="checkbox" name="address_equal" value="1"<?=($this->address_equal?'checked':'')?>> Yes
    <?php endif; ?>
        <br/>

    <input type="submit" value="Save address">

</form>