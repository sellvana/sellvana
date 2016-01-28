<?php

/**
 * Class Sellvana_Catalog_Admin_Controller_Categories
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property FCom_Core_Main $FCom_Core_Main
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 */
class Sellvana_Catalog_Admin_Controller_Categories extends FCom_Admin_Controller_Abstract_TreeForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'catalog/categories';
    protected $_navModelClass = 'Sellvana_Catalog_Model_Category';
    protected $_treeLayoutName = '/catalog/categories';
    protected $_formLayoutName = '/catalog/categories/tree_form';
    protected $_formViewName = 'catalog/categories-tree-form';

    public $formId = 'category_tree_form';
    /*public $imgDir = 'media/category/images';*/

    /*public function action_upload__POST()
    {
        try {
            $id = $this->BRequest->param('id', true);
            $model = $this->Sellvana_Catalog_Model_Category->load($id);
            if (!$model) {
                throw new BException('Invalid Category ID.');
            }
            if (!(isset($_FILES['upload']) && !empty($_FILES['upload']['tmp_name']))) {
                throw new BException('No image file uploaded, please check again.');
            }
            //todo:should add check max size
            $tmp = $_FILES['upload']['tmp_name'];
            $imgInfo = getimagesize($tmp);
            if (!$imgInfo) {
                throw new BException('Invalid Image File');
            }
            $needConvert = ($imgInfo[2] != IMAGETYPE_JPEG) ? true : false; //check if we need convert image to jpg
            $dir = $this->FCom_Core_Main->dir($model->imagePath());
            $imageFile = $dir . $id . '.jpg';
            if (!move_uploaded_file($tmp, $imageFile)) {
                throw new BException('An error occurred while copying uploaded image.');
            }
            if ($needConvert && !$this->BUtil->convertImage($imageFile, $imageFile, null, null, 'jpg')) {
                $model->deleteImage(); //delete uploaded image
                throw new BException('An error occurred while convert image to jpg.');
            }
            $results = ['type' => 'success', 'filename' => $id . '.jpg'];
        } catch (Exception $e) {
            $results = ['type' => 'error', 'msg' => $this->_($e->getMessage())];
        }
        $this->BResponse->json($results);
    }*/

    public function onGenerateSiteMap($args)
    {
        $callback = function ($row) use ($args) {
            if ($row->get('parent_id') != null) {
                array_push($args['site_map'], ['loc' => $this->BApp->frontendHref($row->get('url_path')), 'changefreq' => 'daily']);
            }
        };
        $this->Sellvana_Catalog_Model_Category->orm()->select(['url_path', 'parent_id'])->iterate($callback);
    }

    /**
     * @param array $args
     */
    public function formPostBefore($args)
    {
        parent::formPostBefore($args);

        $args['model']->setData('layout', $this->FCom_Core_LayoutEditor->processFormPost());
    }
}
