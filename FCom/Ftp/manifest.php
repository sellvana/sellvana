<?php return array(
    'modules' => array(

        'FCom_Ftp' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'bootstrap' => array('file'=>'Ftp.php', 'callback'=>'FCom_Ftp::bootstrap'),
            'depends' => array('FCom_Core'),
            'description' => "FTP client",
        ),






    ),
);