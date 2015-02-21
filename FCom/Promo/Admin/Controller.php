<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Admin_Controller
 *
 * @property FCom_Promo_Model_Promo       $FCom_Promo_Model_Promo
 * @property FCom_Promo_Model_PromoMedia  $FCom_Promo_Model_PromoMedia
 * @property FCom_Promo_Model_Product     $FCom_Promo_Model_Product
 * @property FCom_Promo_Model_Group       $FCom_Promo_Model_Group
 * @property FCom_Catalog_Model_Category  $FCom_Catalog_Model_Category
 * @property FCom_Catalog_Model_Product   $FCom_Catalog_Model_Product
 * @property FCom_Admin_View_Grid         $FCom_Admin_View_Grid
 * @property FCom_Promo_Model_PromoCoupon $FCom_Promo_Model_PromoCoupon
 * @property FCom_Promo_Model_PromoDisplay $FCom_Promo_Model_PromoDisplay
 *
 */
class FCom_Promo_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'promo';
    protected $_modelClass = 'FCom_Promo_Model_Promo';
    protected $_gridLayoutName = '/promo';
    protected $_gridHref = 'promo';
    protected $_gridTitle = 'Promotions';
    protected $_recordName = 'Promotion';
    protected $_mainTableAlias = 'p';
    protected $_navPath = 'catalog/promo';

    /**
     * @return array
     */
    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'id', 'width' => 55, 'sorttype' => 'number'],
            ['name' => 'summary', 'label' => 'Description', 'index' => 'summary', 'width' => 250],
            ['name' => 'from_date', 'label' => 'Start Date', 'index' => 'from_date', 'formatter' => 'date'],
            ['name' => 'to_date', 'label' => 'End Date', 'index' => 'to_date', 'formatter' => 'date'],
            ['type' => 'input', 'name' => 'status', 'label' => 'Status', 'index' => 'p.status',
                'editable' => true, 'multirow_edit' => true, 'editor' => 'select',
                'options' => $this->FCom_Promo_Model_Promo->fieldOptions('status')
            ],
            ['name' => 'details', 'label' => 'Details', 'index' => 'details', 'hidden' => true],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'delete'],
            ]],
        ];
        $config['actions'] = [
            'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'from_date', 'type' => 'date-range'],
            ['field' => 'to_date', 'type' => 'date-range'],
            ['field' => 'status', 'type' => 'multiselect'],
            ['field' => 'description', 'type' => 'text'],
        ];
        return $config;
    }

    /**
     * @param $orm
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        //load attachments
        $tMediaLibrary = $this->FCom_Core_Model_MediaLibrary->table();
        $tPromoMedia = $this->FCom_Promo_Model_PromoMedia->table();
        $orm->select("(select group_concat(a.file_name separator ', ')
            from {$tPromoMedia} pa inner join {$tMediaLibrary} a on a.id=pa.file_id where pa.promo_id=p.id)",
            'attachments')
        ;
    }

    /**
     * @param $args
     */
    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        /** @var FCom_Promo_Model_Promo $m */
        $m = $args['model'];
        $args['view']->title = $m->id() ? 'Edit Promo: ' . $m->description : 'Create New Promo';
        if (!$m->id()) {
            // todo initiate promo with status 'incomplete'
            $args['view']->numCodes = 0;
        } else {
            $m->set('numCodes', $this->getPromoCouponCodesCount($m->id()));
            if ($m->get('coupon_type') == 1) {
                // load coupon code for view display
                $coupon = $this->FCom_Promo_Model_PromoCoupon->load($m->id(), 'promo_id');
                if ($coupon) {
                    $m->set('single_coupon_code', $coupon->get('code'));
                }
            }
        }
    }
