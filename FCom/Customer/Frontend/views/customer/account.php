<h2><?= BLocale::_("My account") ?></h2>

<p><?= BLocale::_("Hello") ?> <strong><?=$this->customer->firstname?></strong>
E-mail: <strong><?=$this->customer->email?></strong></p>
<p><a href="<?=BApp::href('customer/myaccount/edit')?>"><?= BLocale::_("Edit") ?></a><br/>
<a href="<?=BApp::href('customer/myaccount/editpassword')?>"><?= BLocale::_("Edit password") ?></a></p>

<br/>
<br/>
<a href="<?=BApp::href('customer/order')?>"><?= BLocale::_("Orders history") ?></a>

<br/>
<br/>
<a href="<?=BApp::href('customer/address')?>"><?= BLocale::_("View Addresses") ?></a><br/>