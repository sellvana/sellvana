<?php

class FCom_Catalog_Admin_Controller_Categories extends FCom_Admin_Controller_Abstract_TreeForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'catalog/categories';
    protected $_navModelClass = 'FCom_Catalog_Model_Category';
    protected $_treeLayoutName = '/catalog/categories';
    protected $_formLayoutName = '/catalog/categories/tree_form';
    protected $_formViewName = 'catalog/categories-tree-form';

    public $formId = 'category_tree_form';
    /*public $imgDir = 'media/category/images';*/

    public function action_upload__POST()
    {
        $id = BRequest::i()->param('id', true);
        try {
            $model = FCom_Catalog_Model_Category::i()->load($id);
            /** @var $model FCom_Catalog_Model_Category */
            if ($model) {
                if (isset($_FILES['upload']) && !empty($_FILES['upload']['tmp_name'])) {
                    //todo:should add check max size
                    $tmp = $_FILES['upload']['tmp_name'];
                    $needConvert = (exif_imagetype($tmp) != IMAGETYPE_JPEG) ? true : false; //check if we need convert image to jpg
                    $dir = FCom_Core_Main::i()->dir($model->imagePath());
                    $imageFile = $dir.$id.'.jpg';
                    if (move_uploaded_file($tmp, $imageFile)) {
                        $results = array('type' => 'success', 'filename' => $id.'.jpg');
                        if ($needConvert && !BUtil::convertImage($imageFile, $imageFile, null, null, 'jpg')) {
                            $results = array('type' => 'error', 'msg' => $this->_('An error occurred while convert image to jpg.'));
                            $model->deleteImage(); //delete uploaded image
                        }
                    } else {
                        $results = array('type' => 'error', 'msg' => $this->_('An error occurred while uploading image.'));
                    }
                } else {
                    $results = array('type' => 'error', 'msg' => $this->_('No image file uploaded, please check again.'));
                }
            } else {
                $results = array('type' => 'error', 'msg' => $this->_('Cannot load model.'));
            }
        } catch (Exception $e) {
            $results = array('type' => 'error', 'msg' => $e->getMessage());
        }
        BResponse::i()->json($results);
    }

    public function onGenerateSiteMap($args)
    {
        $categories = FCom_Catalog_Model_Category::i()->orm()->find_many();
        if ($categories) {
            foreach ($categories as $category) {
                if ($category->get('parent_id')) {
                    array_push($args['site_map'], array('loc' => BApp::frontendHref($category->get('url_path')), 'changefreq' => 'daily'));
                }
            }
        }
    }
}
