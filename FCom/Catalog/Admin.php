<?php

class FCom_Catalog_Admin extends BClass
{
    static public function bootstrap()
    {
        BEvents::i()
            ->on('category_tree_post.associate.products', 'FCom_Catalog_Model_Product.onAssociateCategory')
            ->on('category_tree_post.reorderAZ', 'FCom_Catalog_Model_Category.onReorderAZ')

            ->on('FCom_Catalog_Admin_Controller_Products::action_edit_post', 'FCom_Catalog_Admin::onProductsEditPost')

            /** @todo initialize these events only when needed */
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
        ;

        FCom_Admin_Controller_MediaLibrary::i()
            ->allowFolder('media/product/image')
            ->allowFolder('media/product/attachment')
            ->allowFolder('storage/import/products')
        ;

        FCom_Admin_Model_Role::i()->createPermission([
            'catalog' => 'Catalog',
            'catalog/products' => 'Manage Products',
            'catalog/categories' => 'Manage Categories',
            'catalog/families' => 'Manage Families',
            'catalog/stocks' => 'Manage Stocks',
        ]);
    }

    public static function onProductsEditPost($args)
    {
print_r($args); exit;
    }

    public static function onNavTreeForm($args)
    {
        $args['node_types']['category'] = 'Category';
    }

    public function getAvailableViews()
    {
        $template = [];
        $allViews = FCom_Frontend_Main::i()->getLayout()->getAllViews();
        foreach ($allViews as $view) {
            $tmp = $view->param('view_name');
            if ($tmp != '') {
                $template['view:' . $tmp] = $tmp;
            }
        }
        $cmsBlocks = [];
        $blocks = BDb::many_as_array(FCom_Cms_Model_Block::i()->orm()->select('id')->select('description')->find_many());
        foreach ($blocks as $block) {
            $cmsBlocks['block:' . $block['id']] = $block['description'];
        }
        return [
            '' => '',
            '@CMS Pages' => $cmsBlocks,
            '@Templates' => $template,
        ];
    }
}
