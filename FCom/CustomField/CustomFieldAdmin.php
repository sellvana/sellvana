<?php

class FCom_CustomField_Admin extends BClass
{
    public static function bootstrap()
    {
        FCom_CustomField::bootstrap();

        $ctrl = 'FCom_CustomField_Admin_Controller_FieldSets.';
        BFrontController::i()
            ->route('GET /customfields/fieldsets', $ctrl.'index')
            ->route('GET|POST /customfields/fieldsets/grid_data', $ctrl.'grid_data')
            ->route('GET|POST /customfields/fieldsets/set_field_grid_data', $ctrl.'set_field_grid_data')
            ->route('GET|POST /customfields/fieldsets/field_grid_data', $ctrl.'field_grid_data')
            ->route('GET|POST /customfields/fieldsets/field_option_grid_data', $ctrl.'field_option_grid_data')

            ->route('GET|POST /customfields/fieldsets/form/:id', $ctrl.'form')
            ->route('GET /customfields/fieldsets/form_tab/:id', $ctrl.'form_tab')

            ->route('GET /customfields/products/fields_partial/:id', 'FCom_CustomField_Admin_Controller_Products.fields_partial')
        ;

        BLayout::i()
            ->addAllViews('Admin/views')
        ;

        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_CustomField_Admin::layout')
            ->on('FCom_Catalog_Model_Product::afterSave', 'FCom_CustomField_Admin.productAfterSave')
            ->on('FCom_Catalog_Admin_Controller_Products::gridColumns', 'FCom_CustomField_Admin.productGridColumns');
        ;
    }

    public static function layout()
    {
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'root', 'do'=>array(
                        array('addNav', 'catalog/fieldsets', array('label'=>'Field Sets', 'href'=>BApp::href('customfields/fieldsets'))),
                    )),
                ),
                'catalog_product_form_tabs'=>array(
                    array('view', 'catalog/products/form',
                        'do'=>array(
                            array('addTab', 'fields', array('label' => 'Custom Fields', 'pos'=>'15', 'view'=>'customfields/products/fields-tab')),
                        ),
                    ),
                ),
                '/customfields/fieldsets'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('customfields/fieldsets')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/fieldsets'))),
                ),
                '/customfields/fieldsets/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('customfields/fieldsets/form')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/fieldsets'))),
                ),/*
                '/settings'=>array(
                    array('view', 'settings', 'do'=>array(
                        array('addTab', 'FCom_CustomField', array('label'=>'Custom Fields', 'async'=>true)),
                    )),
                ),*/
            ));
    }

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
