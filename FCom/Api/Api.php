<?php

class FCom_Api extends BClass
{
    public static function bootstrap()
    {
        /*
        BFrontController::i()
            ->route('GET|POST|PUT|DELETE /v1/customers/.action', 'FCom_Customer_Api_Controller_Rest')
        ;
        */
    }
}

class FCom_Api_Controller_Abstract extends FCom_Core_Controller_Abstract
{

}

class FCom_Api_Controller_RestAbstract extends FCom_Api_Controller_Abstract
{

}