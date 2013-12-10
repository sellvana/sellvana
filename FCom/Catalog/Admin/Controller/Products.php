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
            array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>40),
            array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>55, 'hidden'=>true),
            array('name'=>'thumb_path', 'label'=>'Thumbnail', 'width'=>48, 'print'=>'"<img src=\'"+rc.row["thumb_path"]+"\' alt=\'"+rc.row["product_name"]+"\' >"', 'sortable'=>false),
            array('name'=>'product_name', 'label'=>'Name', 'width'=>250),
            array('name'=>'local_sku', 'label'=>'Local SKU', 'index'=>'p.local_sku', 'width'=>100),
            array('name'=>'short_description', 'label'=>'Description',  'width'=>200),
            array('name'=>'base_price', 'label'=>'Base Price',  'width'=>100,'hidden'=>true),
            array('name'=>'sale_price', 'label'=>'Sale Price',  'width'=>100,'hidden'=>true),
            array('name'=>'net_weight', 'label'=>'Net Weight',  'width'=>100,'hidden'=>true),
            array('name'=>'ship_weight', 'label'=>'Ship Weight',  'width'=>100,'hidden'=>true),
            array('name'=>'create_at', 'label'=>'Created', 'index'=>'p.create_at', 'width'=>100),
            array('name'=>'update_at', 'label'=>'Updated', 'index'=>'p.update_at', 'width'=>100),
            array('name'=>'_actions', 'label'=>'Actions', 'sortable'=>false, 'data'=>array('edit'=>array('href'=>BApp::href('catalog/products/form?id='), 'col'=>'id'),'delete'=>true)),
        );
        $config['actions'] = array(
            'export'=>true,
            'delete'=>true
        );
        $config['filters'] = array(
            array('field'=>'product_name', 'type'=>'text'),
            array('field'=>'local_sku', 'type'=>'text'),
            array('field'=>'short_description', 'type'=>'text'),
            array('field'=>'base_price', 'type'=>'number-range'),
            array('field'=>'sale_price', 'type'=>'number-range'),
            array('field'=>'net_weight', 'type'=>'number-range'),
            array('field'=>'ship_weight', 'type'=>'number-range'),
            array('field'=>'create_at', 'type'=>'date-range'),
            array('field'=>'update_at', 'type'=>'date-range'),
            '_quick'=>array('expr'=>'product_name like ? or local_sku like ? or p.id=?', 'args'=> array('?%', '%?%', '?'))
        );
        $config['format_callback'] = function($args) {
            foreach ($args['rows'] as $row) {

            }
        };
        return $config;
    }

    public static function afterInitialData($rows)
    {

        $media = BConfig::i()->get('web/media_dir') ? BConfig::i()->get('web/media_dir') : 'media/';
        $resize_url = FCom_Core_Main::i()->resizeUrl();
        foreach($rows as & $row) {
            $thumbUrl = $row['thumb_url'];
            $url = $media.'/'.($thumbUrl ? $thumbUrl : 'image-not-found.jpg');
            $row['thumb_path'] = $resize_url.'?f='.urlencode(trim($url, '/')).'&s=68x68';
        }

        return $rows;
    }

    public function gridDataAfter($data)
    {
        $media = BConfig::i()->get('web/media_dir') ? BConfig::i()->get('web/media_dir') : 'media/';
        $resize_url = FCom_Core_Main::i()->resizeUrl();

        $data = parent::gridDataAfter($data);
        foreach ($data['rows'] as $row) {
            $customRowData = $row->getData();
            if ($customRowData) {
                $row->set($customRowData);
                $row->set('data', null);
            }

            $thumbUrl = $row->thumb_url;
            $url = $media.'/'.($thumbUrl ? $thumbUrl : 'image-not-found.jpg');
            $row->set('thumb_path', $resize_url.'?f='.urlencode(trim($url, '/')).'&s=68x68');
        }
        unset($row);
        return $data;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $actions = array_merge($args['view']->actions, array(
                'duplicate' => '<button type="button" class="btn btn-primary" ><span>' .  BLocale::_('Duplicate') . '</span></button>',
                'saveAndContinue' => '<button type="submit" class="btn btn-primary" name="saveAndContinue" ><span>' .  BLocale::_('Save And Continue') . '</span></button>'
            ));
        $args['view']->set(array(
            'sidebar_img'=>$m->thumbUrl(98),
            'title'=>$m->id ? 'Edit Product: '.$m->product_name : 'Create New Product',
            'actions' => $actions
        ));
        $this->_formTitle = $m->id ? 'Edit Product: '.$m->product_name : 'Create New Product';
    }

    public function openCategoriesData($model)
    {
        $cp = FCom_Catalog_Model_CategoryProduct::i();
        $categories = $cp->orm('cp')->where('product_id', $model->id())
            ->join('FCom_Catalog_Model_Category', array('c.id','=','cp.category_id'), 'c')
            ->select('c.id_path')
            ->find_many();
        if(!$categories){
            return BUtil::toJson(array());
        }
        $result = array();
        foreach($categories as $c){
            $idPathArr = explode('/', $c->id_path);
            foreach ($idPathArr as $id) {
                $result[] = 'check_'.$id;
            }
        }
        return BUtil::toJson($result);
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
        $download_url = BApp::href('/media/grid/download?folder=media/product/attachment&file=');
        return array(
            'config'=>array(
                'id'=>'product_attachments',
                'caption'=>'Product Attachments',
                'data_mode'=>'local',
                'data'=>BDb::many_as_array($model->mediaORM('A')->order_by_expr('pa.position asc')->select(array('pa.id', 'pa.product_id', 'pa.remote_url','pa.position','pa.label','a.file_name','a.file_size','pa.create_at','pa.update_at'))->select('a.id','file_id')->find_many()),
                'columns'=>array(
                    array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>40),
                    array('name'=>'download_url',  'hidden'=>true, 'default'=>$download_url),
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'file_id', 'label'=>'File ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'product_id', 'label'=>'Product ID', 'width'=>400, 'hidden'=>true, 'default'=>$model->id()),
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>200, 'print'=>'"<a href=\'"+rc.row["download_url"]+rc.row["file_name"]+"\'>"+rc.row["file_name"]+"</a>"'),
                    array('name'=>'file_size', 'label'=>'File Size', 'width'=>200, 'display'=>'file_size'),
                    array('name'=>'label', 'label'=>'Label', 'width'=>250, 'editable'=>'inline', 'validation'=>array('required'=>true)),
                    array('name'=>'position', 'label'=>'Position', 'width'=>50, 'editable'=>'inline', 'validation'=>array('number'=>true,'required'=>true)),
                    array('name'=>'create_at', 'label'=>'Created', 'width'=>200),
                    array('name'=>'update_at', 'label'=>'Updated', 'width'=>200),
                    array('name'=>'_actions', 'label'=>'Actions', 'sortable'=>false, 'data'=>array('edit'=>true,'delete'=>true))
                ),
                'actions'=>array(
                    'add'=>array('caption'=>'Add attachments'),
                    'delete'=>array('caption'=>'Remove')
                ),
                'events'=>array('init-detail', 'add','mass-delete', 'delete', 'edit'),
                'filters'=>array(
                    array('field'=>'file_name', 'type'=>'text'),
                    array('field'=>'label', 'type'=>'text'),
                    '_quick'=>array('expr'=>'file_name like ? ', 'args'=> array('%?%'))
                )
            )
        );
    }

    public function productImagesGridConfig($model)
    {

        $download_url = BApp::href('/media/grid/download?folder=media/product/images&file=');
        $thumb_url = FCom_Core_Main::i()->resizeUrl().'?s=100x100&f='.BConfig::i()->get('web/media_dir').'/'.'product/images/';
        $data = BDb::many_as_array($model->mediaORM('I')
                ->order_by_expr('pa.position asc')
                ->select(array('pa.id', 'pa.product_id', 'pa.remote_url','pa.position','pa.label','a.file_name','a.file_size','pa.create_at','pa.update_at'))
                ->select('a.id','file_id')
                ->find_many());
        return array(
            'config'=>array(
                'id'=>'product_images',
                'caption'=>'Product Images',
                'data_mode'=>'local',
                'data'=>$data,
                'columns'=>array(
                    array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>40),
                    array('name'=>'id', 'hidden'=>true),
                    array('name'=>'file_id',  'hidden'=>true),
                    array('name'=>'product_id', 'hidden'=>true,'default'=>$model->id()),
                    array('name'=>'download_url',  'hidden'=>true, 'default'=>$download_url),
                    array('name'=>'thumb_url',  'hidden'=>true, 'default'=>$thumb_url),
                    array('name'=>'file_name',  'hidden'=>true),
                    array('name'=>'prev_img', 'label'=>'Preview', 'width'=>110, 'print'=>'"<a href=\'"+rc.row["download_url"]+rc.row["file_name"]+"\'><img src=\'"+rc.row["thumb_url"]+rc.row["file_name"]+"\' alt=\'"+rc.row["file_name"]+"\' ></a>"', 'sortable'=>false),
                    array('name'=>'file_size', 'label'=>'File Size', 'width'=>200, 'display'=>'file_size'),
                    array('name'=>'label', 'label'=>'Label', 'width'=>250, 'editable'=>'inline', 'validation'=>array('required'=>true)),
                    array('name'=>'position', 'label'=>'Position', 'width'=>50, 'editable'=>'inline', 'validation'=>array('required'=>true, 'number'=>true)),
                    array('name'=>'main_thumb', 'label'=>'Thumbnail', 'width'=>50, 'editable'=>'inline', 'validation'=>array('required'=>true, 'number'=>true)),
                    array('name'=>'create_at', 'label'=>'Created', 'width'=>200),
                    array('name'=>'update_at', 'label'=>'Updated', 'width'=>200),
                    array('name'=>'_actions', 'label'=>'Actions', 'sortable'=>false, 'data'=>array('edit'=>true, 'delete'=>true))
                ),
                'actions'=>array(
                    'add'=>array('caption'=>'Add images'),
                    'delete'=>array('caption'=>'Remove')
                ),
                'events'=>array('init-detail', 'add','mass-delete', 'delete', 'edit'),
                'filters'=>array(
                    array('field'=>'file_name', 'type'=>'text'),
                    array('field'=>'label', 'type'=>'text'),
                    '_quick'=>array('expr'=>'file_name like ? ', 'args'=> array('%?%'))
                )
            )
        );
    }

    /**
    * modal grid on category/product tab
    */
    public function getAllProdConfig($model)
    {

        $config = parent::gridConfig();
        //$config['id'] = 'category_all_prods_grid-'.$model->id;
        $config['id'] = 'category_all_prods_grid_'.$model->id;
        $config['columns'] = array(
            array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>40),
            array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>55, 'hidden'=>true),
            array('name'=>'product_name', 'label'=>'Name', 'index'=>'p.product_name', 'width'=>250),
            array('name'=>'local_sku', 'label'=>'Local SKU', 'index'=>'p.local_sku', 'width'=>100),
        );
        $config['actions'] = array(
            'add'=>array('caption'=>'Add selected products')
        );
        $config['filters'] = array(
            array('field'=>'product_name', 'type'=>'text'),
            array('field'=>'local_sku', 'type'=>'text'),
            '_quick'=>array('expr'=>'product_name like ? or local_sku like ? or p.id=?', 'args'=> array('?%', '%?%', '?'))
        );

        $config['events'] = array('add');
        /*$config['_callbacks'] = "{
            'add':'categoryProdsMng.addSelectedProds'
        }";*/


        return array('config' =>$config);
    }

    /*
    *main grid on category/product tab
    */
    public function getCatProdConfig($model)
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')
            ->select(array('p.id', 'p.product_name', 'p.local_sku'))
            ->join('FCom_Catalog_Model_CategoryProduct', array('cp.product_id','=','p.id'), 'cp')
            ->where('cp.category_id', $model ? $model->id : 0)
        ;

        $config = parent::gridConfig();

        // TODO for empty local grid, it throws exception
        unset($config['orm']);
        $config['data'] = $orm->find_many();
        $config['id'] = 'category_prods_grid_'.$model->id;
        $config['columns'] = array(
            array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>40),
            array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>80, 'hidden'=>true),
            array('name'=>'product_name', 'label'=>'Name', 'index'=>'p.product_name', 'width'=>400),
            array('name'=>'local_sku', 'label'=>'Local SKU', 'index'=>'p.local_sku', 'width'=>200)
        );
        $config['actions'] = array(
            'add'=>array('caption'=>'Add products'),
            'delete'=>array('caption'=>'Remove')
        );
        $config['filters'] = array(
            array('field'=>'product_name', 'type'=>'text'),
            array('field'=>'local_sku', 'type'=>'text')
        );
        $config['data_mode'] = 'local';
        $config['events'] = array('init', 'add','mass-delete');

        return array('config'=>$config);
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
                'id'           =>$gridId,
                'data'         =>null,
                'data_mode'     =>'local',
                //'caption'      =>$caption,
                'columns'      =>array(
                    array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>40),
                    array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>80, 'hidden'=>true),
                    array('name'=>'product_name', 'label'=>'Name', 'index'=>'p.product_name', 'width'=>400),
                    array('name'=>'local_sku', 'label'=>'Local SKU', 'index'=>'p.local_sku', 'width'=>200)
                ),
                'actions'=>array(
                    'add'=>array('caption'=>'Add products'),
                    'delete'=>array('caption'=>'Remove')
                ),
                'filters'=>array(
                    array('field'=>'product_name', 'type'=>'text'),
                    array('field'=>'local_sku', 'type'=>'text')
                ),
                'events'=>array('init', 'add','mass-delete')
            );


        //BEvents::i()->fire(__METHOD__.'.orm', array('type'=>$type, 'orm'=>$orm));
        $data = BDb::many_as_array($orm->find_many());
        //unset unused columns
        /*$columnKeys = array_keys($config['columns']);
        foreach($data as &$prod){
            foreach($prod as $k=>$p) {
                if (!in_array($k, $columnKeys)) {
                    unset($prod[$k]);
                }
            }
        }*/

        $config['data'] = $data;

        //BEvents::i()->fire(__METHOD__.'.config', array('type'=>$type, 'config'=>&$config));
        return array('config'=>$config);
    }

    public function formPostAfter($args)
    {

        parent::formPostAfter($args);
        $model = $args['model'];
        $data = BRequest::i()->post();
        $this->processCategoriesPost($model);
        $this->processLinkedProductsPost($model, $data);
        $this->processMediaPost($model, $data);
        $this->processCustomFieldPost($model, $data);
        $this->processVariantPost($model, $data);
        $this->processSystemLangFieldsPost($model, $data);
        $this->processFrontendPost($model, $data);
    }

    public function processCategoriesPost($model)
    {
        $post = BRequest::i()->post();
        $categories = array();
        foreach($post as $key=>$value){
            $matches = array();
            if(preg_match("#check_(\d+)#", $key, $matches)){
                $categories[intval($matches[1])] = $value;
            }
        }
        if (!empty($categories)){
            $cat_product = FCom_Catalog_Model_CategoryProduct::i();
            $category_model = FCom_Catalog_Model_Category::i();

            foreach($categories as $cat_id=>$value){
                $product = $cat_product->orm()->where('product_id', $model->id())->where('category_id', $cat_id)->find_one();
                if(0 == $value && $product){
                    $product->delete();
                }elseif(false == $product){
                    $data=array('product_id'=>$model->id(), 'category_id'=>$cat_id);
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
                            $data=array('product_id'=>$model->id(), 'category_id'=>$c_id);
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
                    'product_id'=>$model->id,
                    'link_type'=>$type,
                    'linked_product_id'=>explode(',', $data['grid'][$typeName]['del']),
                ));
            }
            if (!empty($data['grid'][$typeName]['add'])) {
                $oldLinks = $hlp->orm()->where('link_type', $type)->where('product_id', $model->id)
                    ->find_many_assoc('linked_product_id');
                foreach (explode(',', $data['grid'][$typeName]['add']) as $linkedId) {
                    if ($linkedId && empty($oldLinks[$linkedId])) {
                        $m = $hlp->create(array(
                            'product_id'=>$model->id,
                            'link_type'=>$type,
                            'linked_product_id'=>$linkedId,
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
            //$typeName = 'product_'.$typeName;
            if (!empty($data['grid'][$typeName]['del'])) {
                $hlp->delete_many(array(
                    'product_id'=>$model->id,
                    'media_type'=>$type,
                    'id'   =>explode(',', $data['grid'][$typeName]['del']),
                ));
            }

            if (!empty($data['grid'][$typeName]['rows'])) {
                $rows = json_decode($data['grid'][$typeName]['rows'], true);
                foreach($rows as $row) {
                    if (isset($row['_new'])) {
                        $mediaModel = $hlp->create(array(
                            'product_id'=>$model->id,
                            'media_type'=>$type,
                            'file_id'=>$row['file_id'],
                            'label'=>isset($row['label']) ? $row['label'] : '',
                            'position'=>isset($row['position']) ? $row['position'] : '',
                            //TODO remote_url and file_path can be fetched based on file_id. Beside, file_name can be changed in media libary.
                            //'remote_url' =>BApp::href('/media/grid/download?folder=media/product/attachment&file_='.$row['file_id']),

                        ))->save();
                    } else {
                        $mediaModel = $hlp->load($row['id'])->set($row)->save();
                    }
                    if ($mediaModel->get('main_thumb')) {
                        $mediaLibModel = FCom_Core_Model_MediaLibrary::i()->load($mediaModel->get('file_id'));
                        $model->set('thumb_url', $mediaLibModel->get('folder').'/'.$mediaLibModel->get('file_name'))->save();
                    }
                }
/*
//echo "<pre>"; print_r($data['grid'][$typeName]['add']);
                $oldAtt = $hlp->orm()->where('product_id', $model->id)->where('media_type', $type)
                    ->find_many_assoc('file_id');
//print_r(BDb::many_as_array($oldAtt));
                foreach (explode(',', $data['grid'][$typeName]['add']) as $attId) {
                    if ($attId && empty($oldAtt[$attId])) {
//try {
//    echo 1;
                        $m = $hlp->create(array(
                            'product_id'=>$model->id,
                            'media_type'=>$type,
                            'file_id'=>$attId,
                        ))->save();
//    print_r($m->as_array());
//} catch (Exception $e) {
//    echo 2;
//    Debug::exceptionHandler($e);
//}
                    }
                }
//echo "</pre>";
//exit;**/
            }
        }
        return $this;
    }

    public function processCustomFieldPost($model, $data)
    {

        if (!empty($data['custom_fields'])) {
            $model->setData('custom_fields', $data['custom_fields']);
        }

        $model->save();
    }

    public function processVariantPost($model, $data)
    {
        if (!empty($data['vfields'])) {
            $model->setData('variants_fields', json_decode($data['vfields'], true));
        }
        if (!empty($data['variants'])) {
            $model->setData('variants', json_decode($data['variants'], true));
        }
        $model->save();

    }

    public function processSystemLangFieldsPost($model, $data)
    {
        $model->setData('name_lang_fields', $data['name_lang_fields']);
        $model->setData('short_desc_lang_fields', $data['short_desc_lang_fields']);
        $model->setData('desc_lang_fields', $data['desc_lang_fields']);
        $model->save();

    }

    public function processFrontendPost($model, $data)
    {
        if (!empty($data['prod_frontend_data'])) {
            $model->setData('frontend_fields', json_decode($data['prod_frontend_data'], true));
            $model->save();
        }

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
            $hlp->create(array('file_id'=>$id, 'media_type'=>$args['type']))->save();
        }
    }

    public function onMediaGridEdit($args)
    {
        $r = BRequest::i();
        $m = Denteva_Model_Vendor::i()->load(array(
            'is_manuf'=>1,
            'vendor_name'=>$r->post('manuf_vendor_name')
        ));
        FCom_Catalog_Model_ProductMedia::i()
            ->load(array('product_id'=>null, 'file_id'=>$args['model']->id))
            ->set(array(
                'manuf_vendor_id'=>$m ? $m->id : null,
            ))
            ->save();
    }

}
