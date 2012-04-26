<?php

class FCom_Customer_Api extends BClass
{
    public static function bootstrap()
    {
        BFrontController::i()
            ->route('GET|POST|PUT|DELETE /v1/customer/.action/:id', 'FCom_Customer_Api_Controller_RestV1')
        ;
    }
}

class FCom_Customer_Api_Controller_RestV1 extends FCom_Api_Controller_RestAbstract
{
    public function action_customers()
    {

    }

    public function action_customers__POST()
    {

    }

    public function action_customers__PUT()
    {

    }

    public function action_customers__DELETE()
    {

    }

    public function action_addresses()
    {

    }

    public function action_addresses__POST()
    {

    }

    public function action_addresses__PUT()
    {

    }

    public function action_addresses__DELETE()
    {

    }
}