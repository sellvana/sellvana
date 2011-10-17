<?php

class FCom_Catalog extends BClass
{
    static public function bootstrap()
    {
        BEventRegistry::i()
            ->on('FCom_Frontend::bootstrap', 'FCom_Catalog.bootFrontend');
            ->on('FCom_Admin::bootstrap', 'FCom_Catalog.bootAdmin');
    }

    public function bootFrontend()
    {

    }
}

class FCom_Catalog_Ctrl extends BActionController
{

}
