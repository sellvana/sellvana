<?php $d = $this->customer ?>
<!--{ From: "<?=$d->firstname.' '.$d->lastname?>" <<?=$d->email?>> }-->
<!--{ To: "Fulleron" <support@fulleron.com> }-->
<!--{ Subject: New Customer Registration }-->

Hello,

A new customer registration:

Name: <?=$d->firstname.' '.$d->lastname?>

Email: <?=$d->email?>

Direct link to approve or decline:
<?=FCom_Frontend_Main::i()->adminHref('customers/form?id='.$d->id)?>

