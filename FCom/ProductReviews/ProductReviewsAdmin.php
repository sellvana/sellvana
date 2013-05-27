<?php

class FCom_ProductReviews_Admin extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_ProductReviews_Admin::layout')
            ->on('BLayout::hook.catalog/products/tab/main', 'FCom_ProductReviews_Admin.hookProductTab')
        ;

        BRouting::i()
            ->route('GET /prodreviews', 'FCom_ProductReviews_Admin_Controller.index')
            ->route('GET|POST /prodreviews/.action', 'FCom_ProductReviews_Admin_Controller')
        ;

        BLayout::i()->addAllViews('Admin/views');
    }

    public function hookProductTab($args)
    {
        $model = $args['model'];
        BLayout::i()->view('prodreviews/products/tab')->model = $model;
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'admin/header', 'do'=>array(
                    array('addNav', 'catalog/prodreviews', array('label'=>'Product Reviews',
                        'href'=>BApp::href('prodreviews'))),
                )),
            ),
            'catalog_product_form_tabs'=>array(
                    array('view', 'admin/form',
                        'do'=>array(
                            array('addTab', 'product_reviews', array('label' => 'Product Review', 'pos'=>'70',
                                'view'=>'prodreviews/products/tab', 'async'=>true)),
                        ),
                    ),
             ),
            '/prodreviews'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('admin/grid')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'catalog/prodreviews'))),
                ),
             '/prodreviews/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('admin/form')),
                    array('view', 'admin/form', 'set'=>array(
                        'tab_view_prefix' => 'prodreviews/',
                    ), 'do'=>array(
                        array('addTab', 'main', array('label'=>'Product Review', 'pos'=>10))
                    )),
             ),
        ));
    }
}
