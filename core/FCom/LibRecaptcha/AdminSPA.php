<?php

use FCom_AdminSPA_AdminSPA_Controller_Abstract as Ctrl;

class FCom_LibRecaptcha_AdminSPA extends BClass
{
    public function onSettingsConfig($args)
    {
        $args['navs'] = array_merge_recursive($args['navs'], [
            '/other/recaptcha' => [Ctrl::LABEL => 'Google reCaptcha', 'pos' => 100],
            '/other/recaptcha/api' => [Ctrl::LABEL => 'API Settings', 'pos' => 10],
        ]);
        $args['forms'] = array_merge_recursive($args['forms'], [
            '/other/recaptcha/api' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_LibRecaptcha'],
                        [Ctrl::NAME => 'site_key', Ctrl::LABEL => (('Site Key'))],
                        [Ctrl::NAME => 'secret_key', Ctrl::LABEL => (('Secret Key'))],
                    ],
                ],
            ],
        ]);
    }
}