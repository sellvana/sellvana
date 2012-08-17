<?php return array(
    'modules' => array(
        'FCom_Disqus' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'depends' => array('FCom_Core'),
            'translations' => array('de' => 'de.csv'),
            'description' => "Disqus comments",
            'bootstrap' => array('file'=>'DisqusFrontend.php', 'callback'=>'FCom_Disqus_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'DisqusAdmin.php', 'callback'=>'FCom_Disqus_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'DisqusFrontend.php', 'callback'=>'FCom_Disqus_Frontend::bootstrap'),
                ),
            ),
        ),




    ),
);