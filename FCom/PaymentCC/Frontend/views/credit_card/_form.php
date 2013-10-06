    <h4><?= BLocale::_("Credit Card") ?></h4>
    <div class="control-group">
      <label for="#" class="control-label"><?= BLocale::_("Card Type") ?></label>
      <div class="controls">
   			<div class="radio">
   			  <label for="#">Visa 
   			    <input type="radio" name="payment[card_type]" value="visa" />
   			  </label>
   			</div>
   			<div class="radio">
   			  <label for="#">MasterCard 
   			    <input type="radio" name="payment[card_type]" value="master_card" />
   			  </label>
   			</div>
      </div>
    </div>
    <div class="control-group">
      <label for="#" class="control-label"><?= BLocale::_("Card number") ?></label>
      <div class="controls">
        <input type="text" name="payment[card_number]"  />
      </div>
    </div>
    <div class="control-group">
      <label for="#"><?= BLocale::_("Name on card") ?></label>
      <div class="controls">
        <input type="text" name="payment[name_on_card]"  />
      </div>
    </div>
    <div class="form-inline control-group">
      <label for="#"><?= BLocale::_("Expires") ?></label>
      <div class="controls">
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
		    </select>
      </div>
    </div>
    <div class="control-group">
      <label for="#"><?= BLocale::_("CVV") ?></label>
      <div class="controls">
        <input type="text" name="payment[cvv]" />
      </div>
    </div>
    <p><button type="submit" name="update" class="btn"><span><?= BLocale::_("Apply changes") ?></span></button></p>