<?php return array(
    'modules' => array(
        // cron jobs processing
        'FCom_Cron' => array(
            'version' => '0.1.0',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0')),
            'description' => "Cron scheduled tasks manager",
        ),
    ),
);