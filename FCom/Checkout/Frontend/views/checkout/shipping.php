<form action="<?=BApp::href('checkout/shipping')?>" method="post">

<h4>Shipping to:</h4>
<b>(Show active shipping address if any )</b><br/>
Jon Doe<br>
Some address here<br>
Some address here<br>
Some address here<br>
Some address here<br><br>

<b>(or show form for address)</b><br/>
Address: <input type="text" name="address[shipping]" value=""><br/><br/>


<h4>Shipping Options:</h4>

<ul>
    <li><input type="radio" name="shipping_method">Free Standard Shipping (3-5 days)</li>
    <li><input type="radio" name="shipping_method">UPS Shipping (2 days)</li>
    <li><input type="radio" name="shipping_method">Fedex Shipping (2 days)</li>
</ul>

 <input type="submit" value="continue to payment">
</form>