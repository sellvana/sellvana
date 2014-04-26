<?php

class FCom_LibFirephp_Main extends BClass
{
    static public function bootstrap()
    {
        include_once __DIR__ . '/lib/FirePHP.class.php';
        include_once __DIR__ . '/lib/fb.php';
    }
}