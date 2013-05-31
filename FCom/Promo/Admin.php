<?php

class FCom_Promo_Admin extends BClass
{
    static public function bootstrap()
    {
        BRouting::i()

            ->route('GET /promo/vendors/autocomplete', 'Denteva_Admin_Controller_Vendors.autocomplete')

            ->route('GET /promo', 'FCom_Promo_Admin_Controller.index')
            ->route('GET|POST /promo/.action', 'FCom_Promo_Admin_Controller')

            ->route('GET /promo/form/:id/products', 'FCom_Promo_Admin_Controller.form_products')
            ->route('GET /promo/form/:id/group', 'FCom_Promo_Admin_Controller.form_group')
            ->route('GET /promo/attachments', 'FCom_Promo_Admin_Controller.attachments')
            ->route('GET /promo/attachments/download', 'FCom_Promo_Admin_Controller.attachments_download')
            ->route('POST /promo/attachments/:do', 'FCom_Promo_Admin_Controller.attachments_post')
        ;

        BLayout::i()
            ->allViews('Admin/views')
            ->afterTheme('FCom_Promo_Admin::layout')
        ;

        FCom_Admin_Controller_MediaLibrary::i()->allowFolder('media/promo');
    }

    public static function layout()
    {
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'admin/header', 'do'=>array(
                        array('addNav', 'catalog/promo', array('label'=>'Promotions', 'href'=>BApp::href('promo'))),
                    )),
                ),
                '/denteva/vendors'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('denteva/vendors')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'denteva/vendors'))),
                ),
                'denteva_promo_form_tabs'=>array(
                    array('view', 'promo/form',
                        'set'=>array(
                            'tab_view_prefix' => 'promo/tab-',
                        ),
                        'do'=>array(
                            array('addTab', 'main', array('label' => 'General Info')),
                            array('addTab', 'details', array('label' => 'Details', 'async'=>true)),
                            array('addTab', 'attachments', array('label' => 'Attachments', 'async'=>true)),
                            array('addTab', 'history', array('label' => 'History', 'async'=>true)),
                        ),
                    ),
                ),
                '/promo'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('admin/grid')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'catalog/promo'))),
                ),
                '/promo/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('layout', 'denteva_promo_form_tabs'),
                    array('hook', 'main', 'views'=>array('promo/form')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'catalog/promo'))),
                ),
            ));
    }
}