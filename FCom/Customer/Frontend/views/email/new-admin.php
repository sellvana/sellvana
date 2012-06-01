<? $d = $this->customer ?>
<!--{ From: "<?=$d->firstname.' '.$d->lastname?>" <<?=$d->email?>> }-->
<!--{ To: "Fulleron" <support@fulleron.com> }-->
<!--{ Subject: New Customer Registration }-->

Hello,

There has been a new story submitted:

Name: <?=$d->firstname.' '.$d->lastname?>

Email: <?=$d->email?>

Direct link to approve or decline:
<?=FCom_Frontend::adminHref('customers/form?id='.$d->id)?>

