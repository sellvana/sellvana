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
