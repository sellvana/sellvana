<?php

class FCom_Email_Admin extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
        BEvents::i()->on('BView::email.before', 'FCom_Email_Admin::onViewEmailBefore');
    }

    public static function onViewEmailBefore($args)
    {
        $email = $args['email_data']['to'];
        $pref = FCom_Email_Model_Pref::i()->load($email, 'email');
        return $pref->unsub_all ? false : true;
    }
}