<?php

class FCom_Email_Frontend extends BClass
{
    public static function bootstrap()
    {
        BFrontController::i()
            ->route('GET|POST /email/pref', 'FCom_Email_Frontend_Controller.pref')
        ;

        BLayout::i()
            ->addAllViews('Frontend/views')
            ->afterTheme('FCom_Email_Frontend::layout')
        ;

        BPubSub::i()->on('BView::email.before', 'FCom_Email_Admin::onViewEmailBefore');
    }

    public static function onViewEmailBefore($args)
    {
        $email = $args['email_data']['to'];
        $pref = FCom_Email_Model_Pref::i()->load($email, 'email');
        return $pref->unsub_all ? false : true;
    }


    public static function layout()
    {
        BLayout::i()->addLayout(array(
            '/email/pref' => array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('subscription/email-pref')),
            ),
        ));
    }
}
