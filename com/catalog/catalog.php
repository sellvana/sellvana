<?php

class FCom_Catalog extends BClass
{
    static public function bootstrap()
    {
        switch (FCom::area()) {
            case 'frontend': self::frontend(); break;
            case 'admin': self::admin(); break;
        }
    }

    static public function frontend()
    {
        BFrontController::i()
            ->route('GET /', 'FCom_Catalog_Ctrl.index')
        ;
    }

    static public function admin()
    {

    }
}

class FCom_Catalog_Ctrl extends BActionController
{
    public function action_index()
    {
        BResponse::i()->set('INDEX')->render();
    }
}
