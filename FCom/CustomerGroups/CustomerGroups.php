<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_CustomerGroups
    extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()
            ->addAllViews('Admin/views')
            ->afterTheme(array(__CLASS__, 'layout'));

        BFrontController::i()
            ->route('GET /customer-groups', 'FCom_CustomerGroups_Admin_Controller_CustomerGroups.index') // list groups
            ->route('GET|POST /customer-groups/.action', 'FCom_CustomerGroups_Admin_Controller_CustomerGroups'); // add, edit groups
    }

    public static function layout()
    {
        BLayout::i()->addLayout(
            array(
                 'base' => array(
                     array('view', 'admin/header', 'do' => array(
                         array('addNav', 'customer/customer-groups', array(
                             'label' => 'Customer groups', 'href' => BApp::href('customer-groups'),
                         )),
                     )),
                 ),// end base
                 '/customer-groups' => array(
                     array('layout', 'base'),
                     array('hook', 'main', 'views'=>array('admin/grid')),
                     array('view', 'admin/header', 'do'=>array(array('setNav', 'customer-groups'))),
                 ),
            )
        );
    }
}