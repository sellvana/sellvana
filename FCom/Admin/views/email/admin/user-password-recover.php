<? $u = $this->user; $c = BConfig::i() ?>
<!--{ To: "<?=$u->firstname.' '.$u->lastname?>" <<?=$u->email?>> }-->
<!--{ From: "<?=$c->get('modules/FCom_Core/store_name')?>" <<?=$c->get('modules/FCom_Core/admin_email')?>> }-->
<!--{ Subject: Password reset instructions }-->

Hello, <?=$d->firstname?>.

Please go to this URL to reset your password:

<?=BApp::href('password/reset?token='.$u->token)?>