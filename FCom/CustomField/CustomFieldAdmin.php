<?php

class FCom_CustomField_Admin extends BClass
{
    public static function bootstrap()
    {
        FCom_CustomField_Common::bootstrap();

        $ctrl = 'FCom_CustomField_Admin_Controller_FieldSets';
        BRouting::i()
            ->get('/customfields/fieldsets', $ctrl.'.index')
            ->any('/customfields/fieldsets/.action', $ctrl)

            ->get('/customfields/products/.action', 'FCom_CustomField_Admin_Controller_Products')
        ;

        BLayout::i()
            ->addAllViews('Admin/views')
            ->afterTheme('FCom_CustomField_Admin::layout')
        ;

        BEvents::i()
//            ->on('FCom_Catalog_Model_Product::afterSave', 'FCom_CustomField_Admin.productAfterSave')
            ->on('FCom_Catalog_Admin_Controller_Products::gridColumns', 'FCom_CustomField_Admin.productGridColumns')
                //
            ->on('FCom_Catalog_Admin_Controller_Products::formViewBefore', 'FCom_CustomField_Admin_Controller_Products.formViewBefore');
        ;
    }

    public static function layout()
    {
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'admin/header', 'do'=>array(
                        array('addNav', 'catalog/fieldsets', array('label'=>'Field Sets', 'href'=>BApp::href('customfields/fieldsets'))),
                    )),
                ),
                'catalog_product_form_tabs'=>array(
                    array('view', 'admin/form',
                        'do'=>array(
                            array('addTab', 'fields', array('label' => 'Custom Fields', 'pos'=>'15', 'view'=>'customfields/products/fields-tab')),
                        ),
                    ),
                ),
                '/customfields/fieldsets'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('customfields/fieldsets')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'catalog/fieldsets'))),
                ),
                '/customfields/fieldsets/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('customfields/fieldsets/form')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'catalog/fieldsets'))),
                ),/*
                '/settings'=>array(
                    array('view', 'settings', 'do'=>array(
                        array('addTab', 'FCom_CustomField', array('label'=>'Custom Fields', 'async'=>true)),
                    )),
                ),*/
            ));
    }
/*
    public function productAfterSave($args)
    {
        $p = $args['model'];
        $data = $p->as_array();
        $fields = FCom_CustomField_Model_Field::i()->fieldsInfo('product', true);
        if (array_intersect($fields, array_keys($data))) {
            $custom = FCom_CustomField_Model_ProductField::i()->load($p->id, 'product_id');
            if (!$custom) {
                $custom = FCom_CustomField_Model_ProductField::i()->create();
            }
            $custom->set('product_id', $p->id)->set($data)->save();
        }
        // not deleting to preserve meta info about fields
    }
*/
    public function productGridColumns($args)
    {
        $fields = FCom_CustomField_Model_Field::i()->orm('f')->find_many();
        foreach ($fields as $f) {
            $col = array('label'=>$f->field_name, 'index'=>'pcf.'.$f->field_name, 'hidden'=>true);
            if ($f->admin_input_type=='select') {
                $col['options'] = FCom_CustomField_Model_FieldOption::i()->orm()
                    ->where('field_id', $f->id)
                    ->find_many_assoc('id', 'label');
            }
            $args['columns'][$f->field_code] = $col;
        }
    }
}
