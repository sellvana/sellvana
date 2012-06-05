<h2>Review the information below then click "Place your order"</h2>
<form action="<?=BApp::href('checkout')?>" method="post">
<input type="submit" name="place_order" value="Place your order">

<br/><br/>

<h4>Shipping to:</h4>
<a href="/checkout/address/shipping">Change</a><br/>
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
<a href="/cart">Need to change quantities or delete?</a><br/><br/>

<b>Estimated day delivery: June 07 2012</b>

<ul>
    <li>Product item #1</li>
    <li>Product item #2</li>
    <li>Product item #3</li>
</ul>
<br/><br/>
<h4>Shipping Summary:</h4>

Items: $10<br>
Shipping and handling: $0<br>
Total before tax: $10<br>
Estimated tax: $0<br>
<b>Order total: $10</b><br/><br/>

<b>Coupon, discount or promo code:</b>
<input type="text" name="coupon"> <input type="submit" name="coupon_submit" value="Apply">
<br/><br/>

<h4>Payment method:</h4>
<a href="/checkout/payment">Change</a><br/>
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

</form>