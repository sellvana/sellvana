<?php
/**
 * Created by pp
 * @project fulleron
 */

return array(
    'modules' => array(
        'FCom_CustomerGroups' => array(
            'version'      => '0.1.1',
            'require'      => array('module'=>array('FCom_Customer'=>'0.1.3')),
            'translations' => array('de' => 'de.php'),
            'tests'        => "FCom_CustomerGroups_Tests_AllTests",
            'description'  => "Customer Groups Management",
            'migrate'      => 'FCom_CustomerGroups_Migrate',
            'bootstrap'    => array('file' => 'CustomerGroups.php', 'callback' => 'FCom_CustomerGroups::bootstrap'),
            "require"      => array(
                "module" => "FCom_Core",
                "class"  => "PHPUnit",
            ),
            'areas'        => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file' => 'CustomerGroupsFrontend.php', 'callback' => 'FCom_CustomerGroups_Frontend::bootstrap'),
                ),
                'FCom_Admin' => array(
                    'bootstrap' => array('file' => 'CustomerGroupsAdmin.php', 'callback' => 'FCom_CustomerGroups_Admin::bootstrap'),
                ),
            ),
        ),
    )
);