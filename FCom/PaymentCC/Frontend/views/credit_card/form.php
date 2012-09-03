    <h4><?= BLocale::_("Credit Card") ?></h4>
    <ul class="form-group row-label">
    	<li><label for="#"><?= BLocale::_("Card Type") ?></label>
   			<label for="#">Visa <input type="radio" name="payment[card_type]" value="visa" /></label>
    		<label for="#">MasterCard <input type="radio" name="payment[card_type]" value="master_card" /></label></li>
    	<li><label for="#"><?= BLocale::_("Card number") ?></label>
    		<input type="text" name="payment[card_number]"  /></li>
    	<li><label for="#"><?= BLocale::_("Name on card") ?></label>
    		<input type="text" name="payment[name_on_card]"  /></li>
    	<li><label for="#"><?= BLocale::_("Expires") ?></label>	
		    <select id="expiration_month" name="payment[expiration_month]" class="month">
		    <option value=""><?= BLocale::_("Choose") ?>...</option>
		    <?php for($i = 0; $i <12; $i++):
		        if ($i < 10) $i = '0'.$i; ?>
		        <option value="<?=$i?>"><?=$i?></option>
		    <?php endfor; ?>
		    </select>
		    <select id="expiration_year" name="payment[expiration_ year]" class="year">
		    <option value=""><?= BLocale::_("Choose") ?>...</option>
		    <?php for($i = date("Y"); $i < date("Y")+11; $i++) :?>
		        <option value="<?=$i?>"><?=$i?></option>
		    <?php endfor; ?>
		    </select></li>
    	<li><label for="#">CVV</label>
    		<input type="text" name="payment[cvv]" /></li>
  	</ul>
	<p><button type="submit" name="update" class="button btn-aux btn-sz1"><span><?= BLocale::_("Apply changes") ?></span></button></p>