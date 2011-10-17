<?php

class FCom_Admin extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route('GET /', 'FCom_Admin_Ctrl.index')
        ;
    }
}

class FCom_Admin_Ctrl extends BActionController
{
    public function action_index()
    {
        BResponse::i()->set('ADMIN')->render();
    }
}
