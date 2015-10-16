<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Admin
 * @property FCom_Admin_Controller_MediaLibrary $FCom_Admin_Controller_MediaLibrary
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property FCom_Frontend_Main $FCom_Frontend_Main
 * @property Sellvana_Cms_Model_Block $Sellvana_Cms_Model_Block
 */
class Sellvana_Catalog_Admin extends BClass
{
    public function bootstrap()
    {
        $this->BEvents
            ->on('category_tree_post.associate.products', 'Sellvana_Catalog_Model_Product.onAssociateCategory')
            ->on('category_tree_post.reorderAZ', 'Sellvana_Catalog_Model_Category.onReorderAZ')

            ->on('Sellvana_Catalog_Admin_Controller_Products::action_edit_post', 'Sellvana_Catalog_Admin::onProductsEditPost')

            /** @todo initialize these events only when needed */
            /*
            ->on('FCom_Admin_Controller_MediaLibrary::gridConfig:media/product/attachment',
                'Sellvana_Catalog_Admin_Controller_Products.onMediaGridConfig', ['type' => 'A'])

            ->on('FCom_Admin_Controller_MediaLibrary::action_grid_get:media/product/attachment.orm',
                'Sellvana_Catalog_Admin_Controller_Products.onMediaGridGetORM', ['type' => 'A'])

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost:media/product/attachment.upload',
                'Sellvana_Catalog_Admin_Controller_Products.onMediaGridUpload', ['type' => 'A'])

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost:media/product/attachment.edit',
                'Sellvana_Catalog_Admin_Controller_Products.onMediaGridEdit', ['type' => 'A'])

            ->on('FCom_Admin_Controller_MediaLibrary::gridConfig:media/product/image',
                'Sellvana_Catalog_Admin_Controller_Products.onMediaGridConfig', ['type' => 'I'])

            ->on('FCom_Admin_Controller_MediaLibrary::action_grid_get:media/product/image.orm',
                'Sellvana_Catalog_Admin_Controller_Products.onMediaGridGetORM', ['type' => 'I'])

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost:media/product/image.upload',
                'Sellvana_Catalog_Admin_Controller_Products.onMediaGridUpload', ['type' => 'I'])

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost:media/product/image.edit',
                'Sellvana_Catalog_Admin_Controller_Products.onMediaGridEdit', ['type' => 'I'])

            ->on('Sellvana_Cms_Admin_Controller_Nav::action_tree_form', 'Sellvana_Catalog_Admin::onNavTreeForm')
            */
        ;

        $this->FCom_Admin_Controller_MediaLibrary
            ->allowFolder('media/category/images')
            ->allowFolder('media/product/images')
            ->allowFolder('media/product/attachment')
            ->allowFolder('media/product/videos')
            ->allowFolder('storage/import/products')
            ->allowFolder('{random}/import/products')
        ;

        $locale = BLocale::i();
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_Catalog' => $locale->_('Catalog Settings'),
            'catalog' => $locale->_('Catalog'),
            'catalog/products' => $locale->_('Manage Products'),
            'catalog/categories' => $locale->_('Manage Categories'),
            'catalog/families' => $locale->_('Manage Families'),
            'catalog/stocks' => $locale->_('Manage Stocks'),
        ]);
    }

    public function onNavTreeForm($args)
    {
        $args['node_types']['category'] = 'Category';
    }

    public function getAvailableViews()
    {
        $result = ['' => ''];
        $allViews = $this->FCom_Frontend_Main->getLayout()->getAllViews();
        foreach ($allViews as $view) {
            $tmp = $view->param('view_name');
            if ($tmp != '') {
                $result['@Templates']['view:' . $tmp] = $tmp;
            }
        }
        if ($this->BModuleRegistry->isLoaded('Sellvana_Cms')) {
            $blocks = $this->BDb->many_as_array($this->Sellvana_Cms_Model_Block->orm()->select('id')->select('description')->find_many());
            foreach ($blocks as $block) {
                $result['@CMS Pages']['block:' . $block['id']] = $block['description'];
            }
        }
        return $result;
    }
}
