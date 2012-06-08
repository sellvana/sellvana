Payment methods

<form action="<?=BApp::href('checkout/payment')?>" method="post">
    <h4>Payment method:</h4>
Credit card<br/>
Visa<br/>
<br/><br/>
<h4>Billing address</h4>
<a href="/checkout/address/billing">Change</a><br/>
<b>(Show active billing address if any )</b><br/>
Jon Doe<br/>
Some address here<br>
Some address here<br>
Some address here<br>

<b>(or show form for address)</b><br/>
As shipping: <input type="checkbox" name="address[billing_as_shipping]" value="1"> Yes<br/>
Address: <input type="text" name="address[shipping]" value=""><br/><br/>
    <input type="submit" value="continue to checkout">
</form>