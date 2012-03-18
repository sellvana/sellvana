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

class FCom_Cms_Frontend extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Cms_Frontend::layout')
        ;

        BFrontController::i()
            ->route('GET /', 'FCom_Cms_Frontend_Controller.index')
        ;

        BLayout::i()->allViews('Frontend/views', 'cms');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            '/cms'=>array(

            ),
        ));
    }
}

class FCom_Cms_Admin extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Cms_Admin::layout')
        ;

        BFrontController::i()
            ->route('GET /', 'FCom_Cms_Admin_Controller.index')
        ;

        BLayout::i()->allViews('Admin/views', 'cms');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'root', 'do'=>array(
                    array('addNav', 'cms', array('label'=>'CMS', 'pos'=>200)),
                    array('addNav', 'cms/nav', array('label'=>'Navigation', 'href'=>BApp::url('FCom_Cms', '/nav'))),
                    array('addNav', 'cms/pages', array('label'=>'Pages', 'href'=>BApp::url('FCom_Cms', '/pages'))),
                    array('addNav', 'cms/blocks', array('label'=>'Blocks', 'href'=>BApp::url('FCom_Cms', '/blocks'))),
                    array('addNav', 'cms/forms', array('label'=>'Form Actions', 'href'=>BApp::url('FCom_Cms', '/forms'))),
                )),
            ),
        ));
    }
}


class FCom_Cms_Model_Nav extends FCom_Core_Model_TreeAbstract
{
    protected static $_table = 'fcom_cms_nav';
}

class FCom_Cms_Model_Page extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_page';
}

class FCom_Cms_Model_Block extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_block';
}

class FCom_Cms_Model_Form extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_form';
}