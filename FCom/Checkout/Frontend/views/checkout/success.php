<h2>Payment is done successfully</h2>
<?php if ($this->user) :?>
Order #<a href="<?=BApp::href('customer/order/view').'?id='.$this->order->id()?>"><?=$this->order->id()?></a>
<br/>
<?php endif; ?>
Billing information:<br/>
<?=$this->order->billing()->firstname?> <?=$this->order->billing()->lastname?> (<?=$this->order->billing()->email?>)