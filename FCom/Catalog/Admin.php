<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Catalog_Admin extends BClass
{
    public function bootstrap()
    {
        $this->BEvents
            ->on('category_tree_post.associate.products', 'FCom_Catalog_Model_Product.onAssociateCategory')
            ->on('category_tree_post.reorderAZ', 'FCom_Catalog_Model_Category.onReorderAZ')

            ->on('FCom_Catalog_Admin_Controller_Products::action_edit_post', 'FCom_Catalog_Admin::onProductsEditPost')

            /** @todo initialize these events only when needed */
            /*
            ->on('FCom_Admin_Controller_MediaLibrary::gridConfig:media/product/attachment',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridConfig', ['type' => 'A'])

            ->on('FCom_Admin_Controller_MediaLibrary::action_grid_get:media/product/attachment.orm',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridGetORM', ['type' => 'A'])

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost:media/product/attachment.upload',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridUpload', ['type' => 'A'])

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost:media/product/attachment.edit',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridEdit', ['type' => 'A'])

            ->on('FCom_Admin_Controller_MediaLibrary::gridConfig:media/product/image',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridConfig', ['type' => 'I'])

            ->on('FCom_Admin_Controller_MediaLibrary::action_grid_get:media/product/image.orm',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridGetORM', ['type' => 'I'])

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost:media/product/image.upload',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridUpload', ['type' => 'I'])

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost:media/product/image.edit',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridEdit', ['type' => 'I'])

            ->on('FCom_Cms_Admin_Controller_Nav::action_tree_form', 'FCom_Catalog_Admin::onNavTreeForm')
            */
        ;

        $this->FCom_Admin_Controller_MediaLibrary
            ->allowFolder('media/category/images')
            ->allowFolder('media/product/images')
            ->allowFolder('media/product/attachment')
            ->allowFolder('storage/import/products')
            ->allowFolder('{random}/import/products')
        ;

        $this->FCom_Admin_Model_Role->createPermission([
            'catalog' => 'Catalog',
            'catalog/products' => 'Manage Products',
            'catalog/categories' => 'Manage Categories',
            'catalog/families' => 'Manage Families',
            'catalog/stocks' => 'Manage Stocks',
        ]);
    }

    public function onProductsEditPost($args)
    {
print_r($args); exit;
    }

    public function onNavTreeForm($args)
    {
        $args['node_types']['category'] = 'Category';
    }

    public function getAvailableViews()
    {
        $template = [];
        $allViews = $this->FCom_Frontend_Main->getLayout()->getAllViews();
        foreach ($allViews as $view) {
            $tmp = $view->param('view_name');
            if ($tmp != '') {
                $template['view:' . $tmp] = $tmp;
            }
        }
        $cmsBlocks = [];
        $blocks = $this->BDb->many_as_array($this->FCom_Cms_Model_Block->orm()->select('id')->select('description')->find_many());
        foreach ($blocks as $block) {
            $cmsBlocks['block:' . $block['id']] = $block['description'];
        }
        return [
            '' => '',
            '@CMS Pages' => $cmsBlocks,
            '@Templates' => $template,
        ];
    }

    public function onControllerBeforeDispatch($args)
    {
        if ($this->BApp->m('FCom_PushServer')->run_status === BModule::LOADED
            && $this->BConfig->get('modules/FCom_Catalog/catalog_realtime_notification')
        ) {
            $this->FCom_PushServer_Model_Client->sessionClient()->subscribe('catalog_feed');
        }
    }
}
