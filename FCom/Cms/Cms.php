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

        BLayout::i()->allViews('Frontend/views');
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
            ->route('GET /cms/nav', 'FCom_Cms_Admin_Controller_Nav.index')
            ->route('GET|POST /cms/nav/tree', 'FCom_Cms_Admin_Controller_Nav.tree')

            ->route('GET /cms/pages', 'FCom_Cms_Admin_Controller_Pages.index')
            ->route('GET|POST /cms/pages/grid_data', 'FCom_Cms_Admin_Controller_Pages.grid_data')
            ->route('GET|POST /cms/pages/form/:id', 'FCom_Cms_Admin_Controller_Pages.form')

            ->route('GET|POST /cms/blocks', 'FCom_Cms_Admin_Controller_Blocks.index')
            ->route('GET|POST /cms/blocks/form/:id', 'FCom_Cms_Admin_Controller_Blocks.form')

            ->route('GET|POST /cms/forms', 'FCom_Cms_Admin_Controller_Forms.index')
            ->route('GET|POST /cms/forms/form/:id', 'FCom_Cms_Admin_Controller_Forms.form')
        ;

        BLayout::i()->allViews('Admin/views');

        BDb::migrate('FCom_Cms_Admin::migrate');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'root', 'do'=>array(
                    array('addNav', 'cms', array('label'=>'CMS', 'pos'=>200)),
                    array('addNav', 'cms/nav', array('label'=>'Navigation', 'href'=>BApp::href('cms/nav'))),
                    array('addNav', 'cms/pages', array('label'=>'Pages', 'href'=>BApp::href('cms/pages'))),
                    array('addNav', 'cms/blocks', array('label'=>'Blocks', 'href'=>BApp::href('cms/blocks'))),
                    array('addNav', 'cms/forms', array('label'=>'Form Actions', 'href'=>BApp::href('cms/forms'))),
                )),
            ),
            '/cms/nav'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/nav')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/nav'))),
            ),
            '/cms/pages'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/pages')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/pages'))),
            ),
            '/cms/pages/form'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/pages/form')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/pages'))),
            ),
            '/cms/blocks'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/blocks')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/blocks'))),
            ),
            '/cms/blocks/form'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/blocks/form')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/blocks'))),
            ),
            '/cms/forms'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/forms')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/forms'))),
            ),
            '/cms/forms/form'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/forms/form')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/forms'))),
            ),
        ));
    }

    public static function migrate()
    {
        BDb::install('0.1.0', function() {
            $tNav = FCom_Cms_Model_Nav::table();
            BDb::run("
CREATE TABLE IF NOT EXISTS {$tNav} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `id_path` varchar(100) NOT NULL,
  `node_name` varchar(255) NOT NULL,
  `full_name` text NOT NULL,
  `url_key` varchar(255) NOT NULL,
  `url_path` varchar(255) NOT NULL,
  `url_href` varchar(255) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL,
  `num_children` int(10) unsigned DEFAULT NULL,
  `num_descendants` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


            ");
        });
    }
}


class FCom_Cms_Model_Nav extends FCom_Core_Model_TreeAbstract
{
    protected static $_table = 'fcom_cms_nav';
    protected static $_origClass = __CLASS__;
    protected static $_cacheAuto = true;
}

class FCom_Cms_Model_Page extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_page';
    protected static $_origClass = __CLASS__;
}

class FCom_Cms_Model_Block extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_block';
    protected static $_origClass = __CLASS__;
}

class FCom_Cms_Model_Form extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_form';
    protected static $_origClass = __CLASS__;
}