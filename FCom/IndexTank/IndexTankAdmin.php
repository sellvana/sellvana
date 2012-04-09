<?php

class FCom_IndexTankAdmin extends BClass
{
    static public function bootstrap()
    {        
        $module = BApp::m();
        $module->base_src .= '/Admin';

        BFrontController::i()
            ->route('GET /indextank/products', 'FCom_IndexTank_Admin_Controller_Products.index')
        ;
        BLayout::i()->addAllViews('Admin/views');

        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_IndexTankAdmin::layout')
        ;

        FCom_Admin_Model_Role::i()->createPermission(array(
            'indextank' => 'IndexTank',
            'indextank/products' => 'Manage Products'
        ));
    }

    static public function layout()
    {
        $baseHref = BApp::href('indextank');
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'root', 'do'=>array(
                        array('addNav', 'indextank', array('label'=>'IndexTank', 'pos'=>100)),
                        array('addNav', 'indextank/products', array('label'=>'Products', 'href'=>$baseHref.'/products'))
                    )),
                ),
                '/products'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('indextank/products')),
                    array('view', 'root', 'do'=>array(array('setNav', 'indextank/products'))),
                )
            ));
        ;
    }
}