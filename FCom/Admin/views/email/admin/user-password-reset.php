<?php $u = $this->user; $c = BConfig::i() ?>
<!--{ To: "<?=$u->firstname.' '.$u->lastname?>" <<?=$u->email?>> }-->
<!--{ From: "<?=$c->get('modules/FCom_Core/store_name')?>" <<?=$c->get('modules/FCom_Core/admin_email')?>> }-->
<!--{ Subject: Password reset confirmation }-->

Hello <?=$u->firstname?>,

Thank you, your password has been successfully reset.