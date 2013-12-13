<?php

class FCom_Catalog_Admin_Controller_Categories extends FCom_Admin_Admin_Controller_Abstract_TreeForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'catalog/categories';
    protected $_navModelClass = 'FCom_Catalog_Model_Category';
    protected $_treeLayoutName = '/catalog/categories';
    protected $_formLayoutName = '/catalog/categories/tree_form';
    protected $_formViewName = 'catalog/categories-tree-form';

    public $formId = 'category_tree_form';
    public $imgDir = 'media/category/images';

    public function categoryProductGridConfig($model)
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')
            ->select(array('p.id', 'p.product_name', 'p.local_sku'))
            ->join('FCom_Catalog_Model_CategoryProduct', array('cp.product_id','=','p.id'), 'cp')
            ->where('cp.category_id', $model ? $model->id : 0)
        ;

        BEvents::i()->fire(__METHOD__.'.orm', array('orm'=>$orm));

        $config = array(
            'grid' => array(
                'id'            => 'category_products',
                'data'          => BDb::many_as_array($orm->find_many()),
                'datatype'      => 'local',
                'caption'       => 'Category Products',
                'colModel'      => array(
                    array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>40, 'hidden'=>true),
                    array('name'=>'product_name', 'label'=>'Name', 'index'=>'product_name', 'width'=>250),
                    array('name'=>'local_sku', 'label'=>'SKU', 'index'=>'local_sku', 'width'=>70),
                ),
                'rowNum'        => 10,
                'sortname'      => 'p.product_name',
                'sortorder'     => 'asc',
                'autowidth'     => false,
                'multiselect'   => true,
                'shrinkToFit' => true,
                'forceFit' => true,
            ),
            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>false),
            array('navButtonAdd', 'caption' => 'Add', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Products'),
            array('navButtonAdd', 'caption' => 'Remove', 'buttonicon'=>'ui-icon-trash', 'title' => 'Remove Products'),
        );

        BEvents::i()->fire(__METHOD__.'.config', array('config'=>&$config));

        return $config;
    }

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
}
