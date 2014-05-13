<?php

class FCom_Catalog_Admin_Controller_Products extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'FCom_Catalog_Model_Product';
    protected $_gridHref = 'catalog/products';
    protected $_gridTitle = 'Products';
    protected $_recordName = 'Product';
    protected $_mainTableAlias = 'p';
    protected $_permission = 'catalog/products';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 55, 'hidden' => true],
            ['display' => 'eval', 'name' => 'thumb_path', 'label' => 'Thumbnail', 'width' => 48, 'sortable' => false,
                'print' => '"<img src=\'"+rc.row["thumb_path"]+"\' alt=\'"+rc.row["product_name"]+"\' >"'],
            ['name' => 'product_name', 'label' => 'Name', 'width' => 250],
            ['name' => 'local_sku', 'label' => 'SKU', 'index' => 'p.local_sku', 'width' => 100],
            ['name' => 'short_description', 'label' => 'Description',  'width' => 200],
            ['name' => 'base_price', 'label' => 'Base Price',  'width' => 100, 'hidden' => true],
            ['name' => 'sale_price', 'label' => 'Sale Price',  'width' => 100, 'hidden' => true],
            ['name' => 'net_weight', 'label' => 'Net Weight',  'width' => 100, 'hidden' => true],
            ['name' => 'ship_weight', 'label' => 'Ship Weight',  'width' => 100, 'hidden' => true],
            ['name' => 'position', 'label' => 'Position', 'index' => 'p.position', 'hidden' => true],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 'p.create_at', 'width' => 100],
            ['name' => 'update_at', 'label' => 'Updated', 'index' => 'p.update_at', 'width' => 100],
            ['type' => 'btn_group', 'buttons' => [
                  ['name' => 'edit', 'href' => BApp::href('catalog/products/form?id=')],
                  ['name' => 'delete']
            ]],
        ];
        $config['actions'] = [
            'refresh' => true,
            'export' => true,
            'delete' => true,
            //'custom'=>array('class'=>'test', 'caption'=>'ffff', 'id'=>'prod_custom')
        ];
        $config['filters'] = [
            ['field' => 'product_name', 'type' => 'text'],
            ['field' => 'local_sku', 'type' => 'text'],
            ['field' => 'short_description', 'type' => 'text'],
            ['field' => 'base_price', 'type' => 'number-range'],
            ['field' => 'sale_price', 'type' => 'number-range'],
            ['field' => 'net_weight', 'type' => 'number-range'],
            ['field' => 'ship_weight', 'type' => 'number-range'],
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'update_at', 'type' => 'date-range'],
            '_quick' => ['expr' => 'product_name like ? or local_sku like ? or p.id=?', 'args' => ['?%', '%?%', '?']]
        ];
        $config['format_callback'] = function($args) {
            foreach ($args['rows'] as $row) {

            }
        };
        return $config;
    }

    public static function afterInitialData($rows)
    {
        $mediaUrl = BConfig::i()->get('web/media_dir') ? BConfig::i()->get('web/media_dir') : 'media';
        $hlp = FCom_Core_Main::i();
        foreach ($rows as & $row) {
            $thumbUrl = $row['thumb_url'] ? $row['thumb_url'] : 'image-not-found.png';
            $row['thumb_path'] = $hlp->resizeUrl($mediaUrl . '/' . $thumbUrl, ['s' => 68]);
        }
        return $rows;
    }

    public function gridDataAfter($data)
    {
        $mediaUrl = BConfig::i()->get('web/media_dir') ? BConfig::i()->get('web/media_dir') : 'media';
        $hlp = FCom_Core_Main::i();

        $data = parent::gridDataAfter($data);
        foreach ($data['rows'] as $row) {
            $customRowData = $row->getData();
            if ($customRowData) {
                $row->set($customRowData);
                $row->set('data', null);
            }
            $thumbUrl = $row->get('thumb_url') ? $row->get('thumb_url') : 'image-not-found.png';
            $row->set('thumb_path', $hlp->resizeUrl($mediaUrl . '/' . $thumbUrl, ['s' => 68]));
        }
        unset($row);
        return $data;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $newAction = [];
        if ($m->id) {
            $newAction['duplicate'] = '<a href="' . BApp::href($this->_gridHref . '/duplicate?id=' . $m->id) .
                '" title="Duplicate" class="btn btn-primary"><span>' .  BLocale::_('Duplicate') . '</span></a>';
        }
        $newAction['saveAndContinue'] = '<button type="submit" class="btn btn-primary" name="do" value="saveAndContinue"><span>'
            . BLocale::_('Save And Continue') . '</span></button>';
        $actions = array_merge($args['view']->actions, $newAction);
        $args['view']->set([
            'sidebar_img' => $m->thumbUrl(98),
            'title' => $m->id ? 'Edit Product: ' . $m->product_name : 'Create New Product',
            'actions' => $actions
        ]);
        $this->_formTitle = $m->id ? 'Edit Product: ' . $m->product_name : 'Create New Product';
    }

    public function openCategoriesData($model)
    {
        $cp = FCom_Catalog_Model_CategoryProduct::i();
        $categories = $cp->orm('cp')->where('product_id', $model->id())
            ->join('FCom_Catalog_Model_Category', ['c.id', '=', 'cp.category_id'], 'c')
            ->select('c.id_path')
            ->find_many();
        if (!$categories) {
            return BUtil::toJson([]);
        }
        $result = [];
        foreach ($categories as $c) {
            $idPathArr = explode('/', $c->id_path);
            foreach ($idPathArr as $id) {
                $result[] = 'category_id-' . $id;
            }
        }
        return BUtil::toJson($result);
    }

    public function linkedCategoriesData($model)
    {
        $cp = FCom_Catalog_Model_CategoryProduct::i();
        $categories = $cp->orm()->where('product_id', $model->id())->find_many();
        if (!$categories) {
            return BUtil::toJson([]);
        }
        $result = [];
        foreach ($categories as $c) {
            $result[] = 'category_id-' . $c->category_id;
        }
        return BUtil::toJson($result);
    }

    public function productLibraryGridConfig($gridId = false)
    {
        $config = $this->gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 55, 'hidden' => true],
            ['name' => 'product_name', 'label'   => 'Name', 'index'   => 'p.product_name',
                   'width' => 450, 'addable' => true],
            ['name' => 'local_sku', 'label' => 'SKU', 'index' => 'p.local_sku', 'width' => 70],
        ];

