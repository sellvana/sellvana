<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Codeception Configurations
    |--------------------------------------------------------------------------
    |
    | This is where you add your Codeception configurations.
    */
    'sites' => [
        'Sellvana_Wishlist' => FULLERON_ROOT_DIR . '/core/Sellvana/Wishlist/Test/codeception.yml',
        'FCom_Test' => FULLERON_ROOT_DIR . '/core/FCom/Test/Test/codeception.yml'
    ],

    /*
    |--------------------------------------------------------------------------
    | Codeception Executable
    |--------------------------------------------------------------------------
    |
    */
    'executable' => FULLERON_ROOT_DIR . '/codecept.phar',

    /*
    |--------------------------------------------------------------------------
    | Decide which type of tests get included.
    |--------------------------------------------------------------------------
    */
    'tests' => [
        'acceptance' => false,
        'functional' => false,
        'unit' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | When scan for the tests, we need to ignore the following files.
    |--------------------------------------------------------------------------
    */
    'ignore' => [
        'WebGuy.php',
        'TestGuy.php',
        'CodeGuy.php',
        '_bootstrap.php',
        '.DS_Store',
    ],

    /*
    |--------------------------------------------------------------------------
    | When load tests it will require on codeception global bootstrap
    |--------------------------------------------------------------------------
    */
    'codecept_bootstrap' => [
        FULLERON_ROOT_DIR . '/core/FCom/Test/bootstrap.php',
        FULLERON_ROOT_DIR . '/tests/_support/Helper/Sellvana.php',
        FULLERON_ROOT_DIR . '/tests/_support/Helper/Db.php'
    ],

    /*
    |--------------------------------------------------------------------------
    | Setting the location as the current file helps with offering information
    | about where this configuration file sits on the server.
    |--------------------------------------------------------------------------
    */
    'location' => __FILE__,
];