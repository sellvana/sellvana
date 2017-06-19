<?php

class Sellvana_Email_AdminSPA_Dashboard extends BClass
{
    public function widgetNewSubscriptions($filter)
    {
        return [
            'emails' => [
                'a@b.com',
                'test@sellvana.com',
            ],
        ];
    }
}