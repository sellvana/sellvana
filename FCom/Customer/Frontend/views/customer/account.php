<h2><?= BLocale::_("My account") ?></h2>

<?= BLocale::_("Hello") ?> <b><?=$this->customer->firstname?></b><br/>
E-mail: <b><?=$this->customer->email?></b><br/>
<a href="<?=BApp::href('customer/myaccount/edit')?>"><?= BLocale::_("Edit") ?></a><br/>
<a href="<?=BApp::href('customer/myaccount/editpassword')?>"><?= BLocale::_("Edit password") ?></a>

<br/>
<br/>
<a href="<?=BApp::href('customer/order')?>"><?= BLocale::_("Orders history") ?></a>

<br/>
<br/>
<a href="<?=BApp::href('customer/address')?>"><?= BLocale::_("View Addresses") ?></a><br/>