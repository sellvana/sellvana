<h2>Payment is done successfully</h2>

Order #<a href="<?=BApp::href('customer/order/view').'?id='.$this->order->id()?>"><?=$this->order->id()?></a>
<br/>
Billing information:<br/>
<?=$this->order->billing()->firstname?> <?=$this->order->billing()->lastname?> (<?=$this->order->billing()->email?>)