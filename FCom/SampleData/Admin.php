<?php

/**
 * Created by pp
 * @project fulleron
 */
class FCom_SampleData_Admin extends BClass
{
    public static function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission(
                             array(
                                 'sample_data' => 'Install Sample Data'
                             )
        );
        if ( BConfig::i()->get( 'modules/FCom_SampleData/load' ) ) {
            FCom_SampleData_Model_Loader::loadProducts();
        }
    }
}