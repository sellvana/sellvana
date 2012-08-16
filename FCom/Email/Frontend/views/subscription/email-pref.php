<?php
$r = BRequest::i();
$hlp = FCom_Email_Model_Pref::i();
$email = $r->get('email');
$validToken = $hlp->validateToken($email, $r->get('token'));
$pref = $hlp->load($email, 'email');
$formUrl = BUtil::setUrlQuery($r->currentUrl(), array('unsub_all'=>null));
?>
<header class="page-title">
    <h1><?= BLocale::_("Subscription Preferences") ?></h1>
</header>
<div class="main col1-layout">
    <div class="col-main">
        <?=$this->messagesHtml('frontend')?>

<?php if ($validToken): ?>
        <form method="post" action="<?=$formUrl?>">
            <fieldset>
                <p><?= BLocale::_("Your email") ?>: <strong><?=$this->q($email)?></strong></p>
                <input type="hidden" name="model[email]" value="<?=$this->q($email)?>"/>
                <p><label><input type="checkbox" name="model[unsub_all]" value="1"
                    <?=$pref && $pref->unsub_all || $r->get('unsub_all')?'checked':''?>
                /> <?= BLocale::_("Unsubscribe from all non-transactional emails") ?></label></p>

                <p><input type="submit" value="<?= BLocale::_("Save Preferences") ?>"/></p>
            </fieldset>
        </form>
<?php else: ?>

ERROR: <?= BLocale::_("Invalid token") ?>.

<?php endif ?>

    </div>
</div>