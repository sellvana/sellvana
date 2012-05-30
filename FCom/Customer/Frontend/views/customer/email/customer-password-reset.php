<? $d = $this->customer ?>
<!--{ Content-Type: text/html; charset=UTF-8 }-->
<!--{ To: "<?=$d->firstname.' '.$d->lastname?>" <<?=$d->email?>> }-->
<!--{ From: "Fulleron" <support@fulleron.com> }-->
<!--{ Subject: Password reset confirmation }-->

<p>Hello, <?=$d->firstname?>.</p>

<p>Thank you, your password has been successfully reset.</p>
