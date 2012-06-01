<? $d = $this->customer ?>
<!--{ Content-Type: text/html; charset=UTF-8 }-->
<!--{ To: "<?=$d->firstname.' '.$d->lastname?>" <<?=$d->email?>> }-->
<!--{ From: "Fulleron" <support@fulleron.com> }-->
<!--{ Subject: Password reset instructions }-->


<p>Hello, <?=$d->firstname?>.</p>

<p>Please go to this URL to reset your password:</p>

<p><a href="<?=BApp::href('customer/password/reset?token='.$d->token)?>"><?=BApp::href('customer/password/reset?token='.$d->token)?></a></p>
