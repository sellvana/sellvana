<?php

class FCom_Email_Main extends BClass
{
    public static function onViewEmailBefore($args)
    {
        $email = $args['email_data']['to'];
        $pref = FCom_Email_Model_Pref::i()->load($email, 'email');
        return $pref->unsub_all ? false : true;
    }
}