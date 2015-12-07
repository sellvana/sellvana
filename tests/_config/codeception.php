<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Codeception Configurations
    |--------------------------------------------------------------------------
    |
    | This is where you add your Codeception configurations.
    */

    /*
    |--------------------------------------------------------------------------
    | Codeception modules register
    |--------------------------------------------------------------------------
    */
    'sites' => [
        'Sellvana_Wishlist' => FULLERON_ROOT_DIR . '/core/Sellvana/Wishlist/Test/codeception.yml',
        'FCom_Test' => FULLERON_ROOT_DIR . '/core/FCom/Test/Test/codeception.yml'
    ],

    /*
    |--------------------------------------------------------------------------
    | Codeception Executable
    |--------------------------------------------------------------------------
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
        'unit' => true
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
        '.DS_Store'
    ],

    /*
    |--------------------------------------------------------------------------
    | When load tests it will require on codeception global bootstrap
    |--------------------------------------------------------------------------
    */
    'codecept_bootstrap' => [
        FULLERON_ROOT_DIR . '/core/FCom/Test/bootstrap.php',
        FULLERON_ROOT_DIR . '/core/FCom/Test/Core/Sellvana.php',
        FULLERON_ROOT_DIR . '/core/FCom/Test/Core/Db.php'
    ],

    /*
    |--------------------------------------------------------------------------
    | When bootstrapping, codeception need to know where php.exe is
    | Unix base system please ignore it.
    |--------------------------------------------------------------------------
    | eg.
    | Wamp: C:/wamp/bin/php/php5.x.x/php.exe
    | Xampp: C:/xampp/php
    | Mamp: C:/mamp/bin/php/php5.x.x/bin/php.exe
    |
    | or put above `PATH` to windows PATH environment variables
    | to ignore it
    */
    'php_executable' => '',

    /*
    |--------------------------------------------------------------------------
    | Setting the location as the current file helps with offering information
    | about where this configuration file sits on the server.
    |--------------------------------------------------------------------------
    */
    'location' => __FILE__
];