/*

    /**
     * @param $view
     * @param null $model
     * @param string $mode
     * @param null $allowed
     * @return $this
     */
    public function processFormTabs($view, $model = null, $mode = 'edit', $allowed = null)
    {
        return parent::processFormTabs($view, $model, $mode, $allowed);
    }

    /**
     * @param array $args
     */
    public function formPostBefore($args)
    {
        parent::formPostBefore($args);
        if (!empty($args['data']['save_as'])) {
            switch ($args['data']['save_as']) {
                case 'copy': $args['model'] = $args['model']->createClone(); $id = $args['model']->id(); break;
                case 'template': $args['data']['model']['status'] = 'template'; break;
            }
        }
        if (!empty($args['data']['date_range'])) {
            $dates = explode(" - ", $args['data']['date_range']);
            $args['data']['from_date'] = trim($dates[0]);
            if (!empty($dates[1])) {
                $args['data']['to_date'] = trim($dates[1]);
            }
        }

        if (!empty($args['data']['customer_group_ids']) && is_array($args['data']['customer_group_ids'])) {
            $args['data']['customer_group_ids'] = implode(",", $args['data']['customer_group_ids']);
        }

        $serializedData = isset($args['data']['data_serialized'])? $args['data']['data_serialized']: null;
        if ($serializedData) {
            $serializedData = $this->BUtil->fromJson($serializedData);
            $couponCodes = isset($serializedData['coupons'])? $serializedData['coupons']: null;
            if (isset($args['data']['coupon_type']) && $args['data']['coupon_type'] == 2 && $couponCodes) {
                // if coupon type is set and it is 2 == multiple codes, and multiple codes are passed, add them to
                // model for reuse on post after, at this moment, model may not have an id
                $args['model']->set("__multi_codes", $couponCodes);

            }
            unset($serializedData['coupons']);
            $args['data']['data_serialized'] = $this->BUtil->toJson($serializedData);
        }

        if (!empty($args['data']['model'])) {
            $args['data']['model'] = $this->BLocale->parseRequestDates($args['data']['model'], 'from_date,to_date');
            $args['model']->set($args['data']['model']);
        }
    }

    /**
     * @param array $args
     */
    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        $this->processCoupons($args['model']);
        $this->processFrontendDisplay($args['model']);
        #$this->processGroupsPost($args['model'], $_POST);
        #$this->processMediaPost($args['model'], $_POST);
    }

    /**
     * @param $model
     * @param $data
     * @return $this
     */
    public function processGroupsPost($model, $data)
    {
        $groups     = $model->groups();
        $groupData  = [];
        /** @var FCom_Promo_Model_Product[] $groupProds */
        $groupProds = $this->FCom_Promo_Model_Product->orm()->where('promo_id', $model->id())->find_many();
        foreach ($groupProds as $gp) {
            $groupData[$gp->group_id][$gp->product_id] = 1;
        }
        if (!empty($data['_del_group_ids'])) {
            $deleteGroups = explode(',', trim($data['_del_group_ids'], ','));
            $this->FCom_Promo_Model_Group->delete_many([
                  'id'       => $deleteGroups,
                  'promo_id' => $model->id,
                ]
            );
            foreach ($deleteGroups as $gId) {
                unset($groups[$gId], $groupData[$gId]);
            }
        }
        $gIdMap = [];
        if (!empty($data['group'])) {
            foreach ($data['group'] as $gId => $g) {
                if ($gId < 0) {
                    $group  = $this->FCom_Promo_Model_Group->create([
                        'promo_id'   => $model->id,
                        'group_type' => $g['group_type'],
                        'group_name' => $g['group_name'],
                    ])->save();
                    $gIdMap[$gId]       = $group->id;
                    $groups[$group->id] = $group;
                } elseif (!empty($groups[$gId])) {
                    $groups[$gId]->set('group_name', $g['group_name'])->save();
                }

                if (!empty($g['product_ids_add'])) {
                    foreach (explode(',', $g['product_ids_add']) as $pId) {
                        if (!$pId) {
                            continue;
                        }
                        //list($gId, $pId) = explode(':', $gp);
                        if (!empty($groupData[$gId][$pId])) {
                            continue;
                        }
                        $this->FCom_Promo_Model_Product->create([
                            'promo_id'   => $model->id,
                            'group_id'   => $gId,
                            'product_id' => $pId,
                        ])->save();
                        $groupData[$gId][$pId] = 1;
                    }
                }

                if (!empty($g['product_ids_remove'])) {
                    $pIds = [];
                    foreach (explode(',', $g['product_ids_remove']) as $pId) {
                        if (!empty($groupData[$gId][$pId])) {
                            $pIds[] = $pId;
                            unset($groupData[$gId][$pId]);
                        }
                    }
                    if ($pIds) {
                        $this->FCom_Promo_Model_Product->delete_many([
                            'promo_id'   => $model->id,
                            'group_id'   => $gId,
                            'product_id' => $pIds,
                        ]);
                    }
                }

            }
        }

        return $this;
    }

    /**
     * @param $model
     * @param $data
     * @return $this
     */
    public function processMediaPost($model, $data)
    {
        $hlp = $this->FCom_Promo_Model_PromoMedia;
        if (!empty($data['grid']['promo_attachments']['del'])) {
            $hlp->delete_many([
                'promo_id' => $model->id,
                'file_id' => explode(',', $data['grid']['promo_attachments']['del']),
            ]);
        }
        if (!empty($data['grid']['promo_attachments']['add'])) {
            $oldAtt = $hlp->orm()->where('promo_id', $model->id)->find_many_assoc('file_id');
            foreach (explode(',', $data['grid']['promo_attachments']['add']) as $attId) {
                if ($attId && empty($oldAtt[$attId])) {
                    $m = $hlp->create([
                        'promo_id' => $model->id,
                        'file_id' => $attId,
                    ])->save();
                }
            }
        }
        return $this;
    }

    /**
     * @param $model
     * @param $type
     * @param null $groupId
     * @return array
     * @throws BException
     */
    public function productGridConfig($model, $type, $groupId = null)
    {
        static $groups = [], $groupData = [];

        if ($model && $model->id && empty($groups[$model->id])) {
            $groups[$model->id] = $this->FCom_Promo_Model_Promo->load($model->id)->groups();
            $data = $this->FCom_Promo_Model_Product->orm()->table_alias('pp')
                ->join('FCom_Catalog_Model_Product', ['p.id', '=', 'pp.product_id'], 'p')
                ->select('pp.group_id')
                ->select('p.id')->select('p.product_name')->select('p.product_sku')
                ->where('promo_id', $model->id)->find_many();
            foreach ($data as $p) {
                $groupData[$p->group_id][] = $p->as_array();
            }

        }

        $groupName = $model ? htmlspecialchars($groups[$model->id][$groupId]->group_name)
                            : 'Group ' . abs($groupId);
        $gridId    = 'promo_products_' . $type . '_' . $groupId;
        $config    = parent::gridConfig();
        unset($config['orm']);
        $config['id']        = $gridId;
        $config['data']      = !empty($groupData[$groupId]) ? $groupData[$groupId] : [];
        $config['data_mode'] = 'local';
        $config['columns']   = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 40, 'hidden' => true],
            ['name' => 'product_name', 'label' => 'Name', 'index' => 'product_name',
                'width' => 450, 'addable' => true],
            ['name' => 'product_sku', 'label' => 'SKU', 'index' => 'product_sku', 'width' => 70],
        ];
        $actions = [
            'add'    => ['caption' => 'Add products'],
            'delete' => ['caption' => 'Remove products'],
        ];
        $config['actions'] = $actions;
        $config['filters'] = [
            ['field' => 'product_name', 'type' => 'text']
        ];
        $config['grid_before_create'] = $gridId . '_register';

