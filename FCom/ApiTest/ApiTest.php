<?php

class FCom_ApiTest extends BClass
{
    public static function bootstrap()
    {
        BRouting::i()
             //api routes
             ->route( 'GET /v1/apitest/test', 'FCom_ApiTest_ApiServer_V1_Test.list')
             ->route( 'GET|POST|DELETE|PUT /v1/apitest/test/.action', 'FCom_ApiTest_ApiServer_V1_Test')
        ;

    }
}