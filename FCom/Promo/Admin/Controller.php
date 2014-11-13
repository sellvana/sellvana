<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Admin_Controller
 *
 * @property FCom_Promo_Model_Promo $FCom_Promo_Model_Promo
 * @property FCom_Promo_Model_Media $FCom_Promo_Model_Media
 * @property FCom_Promo_Model_Product $FCom_Promo_Model_Product
 * @property FCom_Promo_Model_Group $FCom_Promo_Model_Group
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Admin_View_Grid $FCom_Admin_View_Grid
 * @property FCom_Promo_Model_Coupon $FCom_Promo_Model_Coupon
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
            ['name' => 'description', 'label' => 'Description', 'index' => 'description', 'width' => 250],
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
        $orm->select("(select group_concat(a.file_name separator ', ') from " .
            $this->FCom_Promo_Model_Media->table() .
            " pa inner join fcom_media_library a on a.id=pa.file_id where pa.promo_id=p.id)",
            'attachments')
        ;
    }

    /**
     * @param $args
     */
    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->title = $m->id ? 'Edit Promo: ' . $m->description : 'Create New Promo';
    }

    /**
     * @param $view
     * @param null $model
     * @param string $mode
     * @param null $allowed
     * @return $this
     */
    public function processFormTabs($view, $model = null, $mode = 'edit', $allowed = null)
    {
        if ($model && $model->id) {
            $view->addTab("details", ['label' => $this->BLocale->_("Details"), 'pos' => 20, 'async' => true]);
            $view->addTab("history", ['label' => $this->BLocale->_("History"), 'pos' => 40, 'async' => true]);
        }
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
        $this->processGroupsPost($args['model'], $_POST);
        $this->processMediaPost($args['model'], $_POST);
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
        $hlp = $this->FCom_Promo_Model_Media;
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
        $args['orm']->join('FCom_Promo_Model_Media', ['pa.file_id', '=', 'a.id',  ], 'pa')
            ->where_null('pa.promo_id')
            ->select(['pa.promo_status']);
    }

    /**
     * @param $args
     */
    public function onAttachmentsGridUpload($args)
    {
        $hlp = $this->FCom_Promo_Model_Media;
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
        $this->FCom_Promo_Model_Media
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
                'data' => $this->BDb->many_as_array($model->mediaORM('a')->select('a.id')->select('a.file_name')->find_many()),
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
        $this->_processGridDataPost('FCom_Promo_Model_Coupon');
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
                $grid['config']['orm'] = $this->FCom_Promo_Model_Coupon->orm($mainTableAlias)
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

            $oc = $this->FCom_Promo_Model_Coupon->origClass();
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
            'id' => $this->FCom_Promo_Model_Coupon->origClass(),
            'orm' => $this->FCom_Promo_Model_Coupon->orm('pc'),
            'data_url' => $gridDataUrl,
            'edit_url' => $gridDataUrl,
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
            ]
        ];
        $view = $this->view($this->_gridViewName)->set('grid',['config' => $config]);
        return $view;
    }

    public function action_coupons_grid()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        if(!$id){
            $html = $this->_("Promotion id not found");
            $status = 'error';
            $this->BResponse->status(400, $html, false);
        } else {
            $status = "success";
            $html = $this->couponGridView()->render();
        }
        $this->BResponse->json(['status' => $status, 'html'=>$html]);
    }
    public function action_coupons_generate()
    {
        $r = $this->BRequest;

        $id = $r->get('id');
        $data = $r->get('model');
        if (!$id) {
            $message = $this->_("Promotion id not found");
            $status = 'error';
            $this->BResponse->status(400, $message, false);
        } else if(empty($data)){
            $status = "error";
            $message = $this->_("No data received.");
            $this->BResponse->status(400, $message, $message);
        } else {
            $pattern = isset($data['code_pattern'])? $data['code_pattern']: null;
            $length = isset($data['code_length'])? $data['code_length']: 8;
            $usesPerCustomer = isset($data['code_uses_per_customer'])? $data['code_uses_per_customer']: 1;
            $usesTotal = isset($data['code_uses_total'])? $data['code_uses_total']: 1;
            $couponCount = isset($data['coupon_count'])? $data['coupon_count']: 1;
            $model = $this->FCom_Promo_Model_Coupon;
            $generated = $model->generateCoupons([
                 'promo_id' => $id,
                 'pattern' => $pattern,
                 'length' => $length,
                 'uses_per_customer' => $usesPerCustomer,
                 'uses_total' => $usesTotal,
                 'count' => $couponCount
             ]);
            $status = 'success';
            $message = $this->_("%d coupons generated.", $generated['generated']);
            if ($generated < $couponCount) {
                $status = 'warning';
                $message .= $this->_("\nFailed to generate %d coupons", $generated['failed']);
            }
        }
        $this->BResponse->json(['status' => $status, 'message' => $message]);
    }

    public function action_coupons_import()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        if (!$id) {
            $html = $this->_("Promotion id not found");
            $status = 'error';
            $this->BResponse->status(400, $html, false);
        } else {
            $status = "success";
            $html = $this->view('promo/coupons/import')->render();
        }
        $this->BResponse->json(['status' => $status, 'html' => $html]);
    }
}
