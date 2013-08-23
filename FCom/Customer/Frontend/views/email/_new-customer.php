<?php $d = $this->customer ?>
<!--{ Content-Type: text/html; charset=UTF-8 }-->
<!--{ From: "Fulleron" <support@fulleron.com> }-->
<!--{ To: "<?=$d->firstname.' '.$d->lastname?>" <<?=$d->email?>> }-->
<!--{ Subject: Your registration has been submitted for approval. }-->

Hello, <?=$d->firstname?>.

<p>Thank you for your registration</p>