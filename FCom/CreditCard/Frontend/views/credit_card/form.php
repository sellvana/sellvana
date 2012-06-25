    <b>Credit Card</b>
    Card Type:<br/>
    Visa <input type="radio" name="payment[card_type]" value="visa"
             <?=$this->paymentDetails['card_type']=='visa'?'checked':''?> />
    MasterCard <input type="radio" name="payment[card_type]" value="master_card"
            <?=$this->paymentDetails['card_type']=='master_card'?'checked':''?> />
    <br/>
    Card number: <input type="text" name="payment[card_number]" value="<?=$this->paymentDetails['card_number']?>" /><br/>
    Name on card: <input type="text" name="payment[name_on_card]" value="<?=$this->paymentDetails['name_on_card']?>" /><br/>
    Expires:
    <select id="expiration_month" name="payment[expiration_month]">
    <option value="">Choose...</option>
    <?php for($i = 0; $i <12; $i++):
        if ($i < 10) $i = '0'.$i; ?>
        <option value="<?=$i?>" <?= $i == $this->paymentDetails['expiration_month']?'selected':''?>><?=$i?></option>
    <?php endfor; ?>
    </select>
    <select id="expiration_year" name="payment[expiration_ year]">
    <option value="">Choose...</option>
    <?php for($i = date("Y"); $i < date("Y")+11; $i++) :?>
        <option value="<?=$i?>" <?= $i == $this->paymentDetails['expiration_ year']?'selected':''?>><?=$i?></option>
    <?php endfor; ?>
    </select>
    <br/>
    CVV: <input type="text" name="payment[cvv]" value="<?=$this->paymentDetails['cvv']?>" /><br/>
<input type="submit" name="update" value="Apply changes">