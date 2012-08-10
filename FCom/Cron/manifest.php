<?php return array(
    'modules' => array(
        // cron jobs processing
        'FCom_Cron' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'depends' => array('FCom_Core'),
            'migrate' => 'FCom_Cron_Migrate',
            'description' => "Cron scheduled tasks manager",
            'areas' => array(
                'FCom_Cron' => array(
                    'bootstrap' => array('file'=>'Cron.php', 'callback'=>'FCom_Cron::bootstrap'),
                ),
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CronAdmin.php', 'callback'=>'FCom_Cron_Admin::bootstrap'),
                ),
            ),
        ),





    ),
);