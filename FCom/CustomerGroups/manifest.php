<?php
/**
 * Created by pp
 * @project fulleron
 */

return array(
    'modules' => array(
        'FCom_CustomerGroups' => array(
            'version'      => '0.1.1',
            'depends'      => array('FCom_Customer'),
            'translations' => array('de' => 'de.php'),
            'tests'        => "FCom_CustomerGroups_Tests_AllTests",
            'description'  => "Customer Groups Management",
            'migrate'      => 'FCom_CustomerGroups_Migrate',
            'bootstrap'    => array('file' => 'CustomerGroups.php', 'callback' => 'FCom_CustomerGroups_CustomerGroups::bootstrap'),
            "require"      => array(
                "module" => "FCom_Core",
                "class"  => "PHPUnit",
            ),
        ),
    )
);