//        unset( $config[ 'columns' ][ 'product_name' ][ 'formatter' ], $config[ 'columns' ][ 'product_name' ][ 'formatoptions' ] );
//        $config[ 'columns' ][ 'create_at' ][ 'hidden' ] = true;
        if ($gridId) {
            $config['id'] = $gridId;
        }
        $config['caption'] = 'All products';

        $config['actions'] = [
            'add' => ['caption' => 'Add selected products']
        ];
        $config['grid_before_create'] = 'prodLibGridRegister';
        //$config['custom']['autoresize'] = '#linked-products-layout';
        return ['config' => $config];
    }

    public function productAttachmentsGridConfig($model)
    {
        $download_url = BApp::href('/media/grid/download?folder=media/product/attachment&file=');
        return [
            'config' => [
                'id' => 'product_attachments',
                'caption' => 'Product Attachments',
                'data_mode' => 'local',
                'data' => BDb::many_as_array($model->mediaORM('A')->order_by_expr('pa.position asc')
                    ->select(['pa.id', 'pa.product_id', 'pa.remote_url', 'pa.position', 'pa.label', 'a.file_name',
                        'a.file_size', 'pa.create_at', 'pa.update_at'])
                    ->select('a.id', 'file_id')->find_many()),
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'download_url',  'hidden' => true, 'default' => $download_url],
                    ['name' => 'id', 'label' => 'ID', 'width' => 400, 'hidden' => true],
                    ['name' => 'file_id', 'label' => 'File ID', 'width' => 400, 'hidden' => true],
                    ['name' => 'product_id', 'label' => 'Product ID', 'width' => 400, 'hidden' => true, 'default' => $model->id()],
                    ['name' => 'file_name', 'label' => 'File Name', 'width' => 200, 'display' => 'eval',
                        'print' => '"<a class=\'file-attachments\' data-file-id=\'"+rc.row["file_id"]+"\' '
                            . 'href=\'"+rc.row["download_url"]+rc.row["file_name"]+"\'>"+rc.row["file_name"]+"</a>"'],
                    ['name' => 'file_size', 'label' => 'File Size', 'width' => 200, 'display' => 'file_size'],
                    ['type' => 'input', 'name' => 'label', 'label' => 'Label', 'width' => 250, 'editable' => 'inline'],
                    ['type' => 'input', 'name' => 'position', 'label' => 'Position', 'width' => 50,
                        'editable' => 'inline', 'validation' => ['number' => true]],
                    ['name' => 'create_at', 'label' => 'Created', 'width' => 200],
                    ['name' => 'update_at', 'label' => 'Updated', 'width' => 200],
                    ['type' => 'btn_group', 'buttons' => [['name' => 'delete']]],
                ],
                'actions' => [
                    'add' => ['caption' => 'Add attachments'],
                    'delete' => ['caption' => 'Remove']
                ],
                'grid_before_create' => 'attachmentGridRegister',
                'filters' => [
                    ['field' => 'file_name', 'type' => 'text'],
                    ['field' => 'label', 'type' => 'text'],
                    '_quick' => ['expr' => 'file_name like ? ', 'args' => ['%?%']]
                ]
            ]
        ];
    }

    public function productImagesGridConfig($model)
    {
        $downloadUrl = BApp::href('/media/grid/download?folder=media/product/images&file=');
        $thumbUrl = FCom_Core_Main::i()->resizeUrl(BConfig::i()->get('web/media_dir') . '/product/images', ['s' => 100]);
        $data = BDb::many_as_array($model->mediaORM('I')
                ->order_by_expr('pa.position asc')
                ->left_outer_join('FCom_Catalog_Model_ProductMedia', ['pa.file_id', '=', 'pm.file_id'], 'pm')
                ->select(['pa.id', 'pa.product_id', 'pa.remote_url', 'pa.position', 'pa.label', 'a.file_name',
                    'a.file_size', 'pa.create_at', 'pa.update_at', 'pa.main_thumb'])
                ->select('a.id', 'file_id')
                ->select_expr('IF (a.subfolder is null, "", CONCAT("/", a.subfolder))', 'subfolder')
                ->group_by('pa.id')
                ->find_many());
        return [
            'config' => [
                'id' => 'product_images',
                'caption' => 'Product Images',
                'data_mode' => 'local',
                'data' => $data,
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'hidden' => true],
                    ['name' => 'file_id',  'hidden' => true],
                    ['name' => 'product_id', 'hidden' => true, 'default' => $model->id()],
                    ['name' => 'download_url',  'hidden' => true, 'default' => $downloadUrl],
                    ['name' => 'thumb_url',  'hidden' => true, 'default' => $thumbUrl],
                    ['name' => 'file_name', 'label' => 'File Name'],
                    ['name' => 'prev_img', 'label' => 'Preview', 'width' => 110, 'display' => 'eval',
                        'print' => '"<a href=\'"+rc.row["download_url"]+rc.row["subfolder"]+"/"+rc.row["file_name"]+"\'>'
                            . '<img src=\'"+rc.row["thumb_url"]+rc.row["subfolder"]+"/"+rc.row["file_name"]+"\' '
                            . 'alt=\'"+rc.row["file_name"]+"\' ></a>"',
                        'sortable' => false],
                    ['name' => 'file_size', 'label' => 'File Size', 'width' => 200, 'display' => 'file_size'],
                    ['type' => 'input', 'name' => 'label', 'label' => 'Label', 'width' => 250, 'editable' => 'inline'],
                    ['type' => 'input', 'name' => 'position', 'label' => 'Position', 'width' => 50,
                        'editable' => 'inline', 'validation' => ['number' => true]],
                    ['name' => 'main_thumb', 'label' => 'Thumbnail', 'width' => 50, 'display' => 'eval',
                        'print' => '"<input class=\'main-thumb\' value=\'"+rc.row["id"]+"\' type=\'radio\' '
                            . 'data-file-id=\'"+rc.row["file_id"]+"\' name=\'product_images[main_thumb]\' '
                            . 'data-main-thumb=\'"+rc.row["main_thumb"]+"\'/>"'],
                    ['name' => 'create_at', 'label' => 'Created', 'width' => 200],
                    ['name' => 'update_at', 'label' => 'Updated', 'width' => 200],
                    ['type' => 'btn_group', 'name' => '_actions', 'label' => 'Actions', 'sortable' => false,
                        'buttons' => [['name' => 'delete']]],
                ],
                'actions' => [
                    'refresh' => true,
                    'add' => ['caption' => 'Add images'],
                    'delete' => ['caption' => 'Remove'],
                ],
                'grid_before_create' => 'imagesGridRegister',
                'afterMassDelete' => 'afterMassDelete',
                'filters' => [
                    ['field' => 'file_name', 'type' => 'text'],
                    ['field' => 'label', 'type' => 'text'],
                    '_quick' => ['expr' => 'file_name like ? ', 'args' => ['%?%']]
                ],

            ]
        ];
    }

    /**
    * modal grid on category/product tab
    */
    public function getAllProdConfig($model)
    {

        $config = parent::gridConfig();
        //$config['id'] = 'category_all_prods_grid-'.$model->id;
        $config['id'] = 'category_all_prods_grid_' . $model->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 55, 'hidden' => true],
            ['name' => 'product_name', 'label' => 'Name', 'index' => 'p.product_name', 'width' => 250],
            ['name' => 'local_sku', 'label' => 'SKU', 'index' => 'p.local_sku', 'width' => 100],
        ];
        $config['actions'] = [
            'add' => ['caption' => 'Add selected products']
        ];
        $config['filters'] = [
            ['field' => 'product_name', 'type' => 'text'],
            ['field' => 'local_sku', 'type' => 'text'],
            '_quick' => ['expr' => 'product_name like ? or local_sku like ? or p.id=?', 'args' => ['?%', '%?%', '?']]
        ];

        $config['grid_before_create'] = 'allProdGridRegister';
        /*$config['_callbacks'] = "{
            'add':'categoryProdsMng.addSelectedProds'
        }";*/


        return ['config' => $config];
    }

    /*
    *main grid on category/product tab
    */
    public function getCatProdConfig($model)
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')
            ->select(['p.id', 'p.product_name', 'p.local_sku'])
            ->join('FCom_Catalog_Model_CategoryProduct', ['cp.product_id', '=', 'p.id'], 'cp')
            ->where('cp.category_id', $model ? $model->id : 0)
        ;

        $config = parent::gridConfig();

        // TODO for empty local grid, it throws exception
        unset($config['orm']);
        $config['data'] = $orm->find_many();
        $config['id'] = 'category_prods_grid_' . $model->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 80, 'hidden' => true],
            ['name' => 'product_name', 'label' => 'Name', 'index' => 'p.product_name', 'width' => 400],
            ['name' => 'local_sku', 'label' => 'SKU', 'index' => 'p.local_sku', 'width' => 200]
        ];
        $config['actions'] = [
            'add' => ['caption' => 'Add products'],
            'delete' => ['caption' => 'Remove']
        ];
        $config['filters'] = [
            ['field' => 'product_name', 'type' => 'text'],
            ['field' => 'local_sku', 'type' => 'text']
        ];
        $config['data_mode'] = 'local';
        $config['grid_before_create'] = 'catProdGridRegister';

        return ['config' => $config];
    }

    public function linkedProductGridConfig($model, $type)
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')
            ->select(['p.id', 'p.product_name', 'p.local_sku', 'p.base_price', 'p.sale_price']);

        switch ($type) {
        case 'related': case 'similar':case 'cross_sell':
            $orm->join('FCom_Catalog_Model_ProductLink', ['pl.linked_product_id', '=', 'p.id'], 'pl')
                ->select_expr('pl.position', 'product_link_position')
                ->where('link_type', $type)
                ->where('pl.product_id', $model ? $model->id : 0);

            //TODO: flexibility for more types
            $caption = $type == 'related' ? 'Related Products' : 'Similar Products';
            break;

        default:
            $caption = '';
        }

        $gridId = 'linked_products_' . $type;

        $config = [
                'id'           => $gridId,
                'data'         => null,
                'data_mode'     => 'local',
                //'caption'      =>$caption,
                'columns'      => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 80, 'hidden' => true],
                    ['name' => 'product_name', 'label' => 'Name', 'index' => 'p.product_name', 'width' => 400],
                    ['name' => 'local_sku', 'label' => 'SKU', 'index' => 'p.local_sku', 'width' => 200],
                    ['name' => 'base_price', 'label' => 'Base Price', 'index' => 'p.base_price'],
                    ['name' => 'sale_price', 'label' => 'Sale Price', 'index' => 'p.sale_price'],
                    ['name' => 'product_link_position', 'label' => 'Position', 'index' => 'pl.position', 'width' => 50,
                        'editable' => 'inline', 'validation' => ['number' => true], 'type' => 'input'],
                ],
                'actions' => [
                    'add' => ['caption' => 'Add products'],
                    'delete' => ['caption' => 'Remove']
                ],
                'filters' => [
                    ['field' => 'product_name', 'type' => 'text'],
                    ['field' => 'local_sku', 'type' => 'text']
                ],
                'events' => ['init', 'add', 'mass-delete'],
                'grid_before_create' => $gridId . '_register'
            ];


        //BEvents::i()->fire(__METHOD__.':orm', array('type'=>$type, 'orm'=>$orm));
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

        //BEvents::i()->fire(__METHOD__.':config', array('type'=>$type, 'config'=>&$config));
        return ['config' => $config];
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        $model = $args['model'];
        $data = BRequest::i()->post();


        if (isset($data['do']) && $data['do'] === 'DELETE') {
            $this->deleteRelateInfo($model);
        } else {
            if (!$args['validateFailed']) {
                $this->processCategoriesPost($model);
                $this->processLinkedProductsPost($model, $data);
                $this->processMediaPost($model, $data);
                $this->processCustomFieldPost($model, $data);
                $this->processVariantPost($model, $data);
                $this->processSystemLangFieldsPost($model, $data);
                $this->processFrontendPost($model, $data);
            }
        }
    }

    /**
     * delete all associate info with this product
     * @param $model
     */
    public function deleteRelateInfo($model)
    {
        //delete Categories
        FCom_Catalog_Model_CategoryProduct::i()->delete_many([
           'product_id' => $model->id
        ]);
        //delete Product Link
        FCom_Catalog_Model_ProductLink::i()->delete_many([
            'product_id' => $model->id
        ]);
        //delete Product Media
        FCom_Catalog_Model_ProductMedia::i()->delete_many([
            'product_id' => $model->id
        ]);
        //todo: delete product reviews / wishlist
    }

    public function processCategoriesPost($model)
    {
        $post = BRequest::i()->post();
        $categories = [];
        foreach ($post as $key => $value) {
            $matches = [];
            if (preg_match("#category_id-(\d+)#", $key, $matches)) {
                $categories[intval($matches[1])] = $value;
            }
        }
        if (!empty($categories)) {
            $cat_product = FCom_Catalog_Model_CategoryProduct::i();
            $category_model = FCom_Catalog_Model_Category::i();

            foreach ($categories as $cat_id => $value) {
                $product = $cat_product->orm()->where('product_id', $model->id())->where('category_id', $cat_id)->find_one();
                if (0 == $value && $product) {
                    $product->delete();
                } elseif (false == $product) {
                    $data = ['product_id' => $model->id(), 'category_id' => $cat_id];
                    FCom_Catalog_Model_CategoryProduct::i()->create($data)->save();
                }
            }
        }
    }
    public function processLinkedProductsPost($model, $data)
    {
        //echo "<pre>"; print_r($data); echo "</pre>";
        $hlp = FCom_Catalog_Model_ProductLink::i();
        foreach (['related', 'similar', 'cross_sell'] as $type) {
            $typeName = 'linked_products_' . $type;
            if (!empty($data['grid'][$typeName]['del'])) {
                $hlp->delete_many([
                    'product_id' => $model->id,
                    'link_type' => $type,
                    'linked_product_id' => explode(',', $data['grid'][$typeName]['del']),
                ]);
            }
            if (isset($data[$typeName])) {
                foreach ($data[$typeName] as $key => $arr) {
                    $productLink = $hlp->load(['product_id' => $model->id, 'linked_product_id' => $key, 'link_type' => $type]);
                    $position = (is_numeric($data[$typeName][$key]['product_link_position']))
                        ? (int) $data[$typeName][$key]['product_link_position'] : 0;
                    if ($productLink) {
                        $productLink->set('position', $position)->save();
                    } else {
                        $hlp->create([
                            'product_id' => $model->id,
                            'link_type' => $type,
                            'linked_product_id' => $key,
                            'position' => $position
                        ])->save();
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
        foreach (['A' => 'attachments', 'I' => 'images'] as $type => $typeName) {

            if (!empty($data['grid'][$typeName]['del'])) {
                $hlp->delete_many([
                    'product_id' => $model->id,
                    'media_type' => $type,
                    'id'   => explode(',', $data['grid'][$typeName]['del']),
                ]);
            }

            if (!empty($data['grid'][$typeName]['rows'])) {
                $rows = BUtil::fromJson($data['grid'][$typeName]['rows']);
                foreach ($rows as $image) {
                    $key = $image['id'];
                    unset($image['id']);
                    if ($key != 'main_thumb') {
                        $mediaModel =  $hlp->load($key);
                        $main_thumb = 0;
                        if ($type == 'I') {
                            if (isset($data['product_' . $typeName]['main_thumb'])
                                && $data['product_' . $typeName]['main_thumb'] == $key
                            ) {
                                $main_thumb = 1;
                            }
                            $image['main_thumb'] = $main_thumb;
                        }

                        if (isset($image['position'])) {
                            $image['position'] = (is_numeric($image['position'])) ? (int) $image['position'] : 0;
                        }

                        if ($mediaModel) {
                            $mediaModel->set($image)->save();
                        } else {
                            $productMediaModel = $hlp->orm()->where('product_id', $model->id)
                                ->where('file_id', $image['file_id'])->find_one();
                            if (!$productMediaModel) {
                                $image['file_id'] = (int) $image['file_id'];
                                $image['product_id'] = $model->id;
                                $image['media_type'] = $type;

                                //TODO remote_url and file_path can be fetched based on file_id. Beside, file_name can be changed in media libary.
                                //'remote_url' =>BApp::href('/media/grid/download?folder=media/product/attachment&file_='.$row['file_id']),
                                $hlp->create($image)->save();
                            }
                        }
                    }

                }
            }

        }
        $productMediaModel = $hlp->orm()->where('media_type', 'I')->where('product_id', $model->id)
            ->where('main_thumb', 1)->find_one();
        $thumbUrl = NULL;
        if ($productMediaModel) {
            $mediaLibModel = FCom_Core_Model_MediaLibrary::i()->load($productMediaModel->get('file_id'));
            $thumbUrl = ($mediaLibModel->get('subfolder') != null)
                ? $mediaLibModel->get('folder') . '/' . $mediaLibModel->get('subfolder') . '/' . $mediaLibModel->get('file_name')
                : $mediaLibModel->get('folder') . '/' . $mediaLibModel->get('file_name');
            $thumbUrl = preg_replace('#^media/#', '', $thumbUrl); //TODO: resolve the dir string ambiguity
        }
        $model->set('thumb_url', $thumbUrl)->save();
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
        array_splice($args['config']['grid']['colModel'], -1, 0, [
            ['name' => 'manuf_vendor_name', 'label' => 'Manufacturer', 'width' => 150, 'index' => 'v.vendor_name', 'editable' => true],
        ]);
    }

    public function onMediaGridGetORM($args)
    {
        $args['orm']->join('FCom_Catalog_Model_ProductMedia', ['pa.file_id', '=', 'a.id',  ], 'pa')
            ->where_null('pa.product_id')->where('media_type', $args['type'])
            ->select(['pa.manuf_vendor_id']);
    }

    public function onMediaGridUpload($args)
    {
        $hlp = FCom_Catalog_Model_ProductMedia::i();
        $id = $args['model']->id;
        if (!$hlp->load(['product_id' => null, 'file_id' => $id])) {
            $hlp->create(['file_id' => $id, 'media_type' => $args['type']])->save();
        }
    }

    public function onMediaGridEdit($args)
    {
        $r = BRequest::i();
        $m = Denteva_Model_Vendor::i()->load([
            'is_manuf' => 1,
            'vendor_name' => $r->post('manuf_vendor_name')
        ]);
        FCom_Catalog_Model_ProductMedia::i()
            ->load(['product_id' => null, 'file_id' => $args['model']->id])
            ->set([
                'manuf_vendor_id' => $m ? $m->id : null,
            ])
            ->save();
    }

    public function action_duplicate()
    {
        $id = BRequest::i()->param('id', true);
        $redirectUrl = BApp::href($this->_formHref) . '?id=' . $id;
        try {
            $oldModel = FCom_Catalog_Model_Product::i()->load($id);
            /** @var $oldModel FCom_Catalog_Model_Product */
            if ($oldModel) {
                $data = $oldModel->as_array();
                unset($data['id']);
                $newModel = FCom_Catalog_Model_Product::i()->create($data);
                /** @var $newModel FCom_Catalog_Model_Product */
                $number = $this->getDuplicateSuffixNumber($oldModel->product_name, $oldModel->local_sku, $oldModel->url_key);
                $newModel->product_name = $newModel->product_name . '-' . $number;
                $newModel->url_key = $newModel->url_key . '-' . $number;
                $newModel->local_sku = $newModel->local_sku . '-' . $number;
                $newModel->create_at = $newModel->update_at = date('Y-m-d H:i:s');
                $newModel->is_hidden = 1;
                if ($newModel->save()
                        && $this->duplicateProductCategories($oldModel, $newModel)
                        && $this->duplicateProductLink($oldModel, $newModel)
                        && $this->duplicateProductMedia($oldModel, $newModel)
                        && $this->duplicateProductReviews($oldModel, $newModel)
                ) {
                    $redirectUrl = BApp::href($this->_formHref) . '?id=' . $newModel->id;
                    $this->message('Duplicate successful');
                } else {
                    $this->message('An error occurred while creating model.', 'error');
                }
            } else {
                $this->message('Cannot load model with id ' . $id, 'error');
            }
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
        }

        BResponse::i()->redirect($redirectUrl);
    }

    public function getDuplicateSuffixNumber($oldName, $oldSku, $oldUrlKey)
    {
        $result = FCom_Catalog_Model_Product::i()->orm()
            ->where(['OR' => [
                ['product_name REGEXP ?', $oldName . '-[0-9]$'],
                ['local_sku REGEXP ?', $oldSku . '-[0-9]$'],
                ['url_key REGEXP ?', $oldUrlKey . '-[0-9]$'],
            ]])
            ->order_by_desc('id')->find_one();
        $numberSuffix = 1;
        if ($result) {
            foreach ($result as $arr) {
                $tmpName = explode($oldName . '-', $arr->get('product_name'));
                $tmpSku = explode($oldSku . '-', $arr->get('local_sku'));
                $tmpKey = explode($oldUrlKey . '-', $arr->get('url_key'));
                $max = $tmpName[1];
                $tmpSku[1] = ($tmpSku[1] < $tmpKey[1]) ? $tmpKey[1] : $tmpSku[1];
                $max = ($max < $tmpSku[1]) ? $tmpSku[1] : $max;
            }
            $numberSuffix = $max + 1;
        }
        return $numberSuffix;
    }

    /**
     * @param $old FCom_Catalog_Model_Product
     * @param $new FCom_Catalog_Model_Product
     * @return bool
     */
    public function duplicateProductCategories($old, $new)
    {
        $categories = $old->categories(true);
        if ($categories) {
            $categoryIds = [];
            //todo: request Boris for same function _.pluck in BUtil
            foreach ($categories as $category) {
                $categoryIds[] = $category->id;
            }
            $new->addToCategories($categoryIds);
        }
        return true;
    }

    /**
     * @param $old FCom_Catalog_Model_Product
     * @param $new FCom_Catalog_Model_Product
     * @return bool
     */
    public function duplicateProductLink($old, $new)
    {
        //todo: does we need add product link similar between old and new product
        $hlp = FCom_Catalog_Model_ProductLink::i();
        $links = $hlp->orm('pl')->where('product_id', $old->id)->find_many();
        if ($links) {
            foreach ($links as $link) {
                $data = [
                    'product_id'        => $new->id,
                    'link_type'         => $link->link_type,
                    'linked_product_id' => $link->linked_product_id,
                ];
                if (!$hlp->create($data)->save()) {
                    $this->message('An error occurred while duplicate product links.', 'error');
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param $old FCom_Catalog_Model_Product
     * @param $new FCom_Catalog_Model_Product
     * @return bool
     */
    public function duplicateProductMedia($old, $new)
    {
        $hlp = FCom_Catalog_Model_ProductMedia::i();
        $medias = $hlp->orm('pa')->where('pa.product_id', $old->id)->select('pa.*')->find_many();
        if ($medias) {
            foreach ($medias as $media) {
                $data = $media->as_array();
                unset($data['id']);
                $data['product_id'] = $new->id;
                $data['create_at'] = $data['update_at'] = date('Y-m-d H:i:s');
                if (!$hlp->create($data)->save()) {
                    $this->message('An error occurred while duplicate product medias.', 'error');
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param $old FCom_Catalog_Model_Product
     * @param $new FCom_Catalog_Model_Product
     * @return bool
     */
    public function duplicateProductReviews($old, $new)
    {
        //todo: confirm need duplicate product review or not
        $hlp = FCom_ProductReviews_Model_Review::i();
        $reviews = $hlp->orm('pr')->where('product_id', $old->id)->find_many();
        if ($reviews) {
            foreach ($reviews as $r) {
                $data = $r->as_array();
                unset($data['id']);
                $data['product_id'] = $new->id;
                if (!$hlp->create($data)->save()) {
                    $this->message('An error occurred while duplicate product reviews.', 'error');
                    return false;
                }
            }
        }
        return true;
    }

    public function onHeaderSearch($args)
    {
        $r = BRequest::i()->get();
        if (isset($r['q']) && $r['q'] != '') {
            $value = '%' . $r['q'] . '%';
            $result = FCom_Catalog_Model_Product::i()->orm('p')
                ->where(['OR' => [
                    ['p.id like ?', $value],
                    ['p.local_sku like ?', $value],
                    ['p.url_key like ?', $value],
                    ['p.product_name like ?', $value],
                ]])->find_one();
            $args['result']['product'] = null;
            if ($result) {
                $args['result']['product'] = [
                    'priority' => 1,
                    'url' => BApp::href($this->_formHref) . '?id=' . $result->id()
                ];
            }
        }
    }

    public function onGenerateSiteMap($args)
    {
        $callback = function ($row) use ($args) {
            array_push($args['site_map'], ['loc' => BApp::frontendHref($row->get('url_key')), 'changefreq' => 'daily']);
        };
        FCom_Catalog_Model_Product::i()->orm()->select('url_key')->iterate($callback);
    }
}
