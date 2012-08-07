<?php

class FCom_CustomField_Common extends BClass
{
    protected $_types;

    public static function bootstrap()
    {
        BPubSub::i()
            ->on('FCom_Catalog_Model_Product::find_one.orm', 'FCom_CustomField_Common.productFindORM')
            ->on('FCom_Catalog_Model_Product::find_many.orm', 'FCom_CustomField_Common.productFindORM')
            // is there save on frontend?
            //->on('FCom_Catalog_Model_Product::afterSave', 'FCom_CustomField.productAfterSave')
        ;
    }

    public function productFindORM($args)
    {
        $tP = $args['orm']->table_alias();
        $args['orm']
            ->select($tP.'.*')
            ->left_outer_join('FCom_CustomField_Model_ProductField', array('pcf.product_id','=',$tP.'.id'), 'pcf')
        ;
        $fields = FCom_CustomField_Model_Field::i()->fieldsInfo('product', true);
        $args['orm']->select($fields);
    }
}

