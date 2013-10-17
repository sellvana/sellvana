<?php

class FCom_Catalog_Admin_Controller_Products extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'FCom_Catalog_Model_Product';
    protected $_gridHref = 'catalog/products';
    protected $_gridTitle = 'Products';
    protected $_recordName = 'Product';
    protected $_mainTableAlias = 'p';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 55, 'hidden' => true, 'cell'=>'integer'),
            array('name' => 'product_name', 'label' => 'Name', 'index' => 'p.product_name', 'href' => BApp::href('catalog/products/form?id=:id'), 'width' => 250),
            array('name' => 'local_sku', 'label' => 'Local SKU', 'index' => 'p.local_sku', 'width' => 100),
            array('name' => 'create_at', 'label' => 'Created', 'index' => 'p.create_at', 'width' => 100),
            array('name' => 'update_at', 'label' => 'Updated', 'index' => 'p.update_at', 'width' => 100),
            array('name' => 'uom', 'label' => 'UOM', 'index' => 'p.uom', 'width' => 60),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false),
        );
        $config['actions'] = array(
            'refresh' => true,
            'link_to_page' => true,
        );
        $config['format_callback'] = function($args) {
            foreach ($args['rows'] as $row) {

            }
        };
        return $config;
    }

    public function gridDataAfter($data)
    {
        $data = parent::gridDataAfter($data);
        foreach ($data['rows'] as $row) {
            $customRowData = $row->getData();
            if ($customRowData) {
                $row->set($customRowData);
                $row->set('data', null);
            }
        }
        unset($row);
        return $data;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'sidebar_img' => $m->thumbUrl(98),
            'title' => $m->id ? 'Edit Product: '.$m->product_name : 'Create New Product',
        ));
    }

    public function linkedCategoriesData($model)
    {
        $cp = FCom_Catalog_Model_CategoryProduct::i();
        $categories = $cp->orm()->where('product_id', $model->id())->find_many();
        if(!$categories){
            return BUtil::toJson(array());
        }
        $result = array();
        foreach($categories as $c){
            $result[] = 'check_'.$c->category_id;
        }
        return BUtil::toJson($result);
    }

    public function productLibraryGridConfig($gridId=false)
    {
        $columns = $this->gridColumns();
        unset($columns['product_name']['formatter'], $columns['product_name']['formatoptions']);
        $columns['create_at']['hidden'] = true;
        $config = $this->gridConfig();
        if ($gridId) {
            $config['grid']['id'] = $gridId;
        }
        $config['grid']['autowidth'] = false;
        $config['grid']['caption'] = 'All products';
        $config['grid']['multiselect'] = true;
        $config['grid']['height'] = '100%';
        $config['grid']['columns'] = $columns;
        $config['navGrid'] = array('add'=>false, 'edit'=>false, 'del'=>false);
        $config['custom']['personalize'] = 'products';
        //$config['custom']['autoresize'] = '#linked-products-layout';
        return $config;
    }

    public function productAttachmentsGridConfig($model)
    {
        return array(
            'grid' => array(
                'id' => 'product_attachments',
                'caption' => 'Product Attachments',
                'datatype' => 'local',
                'data' => BDb::many_as_array($model->mediaORM('A')->select('a.id')->select('a.file_name')->find_many()),
                'colModel' => array(
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>400),
                ),
                'multiselect' => true,
                'shrinkToFit' => true,
                'forceFit' => true,
            ),
            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>false),
            array('navButtonAdd', 'caption' => 'Add', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Attachments to Product', 'cursor'=>'pointer'),
            array('navButtonAdd', 'caption' => 'Remove', 'buttonicon'=>'ui-icon-trash', 'title' => 'Remove Attachments From Product', 'cursor'=>'pointer'),
        );
    }

    public function productImagesGridConfig($model)
    {
        return array(
            'grid' => array(
                'id' => 'product_images',
                'caption' => 'Product Images',
                'datatype' => 'local',
                'data' => BDb::many_as_array($model->mediaORM('I')->select('a.id')->select('a.file_name')->find_many()),
                'colModel' => array(
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>400),
                ),
                'multiselect' => true,
                'shrinkToFit' => true,
                'forceFit' => true,
            ),
            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>false),
            array('navButtonAdd', 'caption' => 'Add', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Images to Product', 'cursor'=>'pointer'),
            array('navButtonAdd', 'caption' => 'Remove', 'buttonicon'=>'ui-icon-trash', 'title' => 'Remove Images From Product', 'cursor'=>'pointer'),
        );
    }

    public function linkedProductGridConfig($model, $type)
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')
            ->select(array('p.id', 'p.product_name', 'p.local_sku'));

        switch ($type) {
        case 'related': case 'similar':
            $orm->join('FCom_Catalog_Model_ProductLink', array('pl.linked_product_id','=','p.id'), 'pl')
                ->where('link_type', $type)
                ->where('pl.product_id', $model ? $model->id : 0);

            //TODO: flexibility for more types
            $caption = $type=='related' ? 'Related Products' : 'Similar Products';
            break;

        default:
            $caption = '';
        }

        $gridId = 'linked_products_'.$type;
        $config = array(
            'grid' => array(
                'id'            => $gridId,
                'data'          => null,
                'datatype'      => 'local',
                'caption'       => $caption,
                'columns'       => array(
                    'id' => array('label'=>'ID', 'width'=>30),
                    'product_name' => array('label'=>'Product name', 'width'=>250),
                    'local_sku' => array('label'=>'Local SKU', 'width'=>250),
                ),
                'rowNum'        => 10,
                'sortname'      => 'product_name',
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

        BEvents::i()->fire(__METHOD__.'.orm', array('type'=>$type, 'orm'=>$orm));
        $data = BDb::many_as_array($orm->find_many());
        //unset unused columns
        $columnKeys = array_keys($config['grid']['columns']);
        foreach($data as &$prod){
            foreach($prod as $k => $p) {
                if (!in_array($k, $columnKeys)) {
                    unset($prod[$k]);
                }
            }
        }
        $config['grid']['data'] = $data;

        BEvents::i()->fire(__METHOD__.'.config', array('type'=>$type, 'config'=>&$config));

        return $config;
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        $model = $args['model'];
        $data = BRequest::i()->post();
        $this->processCategoriesPost($model);
        $this->processLinkedProductsPost($model, $data);
        $this->processMediaPost($model, $data);
    }

    public function processCategoriesPost($model)
    {
        $post = BRequest::i()->post();
        $categoreis = array();
        foreach($post as $key => $value){
            $matches = array();
            if(preg_match("#check_(\d+)#", $key, $matches)){
                $categoreis[intval($matches[1])] = $value;
            }
        }
        if (!empty($categoreis)){
            $cat_product = FCom_Catalog_Model_CategoryProduct::i();
            $category_model = FCom_Catalog_Model_Category::i();

            foreach($categoreis as $cat_id => $value){
                $product = $cat_product->orm()->where('product_id', $model->id())->where('category_id', $cat_id)->find_one();
                if(0 == $value && $product){
                    $product->delete();
                }elseif(false == $product){
                    $data=array('product_id' => $model->id(), 'category_id'=>$cat_id);
                    FCom_Catalog_Model_CategoryProduct::i()->create($data)->save();
                    /*
                    $category = $category_model->load($cat_id);
                    if(!$category){
                        continue;
                    }
                    $category_ids = explode("/",$category->id_path);
                    foreach($category_ids as $c_id) {
                        $product = $cat_product->orm()->where('product_id', $model->id())->where('category_id', $c_id)->find_one();
                        if(false == $product){
                            $data=array('product_id' => $model->id(), 'category_id'=>$c_id);
                            FCom_Catalog_Model_CategoryProduct::i()->create($data)->save();
                        }
                    }
                     *
                     */
                }
            }
        }
    }
    public function processLinkedProductsPost($model, $data)
    {
        //echo "<pre>"; print_r($data); echo "</pre>";
        $hlp = FCom_Catalog_Model_ProductLink::i();
        foreach (array('related', 'similar') as $type) {
            $typeName = 'linked_products_'.$type;
            if (!empty($data['grid'][$typeName]['del'])) {
                $hlp->delete_many(array(
                    'product_id' => $model->id,
                    'link_type' => $type,
                    'linked_product_id' => explode(',', $data['grid'][$typeName]['del']),
                ));
            }
            if (!empty($data['grid'][$typeName]['add'])) {
                $oldLinks = $hlp->orm()->where('link_type', $type)->where('product_id', $model->id)
                    ->find_many_assoc('linked_product_id');
                foreach (explode(',', $data['grid'][$typeName]['add']) as $linkedId) {
                    if ($linkedId && empty($oldLinks[$linkedId])) {
                        $m = $hlp->create(array(
                            'product_id' => $model->id,
                            'link_type' => $type,
                            'linked_product_id' => $linkedId,
                        ))->save();
                    }
                }
            }
        }
//exit;
        return $this;
    }

    public function processMediaPost($model, $data)
    {
        $hlp = FCom_Catalog_Model_ProductMedia::i();
        foreach (array('A'=>'attachments', 'I'=>'images') as $type=>$typeName) {
            $typeName = 'product_'.$typeName;
            if (!empty($data['grid'][$typeName]['del'])) {
                $hlp->delete_many(array(
                    'product_id' => $model->id,
                    'media_type' => $type,
                    'file_id'    => explode(',', $data['grid'][$typeName]['del']),
                ));
            }

            if (!empty($data['grid'][$typeName]['add'])) {
//echo "<pre>"; print_r($data['grid'][$typeName]['add']);
                $oldAtt = $hlp->orm()->where('product_id', $model->id)->where('media_type', $type)
                    ->find_many_assoc('file_id');
//print_r(BDb::many_as_array($oldAtt));
                foreach (explode(',', $data['grid'][$typeName]['add']) as $attId) {
                    if ($attId && empty($oldAtt[$attId])) {
//try {
//    echo 1;
                        $m = $hlp->create(array(
                            'product_id' => $model->id,
                            'media_type' => $type,
                            'file_id' => $attId,
                        ))->save();
//    print_r($m->as_array());
//} catch (Exception $e) {
//    echo 2;
//    Debug::exceptionHandler($e);
//}
                    }
                }
//echo "</pre>";
//exit;
            }
        }
        return $this;
    }

    public function onMediaGridConfig($args)
    {
        array_splice($args['config']['grid']['colModel'], -1, 0, array(
            array('name'=>'manuf_vendor_name', 'label'=>'Manufacturer', 'width'=>150, 'index'=>'v.vendor_name', 'editable'=>true),
        ));
    }

    public function onMediaGridGetORM($args)
    {
        $args['orm']->join('FCom_Catalog_Model_ProductMedia', array('pa.file_id','=','a.id',), 'pa')
            ->where_null('pa.product_id')->where('media_type', $args['type'])
            ->select(array('pa.manuf_vendor_id'));
    }

    public function onMediaGridUpload($args)
    {
        $hlp = FCom_Catalog_Model_ProductMedia::i();
        $id = $args['model']->id;
        if (!$hlp->load(array('product_id'=>null, 'file_id'=>$id))) {
            $hlp->create(array('file_id' => $id, 'media_type'=>$args['type']))->save();
        }
    }

    public function onMediaGridEdit($args)
    {
        $r = BRequest::i();
        $m = Denteva_Model_Vendor::i()->load(array(
            'is_manuf' => 1,
            'vendor_name' => $r->post('manuf_vendor_name')
        ));
        FCom_Catalog_Model_ProductMedia::i()
            ->load(array('product_id'=>null, 'file_id'=>$args['model']->id))
            ->set(array(
                'manuf_vendor_id' => $m ? $m->id : null,
            ))
            ->save();
    }
}
