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
        $dir = FCom_Core_Main::i()->dir('media/category/images');
        if (isset($_FILES["upload"])) {
            $id = BRequest::i()->param('id', true);
            $newJpg = $id.'.jpg';
            $tmp = $_FILES['upload']['tmp_name'];

            switch (exif_imagetype($tmp)) {
                case IMAGETYPE_GIF :
                    $img = imagecreatefromgif($tmp);
                    break;
                case IMAGETYPE_JPEG || IMAGETYPE_TIFF_II:
                    $img = imagecreatefromjpeg($tmp);
                    break;
                case IMAGETYPE_BMP:
                    $img = imagecreatefromwbmp($tmp);
                    break;
                case IMAGETYPE_PNG:
                    $img = imagecreatefrompng($tmp);
                    break;
                default : break;
            }
            $jpg = imagejpeg($img, $newJpg);
            if ($jpg) {
                $path = $dir .'/'.$newJpg;
                if (@move_uploaded_file($tmp, $path) ) {
                    $this->resizeImage(300, 300, $path, $dir .'/'.$id.'_large.jpg');
                    $file = array('file_name' => $newJpg, 'large_image' => $id.'_large.jpg');
                    BResponse::i()->json($file);
                }
            } else {
                BResponse::i()->json('{"error": "true"}');
            }

        }
    }

    public function resizeImage($width, $height, $src, $des)
    {
        //todo: put this function into BUtil or Util class
        $dst_image = imagecreatetruecolor($width,$height);
        $color = imagecolorallocate($dst_image,
            base_convert(substr('FFFFFF', 0, 2), 16, 10),
            base_convert(substr('FFFFFF', 2, 2), 16, 10),
            base_convert(substr('FFFFFF', 4, 2), 16, 10)
        );
        imagefill($dst_image, 0, 0, $color);
        $src_image = imagecreatefromjpeg($src);
        if ($src_image) {
            $sw = imagesx($src_image);
            $sh = imagesy($src_image);
            $scale = $sw > $sh ? $width/$sw : $height/$sh;
            $dw1 = $sw*$scale;
            $dh1 = $sh*$scale;
            if ( $sh <$height) {
                $dh1 = $sh;
            }
            if (  $sw < $width) {
                $dw1 = $sw;
            }
            imagecopyresampled($dst_image, $src_image, ($width-$dw1)/2, ($height-$dh1)/2, 0, 0, $dw1, $dh1, $sw, $sh);
            imagejpeg($dst_image, $des);
        }

    }
}
