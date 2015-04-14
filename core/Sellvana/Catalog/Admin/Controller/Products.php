<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Admin_Controller_Products
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_CustomField_Model_ProductVariant $Sellvana_CustomField_Model_ProductVariant
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_CategoryProduct $Sellvana_Catalog_Model_CategoryProduct
 * @property Sellvana_Catalog_Model_ProductLink $Sellvana_Catalog_Model_ProductLink
 * @property Sellvana_Catalog_Model_ProductMedia $Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_CustomField_Model_FieldOption $Sellvana_CustomField_Model_FieldOption
 * @property Sellvana_ProductReviews_Model_Review $Sellvana_ProductReviews_Model_Review
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property FCom_Core_Main $FCom_Core_Main
 * @property FCom_Core_Model_MediaLibrary $FCom_Core_Model_MediaLibrary
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 */
class Sellvana_Catalog_Admin_Controller_Products extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Catalog_Model_Product';
    protected $_gridHref = 'catalog/products';
    protected $_gridTitle = 'Products';
    protected $_recordName = 'Product';
    protected $_mainTableAlias = 'p';
    protected $_permission = 'catalog/products';
    protected $_formLayoutName = '/catalog/products/form';
    protected $_formTitleField = 'product_name';

    /**
     * @return array
     */
    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select', 'width' => 55],
            ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 55, 'hidden' => true],
            ['display' => 'eval', 'name' => 'thumb_path', 'label' => 'Thumbnail', 'width' => 48, 'sortable' => false,
                'print' => '"<img src=\'"+rc.row["thumb_path"]+"\' alt=\'"+rc.row["product_name"]+"\' >"'],
            ['name' => 'product_name', 'label' => 'Name', 'width' => 250],
            ['name' => 'product_sku', 'label' => 'SKU', 'index' => 'p.product_sku', 'width' => 100],
            ['name' => 'short_description', 'label' => 'Description',  'width' => 200],
            //['name' => 'base_price', 'label' => 'Base Price',  'width' => 100, 'hidden' => true],
            //['name' => 'sale_price', 'label' => 'Sale Price',  'width' => 100, 'hidden' => true],
            ['name' => 'net_weight', 'label' => 'Net Weight',  'width' => 100, 'hidden' => true],
            ['name' => 'ship_weight', 'label' => 'Ship Weight',  'width' => 100, 'hidden' => true],
            ['name' => 'position', 'label' => 'Position', 'index' => 'p.position', 'hidden' => true],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 'p.create_at', 'width' => 100],
            ['name' => 'update_at', 'label' => 'Updated', 'index' => 'p.update_at', 'width' => 100],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit', 'href' => $this->BApp->href('catalog/products/form?id=')],
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
            ['field' => 'product_sku', 'type' => 'text'],
            ['field' => 'short_description', 'type' => 'text'],
            //['field' => 'base_price', 'type' => 'number-range'],
            //['field' => 'sale_price', 'type' => 'number-range'],
            ['field' => 'net_weight', 'type' => 'number-range'],
            ['field' => 'ship_weight', 'type' => 'number-range'],
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'update_at', 'type' => 'date-range'],
            '_quick' => ['expr' => 'product_name like ? or product_sku like ? or p.id=?', 'args' => ['?%', '%?%', '?']]
        ];
        $config['page_rows_data_callback'] = [$this, 'afterInitialData'];
        return $config;
    }

    /**
     * @param $rows
     * @return mixed
     */
    public function afterInitialData($rows)
    {
        $mediaUrl = $this->BConfig->get('web/media_dir') ? $this->BConfig->get('web/media_dir') : 'media';
        $hlp = $this->FCom_Core_Main;
        foreach ($rows as & $row) {
            $thumbUrl = $row['thumb_url'] ? $row['thumb_url'] : 'image-not-found.png';
            $row['thumb_path'] = $hlp->resizeUrl($mediaUrl . '/' . $thumbUrl, ['s' => 68]);
        }
        return $rows;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function gridDataAfter($data)
    {
        $mediaUrl = $this->BConfig->get('web/media_dir') ? $this->BConfig->get('web/media_dir') : 'media';
        $hlp = $this->FCom_Core_Main;

        $data = parent::gridDataAfter($data);
        foreach ($data['rows'] as $row) {
            /** @var Sellvana_Catalog_Model_Product $row */
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

    /**
     * @param array $args
     */
    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        /** @var Sellvana_Catalog_Model_Product $m */
        $m = $args['model'];
        $newAction = [];
        if ($m->id) {
            $newAction['duplicate'] = [
                'button',
                [
                    'type' => 'submit',
                    'class' => ['btn', 'btn-primary', 'ignore-validate'],
                    'name' => 'do',
                    'value' => 'DUPLICATE',
                    'onclick' => 'return confirm(\'Are you sure?\')',
                ],
                [
                    ['span', null, $this->_('Duplicate')],
                ]
            ];
        }
        $newAction['save_and_continue'] = [
            'button',
            [
                'type' => 'submit',
                'class' => ['btn', 'btn-primary'],
                'name' => 'do',
                'value' => 'save_and_continue',
            ],
            [
                ['span', null, $this->BLocale->_('Save And Continue')],
            ]
        ];
        $actions = array_merge($args['view']->actions, $newAction);
        $args['view']->set([
            'sidebar_img' => $m->thumbUrl(98),
            'actions' => $actions,
        ]);
    }

    /**
     * @param array $args
     */
    public function formPostBefore($args)
    {
        parent::formPostBefore($args);

        if ($args['do'] == 'DUPLICATE') {
            $this->duplicateProduct($args['id']);
            exit();
        }

        $layout = $this->FCom_Core_LayoutEditor->processFormPost();
        if ($layout) {
            $args['model']->setData('layout', $layout);
        }
    }

    /**
     * @param $model
     * @return string
     */
    public function openCategoriesData($model)
    {
        $cp = $this->Sellvana_Catalog_Model_CategoryProduct;
        $categories = $cp->orm('cp')->where('product_id', $model->id())
            ->join('Sellvana_Catalog_Model_Category', ['c.id', '=', 'cp.category_id'], 'c')
            ->select('c.id_path')
            ->find_many();
        if (!$categories) {
            return $this->BUtil->toJson([]);
        }
        $result = [];
        foreach ($categories as $c) {
            $idPathArr = explode('/', $c->id_path);
            foreach ($idPathArr as $id) {
                $result[] = 'category_id-' . $id;
            }
        }
        return $this->BUtil->toJson($result);
    }

    /**
     * @param $model Sellvana_Catalog_Model_Product
     * @return string
     */
    public function linkedCategoriesData($model)
    {
        $cp = $this->Sellvana_Catalog_Model_CategoryProduct;
        $categories = $cp->orm()->where('product_id', $model->id())->find_many();
        if (!$categories) {
            return $this->BUtil->toJson([]);
        }
        $result = [];
        foreach ($categories as $c) {
            $result[] = 'category_id-' . $c->category_id;
        }
        return $this->BUtil->toJson($result);
    }

    /**
     * @param bool $gridId
     * @return array
     */
    public function productLibraryGridConfig($gridId = false)
    {
        $config = $this->gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 55, 'hidden' => true],
            ['name' => 'product_name', 'label'   => 'Name', 'index'   => 'p.product_name',
                'width' => 450, 'addable' => true],
            ['name' => 'product_sku', 'label' => 'SKU', 'index' => 'p.product_sku', 'width' => 70],
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

    /**
     * @param $model Sellvana_Catalog_Model_Product
     * @return array
     */
    public function productAttachmentsGridConfig($model)
    {
        $download_url = $this->BApp->href('/media/grid/download?folder=media/product/attachment&file=');
        $data = $this->BDb->many_as_array($model->mediaORM(Sellvana_Catalog_Model_ProductMedia::MEDIA_TYPE_ATTCH)->order_by_expr('pa.position asc')
            ->select(['pa.id', 'pa.product_id', 'pa.remote_url', 'pa.position', 'pa.label', 'a.file_name', 'a.file_size', 'pa.create_at', 'pa.update_at'])
            ->select('a.id', 'file_id')->find_many());

        return [
            'config' => [
                'id' => 'product_attachments',
                'caption' => 'Product Attachments',
                'data_mode' => 'local',
                'data' => $data,
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

    /**
     * todo: this method will be remove after test
     * @param $model Sellvana_Catalog_Model_Product
     * @return array
     */
    public function productAttachmentsGridConfigForGriddle($model) {
        $config = $this->productAttachmentsGridConfig($model);
        unset($config['config']['actions']['add']);
        $config['config']['actions'] += [
            'add-attachment' => [
                'caption'  => 'Add attachments',
                'type'     => 'button',
                'id'       => 'add-attachment-from-grid',
                'class'    => 'btn-primary',
                'callback' => 'gridShowMedia' . $config['config']['id']
            ]
        ];

        $config['config']['callbacks'] = [
            'componentDidMount' => 'gridRegister' . $config['config']['id']
        ];

        return $config;
    }

    /**
     * @param $model Sellvana_Catalog_Model_Product
     * @return array
     */
    public function productImagesGridConfig($model)
    {
        $downloadUrl = $this->BApp->href('/media/grid/download?folder=media/product/images&file=');
        $thumbUrl = $this->FCom_Core_Main->resizeUrl($this->BConfig->get('web/media_dir') . '/product/images', ['s' => 100]);
        $data = $this->BDb->many_as_array($model->mediaORM(Sellvana_Catalog_Model_ProductMedia::MEDIA_TYPE_IMG)
            ->order_by_expr('pa.position asc')
            ->left_outer_join('Sellvana_Catalog_Model_ProductMedia', ['pa.file_id', '=', 'pm.file_id'], 'pm')
            ->select(['pa.id', 'pa.product_id', 'pa.remote_url', 'pa.position', 'pa.label', 'a.file_name',
                'a.file_size', 'pa.create_at', 'pa.update_at', 'pa.main_thumb'])
            ->select('a.id', 'file_id')
            ->select_expr('IF (a.subfolder is null, "", CONCAT("/", a.subfolder))', 'subfolder')
            ->group_by('pa.id')
            ->find_many());
        $config =  [
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
                            . ' "+(rc.row["main_thumb"]==1 ? checked=\'checked\' : \'\')+" '
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
                    'quick_add' => [
                        'html' => '<span class="btn btn-success fileinput-button" style="float: none;line-height: 23px;">
                                     <i class="icon-plus icon-white"></i>
                                     <span>Quick add images</span> <input type="file" name="upload[]" id="quick-add-images" multiple="">
                                   </span>'
                    ],
                    'delete' => ['caption' => 'Remove'],
                ],
                'grid_before_create' => 'imagesGridRegister',
                'grid_after_built' => 'afterBuiltImagesGrid',
                'afterMassDelete' => 'afterMassDelete',
                'filters' => [
                    ['field' => 'file_name', 'type' => 'text'],
                    ['field' => 'label', 'type' => 'text'],
                    //TODO: remove all unused server side '_quick' filters
                    '_quick' => ['expr' => 'file_name like ? ', 'args' => ['%?%']]
                ],

            ]
        ];

        return $config;
    }

    /**
     * //todo: remove after finish griddle
     * temporary function to build griddle, will be removed after test
     * @param $model
     * @return array
     */
    public function productImagesGridConfigForGriddle($model)
    {
        $config = $this->productImagesGridConfig($model);
        unset($config['config']['actions']['add']);
        $config['config']['actions'] += [
            'add-images' => [
                'caption'  => 'Add images',
                'type'     => 'button',
                'id'       => 'add-image-from-grid',
                'class'    => 'btn-primary',
                'callback' => 'gridShowMedia' . $config['config']['id']
            ]
        ];

        $config['config']['callbacks'] = [
            'componentDidMount' => 'gridRegister' . $config['config']['id']
        ];

        return $config;
    }

    /**
     * modal grid on category/product tab
     * @param $model Sellvana_Catalog_Model_Product
     * @return array
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
            ['name' => 'product_sku', 'label' => 'SKU', 'index' => 'p.product_sku', 'width' => 100],
        ];
        $config['actions'] = [
            #'add' => ['caption' => 'Add selected products']
        ];
        $config['filters'] = [
            ['field' => 'product_name', 'type' => 'text'],
            ['field' => 'product_sku', 'type' => 'text'],
            '_quick' => ['expr' => 'product_name like ? or product_sku like ? or p.id=?', 'args' => ['?%', '%?%', '?']]
        ];

        $config['grid_before_create'] = 'allProdGridRegister';
        /*$config['_callbacks'] = "{
            'add':'categoryProdsMng.addSelectedProds'
        }";*/


        return ['config' => $config];
    }

    /**
     * main grid on category/product tab
     * @param $model Sellvana_Catalog_Model_Category
     * @return array
     */
    public function getCatProdConfig($model)
    {
        $orm = $this->Sellvana_Catalog_Model_Product->orm('p')
            ->select(['p.id', 'p.product_name', 'p.product_sku'])
            ->join('Sellvana_Catalog_Model_CategoryProduct', ['cp.product_id', '=', 'p.id'], 'cp')
            ->where('cp.category_id', $model ? $model->id : 0)
        ;

        $config = parent::gridConfig();

        // TODO for empty local grid, it throws exception
        unset($config['orm']);
        $config['config']['data'] = $orm->find_many();
        $config['config']['id'] = 'category_prods_grid_' . $model->id;
        $config['config']['data_mode'] = 'local';
        $config['config']['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 80, 'hidden' => true],
            ['name' => 'product_name', 'label' => 'Name', 'index' => 'p.product_name', 'width' => 400],
            ['name' => 'product_sku', 'label' => 'SKU', 'index' => 'p.product_sku', 'width' => 200]
        ];
        $config['config']['actions'] = [
            #'add' => ['caption' => 'Add products'],
            'add-product' => [
                'caption'  => 'Add Products',
                'type'     => 'button',
                'id'       => 'add-product-from-grid',
                'class'    => 'btn-primary',
                'callback' => 'showModalToAddProduct'
            ],
            'delete' => ['caption' => 'Remove']
        ];
        $config['config']['filters'] = [
            ['field' => 'product_name', 'type' => 'text'],
            ['field' => 'product_sku', 'type' => 'text']
        ];
        $config['config']['data_mode'] = 'local';
        $config['config']['grid_before_create'] = 'catProdGridRegister';

        $config['config']['callbacks'] = [
            'componentDidMount' => 'setCatProdMainGrid'
        ];

        return $config;
    }

    /**
     * @param $model
     * @param $type
     * @return array
     */
    public function linkedProductGridConfig($model, $type)
    {
        $orm = $this->Sellvana_Catalog_Model_Product->orm('p')
            ->select(['p.id', 'p.product_name', 'p.product_sku']);//, 'p.base_price', 'p.sale_price']);

        switch ($type) {
            case 'related': case 'similar':case 'cross_sell':
            $orm->join('Sellvana_Catalog_Model_ProductLink', ['pl.linked_product_id', '=', 'p.id'], 'pl')
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

        $config['config'] = [
            'id'           => $gridId,
            'data'         => null,
            'data_mode'     => 'local',
            //'caption'      =>$caption,
            'columns'      => [
                ['type' => 'row_select'],
                ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 80, 'hidden' => true],
                ['name' => 'product_name', 'label' => 'Name', 'index' => 'p.product_name', 'width' => 400],
                ['name' => 'product_sku', 'label' => 'SKU', 'index' => 'p.product_sku', 'width' => 200],
                //['name' => 'base_price', 'label' => 'Base Price', 'index' => 'p.base_price'],
                //['name' => 'sale_price', 'label' => 'Sale Price', 'index' => 'p.sale_price'],
                ['name' => 'product_link_position', 'label' => 'Position', 'index' => 'pl.position', 'width' => 50,
                    'editable' => 'inline', 'validation' => ['number' => true], 'type' => 'input'],
            ],
            'actions' => [
                #'add' => ['caption' => 'Add products'],
                'delete' => ['caption' => 'Remove']
            ],
            'filters' => [
                ['field' => 'product_name', 'type' => 'text'],
                ['field' => 'product_sku', 'type' => 'text']
            ],
            'events' => ['init', 'add', 'mass-delete'],
            'grid_before_create' => $gridId . '_register'
        ];

        switch ($type) {
            case 'related':
                $config['config']['actions'] += [
                    'add-related-product' => [
                        'caption'  => 'Add Related Products',
                        'type'     => 'button',
                        'id'       => 'add-related-product-from-grid',
                        'class'    => 'btn-primary',
                        'callback' => 'showModalToAddRelatedProduct'
                    ]
                ];
                break;
            case 'similar':
                $config['config']['actions'] += [
                    'add-similar-product' => [
                        'caption'  => 'Add Similar Products',
                        'type'     => 'button',
                        'id'       => 'add-similar-product-from-grid',
                        'class'    => 'btn-primary',
                        'callback' => 'showModalToAddSimilarProduct'
                    ]
                ];
                break;
            case 'cross_sell':
                $config['config']['actions'] += [
                    'add-cross-product' => [
                        'caption'  => 'Add Cross Sell Products',
                        'type'     => 'button',
                        'id'       => 'add-cross-product-from-grid',
                        'class'    => 'btn-primary',
                        'callback' => 'showModalToAddCrossProduct'
                    ]
                ];
                break;
        }


        //$this->BEvents->fire(__METHOD__.':orm', array('type'=>$type, 'orm'=>$orm));
        $data = $this->BDb->many_as_array($orm->find_many());
        //unset unused columns
        /*$columnKeys = array_keys($config['columns']);
        foreach($data as &$prod){
            foreach($prod as $k=>$p) {
                if (!in_array($k, $columnKeys)) {
                    unset($prod[$k]);
                }
            }
        }*/

        $config['config']['data'] = $data;

        $config['config']['type'] = $type;

        $config['config']['callbacks'] = [
            'componentDidMount' => 'setLinkedProdMainGrid'
        ];

        //$this->BEvents->fire(__METHOD__.':config', array('type'=>$type, 'config'=>&$config));
        return $config;
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);

        $model = $args['model'];
        $data = $this->BRequest->post();

        if (isset($data['do']) && $data['do'] === 'DELETE') {
            $this->deleteRelatedInfo($model);
        } else {
            if (empty($args['validate_failed'])) {
                $this->processCategoriesPost($model, $data);
                $this->processLinkedProductsPost($model, $data);
                $this->processMediaPost($model, $data);
                $this->processInventoryPost($model, $data);
                $this->processSystemLangFieldsPost($model, $data);
                $this->processPricesPost($model, $data);
                // moved to Sellvana_CustomFields
                #$this->processVariantPost($model, $data);
                #$this->processCustomFieldPost($model, $data);
                #$this->processFrontendPost($model, $data);
                $this->BEvents->fire(__METHOD__.':afterValidate', ['model' => $model, 'data' => $data]);
                $model->save();
            }
        }
    }

    /**
     * delete all associate info with this product
     * @param $model
     */
    public function deleteRelatedInfo($model)
    {
        /*
        //delete Categories
        $this->Sellvana_Catalog_Model_CategoryProduct->delete_many([
           'product_id' => $model->id(),
        ]);
        //delete Product Link
        $this->Sellvana_Catalog_Model_ProductLink->delete_many([
            'product_id' => $model->id(),
        ]);
        //delete Product Media
        $this->Sellvana_Catalog_Model_ProductMedia->delete_many([
            'product_id' => $model->id(),
        ]);
        */
        //todo: delete product reviews / wishlist
    }

    /**
     * @param $model Sellvana_Catalog_Model_Product
     */
    public function processCategoriesPost($model, $post)
    {
        $categories = [];
        foreach ($post as $key => $value) {
            $matches = [];
            if (preg_match('#category_id-(\d+)#', $key, $matches)) {
                $categories[intval($matches[1])] = $value;
            }
        }
        if (!empty($categories)) {
            $cat_product = $this->Sellvana_Catalog_Model_CategoryProduct;
            $category_model = $this->Sellvana_Catalog_Model_Category;

            foreach ($categories as $cat_id => $value) {
                $product = $cat_product->orm()->where('product_id', $model->id())->where('category_id', $cat_id)->find_one();
                if (0 == $value && $product) {
                    $product->delete();
                } elseif (false == $product) {
                    $data = ['product_id' => $model->id(), 'category_id' => $cat_id];
                    $this->Sellvana_Catalog_Model_CategoryProduct->create($data)->save();
                }
            }
        }
    }

    /**
     * @param $model Sellvana_Catalog_Model_Product
     * @param $data
     * @return $this
     * @throws BException
     */
    public function processLinkedProductsPost($model, $data)
    {
        //echo "<pre>"; print_r($data); echo "</pre>";die;
        $hlp = $this->Sellvana_Catalog_Model_ProductLink;
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
                //echo "<pre>"; print_r($data[$typeName]); echo "</pre>";die;
                foreach ($data[$typeName] as $key => $arr) {
                    $productLink = $hlp->loadWhere([
                        'product_id' => $model->id(),
                        'linked_product_id' => (int)$key,
                        'link_type' => (string)$type
                    ]);
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

    /**
     * @param $model Sellvana_Catalog_Model_Product
     * @param $data
     * @return $this
     * @throws BException
     */
    public function processMediaPost($model, $data)
    {
        $hlp = $this->Sellvana_Catalog_Model_ProductMedia;
        foreach (['A' => 'attachments', 'I' => 'images'] as $type => $typeName) {

            if (!empty($data['grid'][$typeName]['del'])) {
                $hlp->delete_many([
                    'product_id' => $model->id,
                    'media_type' => $type,
                    'id'   => explode(',', $data['grid'][$typeName]['del']),
                ]);
            }
            $rows = array();
            if (isset($data['grid'][$typeName]['rows']) && $data['grid'][$typeName]['rows'] != '') {
                $rows = $this->BUtil->fromJson($data['grid'][$typeName]['rows']);
            }
            if (!empty($rows)) {
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
                                //'remote_url' =>$this->BApp->href('/media/grid/download?folder=media/product/attachment&file_='.$row['file_id']),
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
            $mediaLibModel = $this->FCom_Core_Model_MediaLibrary->load($productMediaModel->get('file_id'));
            $thumbUrl = ($mediaLibModel->get('subfolder') != null)
                ? $mediaLibModel->get('folder') . '/' . $mediaLibModel->get('subfolder') . '/' . $mediaLibModel->get('file_name')
                : $mediaLibModel->get('folder') . '/' . $mediaLibModel->get('file_name');
            $thumbUrl = preg_replace('#^media/#', '', $thumbUrl); //TODO: resolve the dir string ambiguity
        }
        $model->set('thumb_url', $thumbUrl)->save();
        return $this;
    }

    /**
     * @param Sellvana_Catalog_Model_Product $model
     * @param $data
     */
    public function processInventoryPost($model, $data)
    {
        // update product inventory sku if needed
        if (!empty($data['inventory']['inventory_sku'])) {
            $model->set('inventory_sku', $data['inventory']['inventory_sku']);
        }
        // find inventory model
        $invModel = $model->getInventoryModel();
        // update inventory model
        if ($invModel && !empty($data['inventory'])) {
            // unset key field data
            unset($data['inventory']['id'], $data['inventory']['inventory_sku']);
            // save inventory form data
            $data['inventory']['manage_inventory'] = $model->get('manage_inventory');
            if ($invModel->validate($data['inventory'], [], $this->formId())) {
                $invModel->set($data['inventory'])->save();
            } else {
                throw new BException('Cannot save inventory data, please fix above errors');
            }
        }
    }

    /**
     * @param Sellvana_Catalog_Model_Product $model
     * @param $data
     */
    public function processSystemLangFieldsPost($model, $data)
    {
        $model->setData('name_lang_fields', $data['name_lang_fields']);
        $model->setData('short_desc_lang_fields', $data['short_desc_lang_fields']);
        $model->setData('desc_lang_fields', $data['desc_lang_fields']);
        //$model->save();

    }

    /**
     * process duplicate product
     * @param $id
     */
    public function duplicateProduct($id = '')
    {
        if (empty($id)) {
            $id = $this->BRequest->param('id', true);
        }
        $redirectUrl = $this->BApp->href($this->_formHref) . '?id=' . $id;
        try {
            $oldModel = $this->Sellvana_Catalog_Model_Product->load($id);
            /** @var $oldModel Sellvana_Catalog_Model_Product */
            if ($oldModel) {
                $data = $oldModel->as_array();
                unset($data['id']);
                $newModel = $this->Sellvana_Catalog_Model_Product->create($data);
                /** @var $newModel Sellvana_Catalog_Model_Product */
                $number = $this->getDuplicateSuffixNumber($oldModel->product_name, $oldModel->product_sku, $oldModel->url_key);
                $newModel->product_name = $newModel->product_name . '-' . $number;
                $newModel->url_key = $newModel->url_key . '-' . $number;
                $newModel->product_sku = $newModel->product_sku . '-' . $number;
                $newModel->create_at = $newModel->update_at = date('Y-m-d H:i:s');
                $newModel->is_hidden = 1;
                if ($newModel->save()
                    && $this->duplicateProductPrices($oldModel, $newModel)
                    && $this->duplicateProductCategories($oldModel, $newModel)
                    && $this->duplicateProductLink($oldModel, $newModel)
                    && $this->duplicateProductMedia($oldModel, $newModel)
                    && $this->duplicateProductReviews($oldModel, $newModel)
                ) {
                    $redirectUrl = $this->BApp->href($this->_formHref) . '?id=' . $newModel->id;
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

        $this->BResponse->redirect($redirectUrl);
    }

    /**
     * @param $oldName
     * @param $oldSku
     * @param $oldUrlKey
     * @return int
     */
    public function getDuplicateSuffixNumber($oldName, $oldSku, $oldUrlKey)
    {
        $result = $this->Sellvana_Catalog_Model_Product->orm()
            ->where(['OR' => [
                ['product_name REGEXP ?', (string)$oldName . '-[0-9]$'],
                ['product_sku REGEXP ?', (string)$oldSku . '-[0-9]$'],
                ['url_key REGEXP ?',(string) $oldUrlKey . '-[0-9]$'],
            ]])
            ->order_by_desc('id')->find_one();
        $numberSuffix = 1;
        if ($result) {
            foreach ($result as $arr) {
                $tmpName = explode($oldName . '-', $arr->get('product_name'));
                $tmpSku = explode($oldSku . '-', $arr->get('product_sku'));
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
     * @param $old Sellvana_Catalog_Model_Product
     * @param $new Sellvana_Catalog_Model_Product
     * @return bool
     */
    public function duplicateProductPrices($old, $new)
    {
        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        $prices = $priceHlp->orm()->where('product_id', $old->id())->find_many();
        if ($prices) {
            $newId = $new->id();
            foreach ($prices as $price) {
                $data = $price->as_array();
                unset($data['id']);
                try {
                    $priceHlp->create($data)->set('product_id', $newId)->save();
                } catch (Exception $e) {
                    var_dump($e); exit;
                }
            }
        }
        return true;
    }

    /**
     * @param $old Sellvana_Catalog_Model_Product
     * @param $new Sellvana_Catalog_Model_Product
     * @return bool
     */
    public function duplicateProductCategories($old, $new)
    {
        $categories = $old->categories(true);
        if ($categories) {
            $categoryIds = [];
            //todo: request Boris for same function _.pluck in BUtil
            foreach ($categories as $category) {
                $categoryIds[] = $category->id();
            }
            $new->addToCategories($categoryIds);
        }
        return true;
    }

    /**
     * @param $old Sellvana_Catalog_Model_Product
     * @param $new Sellvana_Catalog_Model_Product
     * @return bool
     */
    public function duplicateProductLink($old, $new)
    {
        //todo: does we need add product link similar between old and new product
        $hlp = $this->Sellvana_Catalog_Model_ProductLink;
        $links = $hlp->orm('pl')->where('product_id', $old->id())->find_many();
        if ($links) {
            foreach ($links as $link) {
                $data = [
                    'product_id'        => $new->id(),
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
     * @param $old Sellvana_Catalog_Model_Product
     * @param $new Sellvana_Catalog_Model_Product
     * @return bool
     */
    public function duplicateProductMedia($old, $new)
    {
        $hlp = $this->Sellvana_Catalog_Model_ProductMedia;
        $medias = $hlp->orm('pa')->where('pa.product_id', $old->id)->select('pa.*')->find_many();
        if ($medias) {
            foreach ($medias as $media) {
                $data = $media->as_array();
                unset($data['id']);
                $data['product_id'] = $new->id();
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
     * @param $old Sellvana_Catalog_Model_Product
     * @param $new Sellvana_Catalog_Model_Product
     * @return bool
     */
    public function duplicateProductReviews($old, $new)
    {
        if ($this->BModuleRegistry->isLoaded('Sellvana_ProductReviews')) {
            //todo: confirm need duplicate product review or not
            $hlp = $this->Sellvana_ProductReviews_Model_Review;
            $reviews = $hlp->orm('pr')->where('product_id', $old->id)->find_many();
            if ($reviews) {
                foreach ($reviews as $r) {
                    $data = $r->as_array();
                    unset($data['id']);
                    $data['product_id'] = $new->id();
                    if (!$hlp->create($data)->save()) {
                        $this->message('An error occurred while duplicate product reviews.', 'error');
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function onHeaderSearch($args)
    {
        $r = $this->BRequest->get();
        if (isset($r['q']) && $r['q'] != '') {
            $value = '%' . $r['q'] . '%';
            $result = $this->Sellvana_Catalog_Model_Product->orm('p')
                ->where(['OR' => [
                    ['p.id like ?', (string)$value],
                    ['p.product_sku like ?', (string)$value],
                    ['p.url_key like ?', (string)$value],
                    ['p.product_name like ?', (string)$value],
                ]])->find_one();
            $args['result']['product'] = null;
            if ($result) {
                $args['result']['product'] = [
                    'priority' => 1,
                    'url' => $this->BApp->href($this->_formHref) . '?id=' . $result->id()
                ];
            }
        }
    }

    public function onGenerateSiteMap($args)
    {
        $callback = function ($row) use ($args) {
            array_push($args['site_map'], ['loc' => $this->BApp->frontendHref($row->get('url_key')), 'changefreq' => 'daily']);
        };
        $this->Sellvana_Catalog_Model_Product->orm()->select('url_key')->iterate($callback);
    }

    protected function processPricesPost($model, $data)
    {
        if(empty($data['prices'])){
            return;
        }

        if (!empty($data['prices']['productPrice'])) {
            foreach ($data['prices']['productPrice'] as $id => $priceData) {
                foreach ($priceData as $field => $pf) {
                    if (in_array($field, ['customer_group_id', 'site_id']) && !is_numeric($pf)) {
                        $priceData[$field] = null;
                    }

                    if($field == 'currency_code' && $pf == '*'){
                        $priceData[$field] = null;
                    }
                }

                $priceData['product_id'] = $model->id();
                if (is_numeric($id)) {
                    $price = $this->Sellvana_Catalog_Model_ProductPrice->load($id);
                } else {
                    $price = $this->Sellvana_Catalog_Model_ProductPrice->create();
                }
                $price->set($priceData)->save();
            }
        }

        if(!empty($data['prices']['delete'])){
            foreach ($data['prices']['delete'] as $delPrice) {
                $price = $this->Sellvana_Catalog_Model_ProductPrice->load($delPrice);
                if($price){
                    $price->delete();
                }
            }

        }

    }
}
