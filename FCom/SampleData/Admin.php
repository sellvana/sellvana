<?php

/**
 * Created by pp
 * @project fulleron
 */
class FCom_SampleData_Admin extends BClass
{
    public static function bootstrap()
    {
        if(BConfig::i()->get('modules/FCom_SampleData/load')){
            FCom_SampleData_Model_Loader::loadProducts();
        }
    }
}