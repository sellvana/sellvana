<h2>My account</h2>

Hello <b><?=$this->customer->firstname?></b><br/>
E-mail: <b><?=$this->customer->email?></b><br/>
<a href="<?=BApp::href('customer/myaccount/edit')?>">Edit</a><br/>
<a href="<?=BApp::href('customer/myaccount/password')?>">Edit password</a>
