<?php

class FCom_Cron_Admin extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
        BLayout::i()->afterTheme('FCom_Cron_Admin::layout');
    }

    public static function layout()
    {
        BLayout::i()
            ->layout(array(
                '/settings'=>array(
                    array('view', 'settings', 'set'=>array('tab_view_prefix'=>'settings/'), 'do'=>array(
                        array('addTab', 'FCom_Cron', array('label'=>'Fulleron Crontab', 'async'=>true)),
                    )),
                ),
            ));
    }
}