//        $config = array(
//            'grid' => array(
//                'id'            => $gridId,
//                'data'          => !empty($groupData[$groupId]) ? $groupData[$groupId] : array(),
//                'datatype'      => 'local',
//                'caption'       =>
//                    "<input type='text' name='group[$groupId][group_name]' value='$groupName'>"
//                    ."<input type='hidden' name='group[$groupId][group_type]' value='$type'>"
//                    ."<input type='hidden' name='_group_id' value='$groupId'>"
//                    .($type==='buy' && !empty($model) && $model->buy_group!=='one'?" <button type='button' class='sz2 st2 btn' onclick=\"return removeGroup(this);\">Remove</button>":''),
//                'colModel'      => array(
//                    array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>40, 'hidden'=>true),
//                    array('name'=>'product_name', 'label'=>'Name', 'index'=>'product_name', 'width'=>250),
//                    array('name'=>'product_sku', 'label'=>'Mfr Part #', 'index'=>'product_sku', 'width'=>70),
//                ),
//                'rowNum'        => 10,
//                'sortname'      => 'p.product_name',
//                'sortorder'     => 'asc',
//                'autowidth'     => false,
//                'multiselect'   => true,
//                'multiselectWidth' => 30,
//                'shrinkToFit' => true,
//                'forceFit' => true,
//            ),
//            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>false),
//            array('navButtonAdd', 'caption' => '', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Products'),
//            array('navButtonAdd', 'caption' => '', 'buttonicon'=>'ui-icon-trash', 'title' => 'Remove Products'),
//            'js' => array(
//                "if (typeof productLibrary !== 'undefined'){ productLibrary.initTargetGrid('#$gridId'); }",
//            ),
//        );


        return [
            'config'  => $config,
            "group_name" => $groupName,
            'js' => [
                "if (typeof productLibrary !== 'undefined'){ productLibrary.initTargetGrid('#$gridId'); }",
            ]
        ];
    }

    /**
     *
     */
    public function action_form_group()
    {
        $this->BResponse->nocache();
        $r = $this->BRequest;
        $this->view('jqgrid')->set('config', $this->productGridConfig(false, $r->get('type'), $r->get('group_id')));
        $this->BLayout->setRootView('jqgrid');
    }

    /**
     *
     */
    public function action_form_products()
    {
        $orm = $this->FCom_Catalog_Model_Product->orm()->table_alias('p')->select('p.*')
            ->join('FCom_Promo_Model_Product', ['pp.product_id', '=', 'p.id'], 'pp')
            ->select('pp.qty')
            ->join('FCom_Promo_Model_Promo', ['promo.id', '=', 'pp.promo_id'], 'promo')
        ;
        $data = $this->FCom_Admin_View_Grid->processORM($orm, 'FCom_Promo_Admin_Controller::action_form_products');
        $this->BResponse->json($data);
    }

    /**
     * @param array $args
     */
    public function onAttachmentsGridConfig($args)
    {
        array_splice($args['config']['grid']['colModel'], -1, 0, [
            ['name'          => 'promo_status',
                'label'         => 'Status',
                'width'         => 80,
                'options'       => ['' => 'All', 'A' => 'Active', 'I' => 'Inactive'],
                'editable'      => true,
                'edittype'      => 'select',
                'searchoptions' => ['defaultValue' => 'A']
            ],
        ]);
    }

    /**
     * @param $args
     */
    public function onAttachmentsGridGetORM($args)
    {
        $args['orm']->join('FCom_Promo_Model_PromoMedia', ['pa.file_id', '=', 'a.id',  ], 'pa')
            ->where_null('pa.promo_id')
            ->select(['pa.promo_status']);
    }

    /**
     * @param $args
     */
    public function onAttachmentsGridUpload($args)
    {
        $hlp = $this->FCom_Promo_Model_PromoMedia;
        $id = $args['model']->id;
        if (!$hlp->loadWhere(['promo_id' => null, 'file_id' => $id])) {
            $hlp->create(['file_id' => $id])->save();
        }
    }

    /**
     * @param $args
     * @throws BException
     */
    public function onAttachmentsGridEdit($args)
    {
        $r = $this->BRequest;
        $this->FCom_Promo_Model_PromoMedia
            ->loadWhere(['promo_id' => null, 'file_id' => $args['model']->id])
            ->set([
                'promo_status' => $r->post('promo_status'),
            ])
            ->save();
    }

    /**
     * @param $model
     * @return array
     */
    public function attachmentGridConfig($model)
    {
        return [
            'grid' => [
                'id' => 'promo_attachments',
                'caption' => 'Promotion Attachments',
                'datatype' => 'local',
                'data' => $this->BDb->many_as_array($model->mediaORM(FCom_Catalog_Model_ProductMedia::MEDIA_TYPE_ATTCH)->select('a.id')->select('a.file_name')->find_many()),
                'colModel' => [
                    ['name' => 'id', 'label' => 'ID', 'width' => 400, 'hidden' => true],
                    ['name' => 'file_name', 'label' => 'File Name', 'width' => 400],
                ],
                'multiselect' => true,
                'multiselectWidth' => 30,
                'shrinkToFit' => true,
                'forceFit' => true,
            ],
            'navGrid' => ['add' => false, 'edit' => false, 'search' => false, 'del' => false, 'refresh' => false],
            ['navButtonAdd', 'caption' => 'Add', 'buttonicon' => 'ui-icon-plus', 'title' => 'Add Attachments to Promotion', 'cursor' => 'pointer'],
            ['navButtonAdd', 'caption' => 'Remove', 'buttonicon' => 'ui-icon-trash', 'title' => 'Remove Attachments From Promotion', 'cursor' => 'pointer'],
        ];
    }
    public function action_coupons_grid_data__POST()
    {
        $this->_processGridDataPost('FCom_Promo_Model_PromoCoupon');
    }

    public function action_coupons_grid_data()
    {
        if ($this->BRequest->get('export')) {
            if ($this->BRequest->csrf('referrer', 'GET')) {
                $this->BResponse->status('403', 'Invalid referrer', 'Invalid referrer');
                return;
            }
        } else {
            if (!$this->BRequest->xhr()) {
                $this->BResponse->status('403', 'Available only for XHR', 'Available only for XHR');
                return;
            }
        }
        $view = $this->couponGridView();
        $grid = $view->get('grid');
        $mainTableAlias = 'pc';

        if (isset($grid['config']['data']) && (!empty($grid['config']['data']))) {
            $data = $grid['config']['data'];
            $data = $this->gridDataAfter($data);
            $this->BResponse->json([['c' => 1], $data]);
        } else {
            $r = $this->BRequest->get();
            //TODO: clean up and remove
            if (empty($grid['config']['orm'])) {
                $grid['config']['orm'] = $this->FCom_Promo_Model_PromoCoupon->orm($mainTableAlias)
                    ->select($mainTableAlias . '.*');
                $view->set('grid', $grid);
            }
            if (isset($r['filters'])) {
                $filters = $this->BUtil->fromJson($r['filters']);
                if (isset($filters['exclude_id']) && $filters['exclude_id'] != '') {
                    $arr = explode(',', $filters['exclude_id']);
                    $grid['config']['orm']->where_not_in($mainTableAlias . '.id', $arr);
                }
            }

            $oc = $this->FCom_Promo_Model_PromoCoupon->origClass();
            if ($this->BRequest->request('export')) {
                $data = $view->generateOutputData(true);
                $view->export($data['rows'], $oc);
            } else {

                //$data = $view->processORM($orm, $oc.'::action_grid_data', $gridId);
                $data = $view->generateOutputData();
                $data = $this->gridDataAfter($data);
                $this->BResponse->json([
                    ['c' => $data['state']['c']],
                    $this->BDb->many_as_array($data['rows']),
                ]);
            }
        }
    }

    /**
     * @return FCom_Core_View_BackboneGrid
     */
    protected function couponGridView()
    {
        $gridDataUrl = $this->BApp->href($this->_gridHref . '/coupons_grid_data');
        $config = [
            'id' => $this->getCouponGridId(),
            'data_mode' => 'local',
            'data' => $this->BDb->many_as_array($this->FCom_Promo_Model_PromoCoupon->orm('pc')->find_many()),
            /*'data_url' => $gridDataUrl,
            'edit_url' => $gridDataUrl,*/
            'grid_url' => null,
            'form_url' => null,
            'columns' => [
                ['type' => 'row_select', 'width'=>40],
                ['name' => 'id', 'label' => 'ID', 'hidden' => true],
                ['name' => 'code', 'label' => 'Code', 'index' => 'code', 'width' => 400, 'sorttype' => 'string'],
                ['name' => 'total_used', 'label' => 'Used', 'index' => 'total_used', 'sorttype' => 'number', 'width'=>40],
                ['type' => 'btn_group', 'buttons' => [['name' => 'delete']]],
            ],
            'actions' => [
                'delete' => true,
            ],
            'filters' => [
                ['field' => 'code', 'type' => 'text'],
                ['field' => 'total_used', 'type' => 'number-range'],
            ],
            'grid_after_built' => 'couponsGridRegister'
        ];
        $view = $this->view($this->_gridViewName)->set('grid',['config' => $config]);
        return $view;
    }

    public function action_coupons_grid()
    {
        $r = $this->BRequest;
        //$id = $r->get('id');
        //if(!$id){
        //    $html = $this->_("Promotion id not found");
        //    $status = 'error';
        //    $this->BResponse->status(400, $html, false);
        //} else {
            $status = "success";
            $html = $this->couponGridView()->render();
        //}
        $this->BResponse->json(['status' => $status, 'html' => $html]);
    }

    public function action_coupons_generate__POST()
    {
        $r = $this->BRequest;

        //$id = $r->get('id');
        $data = $r->post('model');

        if (empty($data)) {
            $status = "error";
            $message = $this->_("No data received.");
            $this->BResponse->status(400, $message, $message);
        } else {
            $pattern = isset($data['code_pattern'])? $data['code_pattern']: null;
            $length = isset($data['code_length'])? $data['code_length']: 8;
            //$usesPerCustomer = isset($data['code_uses_per_customer'])? $data['code_uses_per_customer']: 1;
            //$usesTotal = isset($data['code_uses_total'])? $data['code_uses_total']: 1;
            $couponCount = isset($data['coupon_count'])? $data['coupon_count']: 1;
            $model = $this->FCom_Promo_Model_PromoCoupon;
            $generated = $model->generateCoupons([
                 //'promo_id' => $id,
                 'pattern' => $pattern,
                 'length' => $length,
                 //'uses_per_customer' => $usesPerCustomer,
                 //'uses_total' => $usesTotal,
                 'count' => $couponCount
             ]);
            $status = 'success';
            $message = $this->_("%d coupon(s) generated.", $generated['generated']);
            if ($generated['generated'] < $couponCount) {
                $status = 'warning';
                $message .= $this->_("\nFailed to generate %d coupons", $generated['failed']);
            }
        }
        $result = ['status' => $status, 'message' => $message];
        if (!empty($generated['codes'])) {
            $result['codes'] = $generated['codes'];
            $result['grid_id'] = $this->getCouponGridId();
        }
        $this->BResponse->json($result);
    }

    public function action_coupons_import__POST()
    {
        //$id = $this->BRequest->get('id');
        if (empty($_FILES) || !isset($_FILES['upload'])) {
            $this->BResponse->json(['msg' => "Nothing found"]);
            return;
        }
        $this->BResponse->setContentType('application/json');
        /** @var FCom_Promo_Model_PromoCoupon $importer */
        $importer = $this->FCom_Promo_Model_PromoCoupon;
        $uploads = $_FILES['upload'];
        $rows = [];
        try {
            foreach ($uploads['name'] as $i => $fileName) {
                if (!$fileName) {
                    continue;
                }
                $fileName = preg_replace('/[^\w\d_.-]+/', '_', $fileName);
                $path = $this->BApp->storageRandomDir() . '/import/coupons';
                $this->BUtil->ensureDir($path);
                $fullFileName = $path . '/' . trim($fileName, '\\/');
                $realPath = str_replace('\\', '/', realpath(dirname($fullFileName)));
                $imported = 0;

                $this->BUtil->ensureDir(dirname($fullFileName));
                $fileSize = 0;
                if (strpos($realPath, $path) !== 0) {
                    $error = $this->_("Weird file path." . $realPath . '|' . $path);
                } else if ($uploads['error'][$i]) {
                    $error = $uploads['error'][$i];
                } elseif (!@move_uploaded_file($uploads['tmp_name'][$i], $fullFileName)) {
                    $error = $this->_("Problem storing uploaded file.");
                } elseif ($importer->validateImportFile($fullFileName)) {
                    $this->BResponse->startLongResponse(false);
                    $imported = $importer->importFromFile($fullFileName);
                    $error = '';
                    $fileSize = $uploads['size'][$i];
                } else {
                    $error = $this->_("Invalid import file.");
                }

                $row = [
                    'name' => $fileName,
                    'size' => $fileSize,
                    'folder' => '.../',
                    'imported' => $imported
                ];
                if ($error) {
                    $row['error'] = $error;
                }
                $rows[] = $row;
            }
        } catch(Exception $e) {
            $this->BDebug->logException($e);
            $this->BResponse->json(['error' => $e->getMessage()]);
        }
        $this->BResponse->json(['files' => $rows]);
    }

    public function action_coupons_import()
    {
        $r  = $this->BRequest;
        //$id = $r->get('id');
        $m  = [
            'config' => ['max_import_file_size' => $this->_getMaxUploadSize(), 'id' => $this->getCouponGridId()],
        ];

        $status = "success";
        $html   = $this->view('promo/coupons/import')->set('model', $m)->render();
        $this->BResponse->json(['status' => $status, 'html' => $html]);
    }

    protected function _getMaxUploadSize()
    {
        $p = ini_get('post_max_size');
        $u = ini_get('upload_max_filesize');
        $max = min($p, $u);
        return $max;
    }

    public function getCouponGridId()
    {
        return $this->FCom_Promo_Model_PromoCoupon->origClass() . '_grid';
    }

    protected function getPromoCouponCodesCount($id)
    {
        $couponOrm = $this->FCom_Promo_Model_PromoCoupon->orm('pc');
        $couponOrm->where('promo_id', $id);
        return $couponOrm->count();
    }

    /**
     * @param FCom_Promo_Model_Promo $model
     * @return $this|bool
     */
    protected function processCoupons($model)
    {
        $this->_processSingleCoupon($model);
        $this->_processMultiCoupons($model);
    }

    /**
     * @param FCom_Promo_Model_Promo $model
     * @throws BException
     */
    protected function processFrontendDisplay($model)
    {
        $data = $this->BRequest->post('display');
        if(!$data) {
            return;
        }
        $displayModel = $this->FCom_Promo_Model_PromoDisplay;
        foreach ($data as $id => $displayData) {
            $serialData = $displayData['data'];
            unset($displayData['data']);
            $displayData['promo_id'] = $model->id();
            /** @var FCom_Promo_Model_PromoDisplay $dModel */
            if(is_numeric($id)) {
                $dModel = $displayModel->load($id);
                if(!$dModel) {
                    throw new BException("Wrong id: " . $id);
                }
            } else {
                $dModel = $displayModel->create();
            }
            $dModel->set($displayData)
                ->setData($serialData)
                ->save();
        }

    }

    /**
     * @param FCom_Promo_Model_Promo $model
     * @return $this|bool
     */
    protected function _processSingleCoupon($model)
    {
        $data = $this->BRequest->post('model');
        if (!$data || !array_key_exists('single_coupon_code', $data)) {
            // if single_coupon_code is not provided, then nothing to do
            return null;
        }

        $code   = $data['single_coupon_code'];
        $coupon = $this->FCom_Promo_Model_PromoCoupon;

        $promoId  = $model->id();
        $params   = [
            'promo_id' => $promoId
        ];
        $existing = $coupon->orm()->where_complex($params)->find_many();
        // see if there is existing coupon code for the promo, if so,
        // and new code is different - or auto generate, delete existing one
        if (empty($code)) {
            $params['count']  = 1;
            $params['length'] = 5;// todo create setting for this?
            $result           = $coupon->generateCoupons($params);
            if ($existing) {
                foreach ($existing as $ex) {
                    $ex->delete();
                }
            }

            return $result['generated'] == 1; // only one coupon should be auto generated
        }
        $same = null;
        foreach ($existing as $ex) {
            if ($ex->get('code') == $code) {
                $same = $ex;
            } else {
                $ex->delete();
            }
        }

        $params['code'] = $code;

        return $same || $coupon->create($params)->save();
    }

    /**
     * @param FCom_Promo_Model_Promo $model
     * @return $this|bool
     */
    protected function _processMultiCoupons($model)
    {
        $couponData = $model->get('__multi_codes');
        if (!$couponData) {
            return null;
        }

        $codes = [];
        foreach ($couponData as $cd) {
            $codes[] = $cd['code'];
        }
        try {
            $created = $this->FCom_Promo_Model_PromoCoupon->createCouponCodes($codes, $model->id());
            $this->message($this->_("Created %d coupon codes.", $created));
            return $created;
        } catch(Exception $e) {
            $this->message($e->getMessage(), 'error');
            return false;
        }
    }
}
