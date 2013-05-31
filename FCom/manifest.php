<?php return array(
    'modules' => array(
        // Core module
        'FCom_Core'=>array(
            'version' => '0.1.0',
            'root_dir' => 'Core',
            'require' => array(),
            'bootstrap' => array('callback'=>'FCom_Core_Main::bootstrap'),
            'run_level' => 'REQUIRED',
            'migrate' => 'FCom_Core_Migrate',
            'description' => "Base Fulleron classes and JS libraries",
        ),
        // Initial installation module
        'FCom_Install' => array(
            'version' => '0.1.0',
            'root_dir' => 'Install',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0')),
            'description' => "Initial installation wizard",
        ),
        // API area
        'FCom_Api' => array(
            'version' => '0.1.0',
            'root_dir' => 'Api',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0')),
            'description' => "API area",
        ),
        // Frontend collection of modules
        'FCom_Frontend' => array(
            'version' => '0.1.0',
            'root_dir' => 'Frontend',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0', 'FCom_Frontend_DefaultTheme'=>'0.1.0')),
            'description' => "Base frontend functionality",
        ),
        // Frontend collection of modules
        'FCom_Frontend_DefaultTheme' => array(
            'version' => '0.1.0',
            'root_dir' => 'Frontend',
            'bootstrap' => array('file'=>'DefaultTheme.php', 'callback'=>'FCom_Frontend_DefaultTheme::bootstrap'),
            'require' => array('module'=>array('FCom_Core'=>'0.1.0', 'BTwig'=>'1.12.4')),
            'description' => "Default frontend theme",
            'provides' => array('theme' => 'FCom_Frontend_DefaultTheme'),
        ),
        // administration panel views and controllers
        'FCom_Admin' => array(
            'version' => '0.1.4',
            'root_dir' => 'Admin',
            'require' => array('module'=>array('FCom_Core'=>'0.1.0', 'FCom_Admin_DefaultTheme'=>'0.1.0')),
            'description' => "Base admin functionality",
        ),
        // Frontend collection of modules
        'FCom_Admin_DefaultTheme' => array(
            'version' => '0.1.0',
            'root_dir' => 'Admin',
            'bootstrap' => array('file'=>'DefaultTheme.php', 'callback'=>'FCom_Admin_DefaultTheme::bootstrap'),
            'require' => array('module'=>array('FCom_Core'=>'0.1.0', 'BHAML'=>'3.2')),
            'description' => "Default admin theme",
            'provides' => array('theme' => 'FCom_Admin_DefaultTheme'),
        ),
    ),
);