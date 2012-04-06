<?php

class FCom_Cms extends BClass
{
    public static function bootstrap()
    {
        switch (FCom::area()) {
            case 'FCom_Frontend': FCom_Cms_Frontend::bootstrap(); break;
            case 'FCom_Admin': FCom_Cms_Admin::bootstrap(); break;
        }
    }
}

class FCom_Cms_Model_Form extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_form';
    protected static $_origClass = __CLASS__;
}