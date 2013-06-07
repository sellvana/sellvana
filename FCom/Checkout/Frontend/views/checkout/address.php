<script type="text/javascript" src="http://fulleron.home/FCom/Core/js/lib/less.min.js?1333539941" ></script>

<form action="<?=BApp::href('checkout/address')?>" method="post" class="address-form" id="address-form">
    <input type="hidden" name="address_equal" value="0">
    <input type="hidden" name="t" value="<?=$this->address_type?>">
    <fieldset>
		<header class="page-title">
			<h1 class="title"><?= BLocale::_("Shipping Address") ?></h1>
		</header>
    	<table class="form-group row-label">
    		<col width="50%"/>
    		<col width="50%"/>
    		<tr>
    			<td>
    				<label for="#"><?= BLocale::_("First name") ?></label>
		    		<input type="text" name="firstname" value="<?=$this->address->firstname?>" class="required">
		    	</td>
		    	<td>
		    		<label for="#"><?= BLocale::_("Last name") ?></label>
		    		<input type="text" name="lastname" value="<?=$this->address->firstname?>" class="required">
		    	</td>
		    </tr>
    		<tr>
    			<td>
    				<label for="#"><?= BLocale::_("E-mail") ?></label>
		    		<input type="text" name="email" value="<?=$this->address->email?>" class="required">
		    	</td>
		    	<td>&nbsp;</td>
		    </tr>
		    <tr>
    			<td colspan="2">
    				<label for="#"><?= BLocale::_("Street 1") ?></label>
		    		<input type="text" name="street1" value="<?=$this->address->street1?>" class="required">
		    	</td>
		    </tr>
		    <tr>
		    	<td colspan="2"><label for="#"><?= BLocale::_("Street 2") ?></label>
		    	<input type="text" name="street2" value="<?=$this->address->street2?>"></td>
		    </tr>
    		<tr>
    			<td>
    				<label for="#"><?= BLocale::_("City") ?></label>
		    		<input type="text" name="city" value="<?=$this->address->city?>" class="required">
		    	</td>
		    	<td>
				    <?=$this->view('geo/embed')?>
				    <script>
				        $(function() {
				            $('.geo-country').geoCountryRegion({country:'<?=$this->address->country?>', region:'<?=$this->address->region?>'});
				        })
				    </script>
		    		<label for="#"><?= BLocale::_("Region") ?></label>
		    		<select class="geo-region required" name="region"  class="required">
				        <option value=""><?= BLocale::_("Select an option") ?></option>
				    </select>
				    <input type="text" class="geo-region" name="region"  class="required"/>
		    	</td>
		    </tr>
    		<tr>
    			<td>
		  			<label for="#"><?= BLocale::_("Country") ?><em class="required">*</em></label>
		    		<select class="geo-country" name="country" id="country" class="required">
		        		<option value=""><?= BLocale::_("Select an option") ?></option>
		    		</select>
		    	</td>
		    	<td>
		    		<label for="#"><?= BLocale::_("Zip / Postal Code") ?></label>
		    		<input type="text" name="postcode" value="<?=$this->address->postcode?>" class="required">
		    	</td>
		    </tr>
		</table>
		<?php if ('s' == $this->address_type): ?>
		<p class="checkbox-row">
			<label for="address_equal">
				<input type="checkbox" id="address_equal" name="address_equal" value="1" <?=($this->address_equal?'checked':'')?>>
				<?= BLocale::_("Billing address as shipping") ?>: Yes</label>
		</p>
		<?php endif; ?>
    	<button class="button" type="submit"><span><?= BLocale::_("Save address") ?></span></button>
	</fieldset>
</form>
<script>
$(function() {
    $('#address-form').validate();
})
</script>