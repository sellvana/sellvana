<?php

class FCom_Catalog_Frontend extends BClass
{
    static public function bootstrap()
    {
        if (class_exists('FCom_FrontendCP_Main')) {
            FCom_FrontendCP_Main::i()
                ->addEntityHandler('product', 'FCom_Catalog_Frontend_ControlPanel::productEntityHandler')
                ->addEntityHandler('category', 'FCom_Catalog_Frontend_ControlPanel::categoryEntityHandler')
            ;
        }
    }
}