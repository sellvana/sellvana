    <b><?= BLocale::_("Credit Card"); ?></b>
    <?= BLocale::_("Card Type"); ?>:<br/>
    Visa <input type="radio" name="payment[card_type]" value="visa" />
    MasterCard <input type="radio" name="payment[card_type]" value="master_card" />
    <br/>
    <?= BLocale::_("Card number"); ?>: <input type="text" name="payment[card_number]"  /><br/>
    <?= BLocale::_("Name on card"); ?>: <input type="text" name="payment[name_on_card]"  /><br/>
    <?= BLocale::_("Expires"); ?>:
    <select id="expiration_month" name="payment[expiration_month]">
    <option value=""><?= BLocale::_("Choose"); ?>...</option>
    <?php for($i = 0; $i <12; $i++):
        if ($i < 10) $i = '0'.$i; ?>
        <option value="<?=$i?>"><?=$i?></option>
    <?php endfor; ?>
    </select>
    <select id="expiration_year" name="payment[expiration_ year]">
    <option value=""><?= BLocale::_("Choose"); ?>...</option>
    <?php for($i = date("Y"); $i < date("Y")+11; $i++) :?>
        <option value="<?=$i?>"><?=$i?></option>
    <?php endfor; ?>
    </select>
    <br/>
    CVV: <input type="text" name="payment[cvv]" /><br/>
<input type="submit" name="update" value="<?= BLocale::_("Apply changes"); ?>">