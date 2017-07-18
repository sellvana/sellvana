<?php

/**
 * Class Sellvana_Promo_Admin_Controller
 *
 * @property Sellvana_Promo_Model_Promo       $Sellvana_Promo_Model_Promo
 * @property Sellvana_Promo_Model_PromoMedia  $Sellvana_Promo_Model_PromoMedia
 * @property Sellvana_Catalog_Model_Category  $Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_Product   $Sellvana_Catalog_Model_Product
 * @property FCom_Admin_View_Grid         $FCom_Admin_View_Grid
 * @property Sellvana_Promo_Model_PromoCoupon $Sellvana_Promo_Model_PromoCoupon
 * @property Sellvana_Promo_Model_PromoDisplay $Sellvana_Promo_Model_PromoDisplay
 *
 */
class Sellvana_Promo_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'promo';
    protected $_modelClass = 'Sellvana_Promo_Model_Promo';
    protected $_gridHref = 'promo';
    protected $_gridTitle = (('Promotions'));
    protected $_recordName = (('Promotion'));
    protected $_formTitleField = 'description';
    protected $_mainTableAlias = 'p';
    protected $_navPath = 'catalog/promo';
    protected $_formLayoutName = '/promo/form';

    /**
     * @return array
     */
    public function gridConfig()
    {
        $config = parent::gridConfig();

        $hlp = $this->Sellvana_Promo_Model_Promo;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'delete'],
            ]],
            ['name' => 'id', 'label' => (('ID')), 'width' => 55, 'sorttype' => 'number'],
            ['name' => 'summary', 'label' => (('Summary')), 'width' => 250],
            ['name' => 'promo_type', 'label' => (('Promo Type')), 'options' => $hlp->fieldOptions('promo_type') ],
            ['name' => 'coupon_type', 'label' => (('Coupon Type')), 'options' => $hlp->fieldOptions('coupon_type') ],
            ['name' => 'internal_notes', 'label' => (('Admin Notes')), 'width' => 250, 'hidden' => 1],
            ['name' => 'customer_label', 'label' => (('Customer Label')), 'width' => 250, 'hidden' => 1],
            ['name' => 'customer_details', 'label' => (('Customer Details')), 'width' => 250, 'hidden' => 1],
            ['name' => 'from_date', 'label' => (('Start Date')), 'formatter' => 'date', 'cell' => 'date'],
            ['name' => 'to_date', 'label' => (('End Date')), 'formatter' => 'date', 'cell' => 'date'],
            ['type' => 'input', 'name' => 'status', 'label' => (('Status')), 'index' => 'p.status',
                'editable' => true, 'multirow_edit' => true, 'editor' => 'select',
                'options' => $hlp->fieldOptions('status')
            ],
            ['name' => 'details', 'label' => (('Details')), 'hidden' => true],
            ['name' => 'create_at', 'label' => (('Created')), 'formatter' => 'date', 'cell' => 'datetime'],
            ['name' => 'update_at', 'label' => (('Updated')), 'formatter' => 'date', 'cell' => 'datetime'],
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
        $tPromoMedia = $this->Sellvana_Promo_Model_PromoMedia->table();
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
        /** @var Sellvana_Promo_Model_Promo $m */
        $m = $args['model'];
        if (!$m->id()) {
            // todo initiate promo with status 'incomplete'
            $args['view']->numCodes = 0;
        } else {
            $m->set('numCodes', $this->_getPromoCouponCodesCount($m->id()));
            if ($m->get('coupon_type') == 1) {
                // load coupon code for view display
                $coupon = $this->Sellvana_Promo_Model_PromoCoupon->load($m->id(), 'promo_id');
                if ($coupon) {
                    $m->set('single_coupon_code', $coupon->get('code'));
                }
            }
        }
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

        if (isset($args['data']['customer_group_ids']) && is_array($args['data']['customer_group_ids'])) {
            $args['data']['customer_group_ids'] = implode(",", $args['data']['customer_group_ids']);
        }

        $serializedData = isset($args['data']['data_serialized'])? $args['data']['data_serialized']: [];
        if (!empty($serializedData) && is_string($serializedData)) {
            $serializedData = $this->BUtil->fromJson($serializedData);
            $couponCodes = isset($serializedData['coupons'])? $serializedData['coupons']: null;
            if (isset($args['data']['coupon_type']) && $args['data']['coupon_type'] == 2 && $couponCodes) {
                // if coupon type is set and it is 2 == multiple codes, and multiple codes are passed, add them to
                // model for reuse on post after, at this moment, model may not have an id
                $args['model']->set("__multi_codes", $couponCodes);

            }
            $couponCodesRemoved = isset($serializedData['coupons_removed'])? $serializedData['coupons_removed']: null;
            if (isset($args['data']['coupon_type']) && $args['data']['coupon_type'] == 2 && $couponCodesRemoved) {
                $args['model']->set("__multi_codes_removed", $couponCodesRemoved);

            }
            unset($serializedData['coupons']);
            unset($serializedData['coupons_removed']);
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
        $this->_processCoupons($args['model']);
        $this->_processFrontendDisplay($args['model']);
        #$this->processGroupsPost($args['model'], $_POST);
        #$this->processMediaPost($args['model'], $_POST);
    }

    public function action_coupons_grid_data__POST()
    {
        $this->_processGridDataPost('Sellvana_Promo_Model_PromoCoupon');
    }

    public function action_coupons_grid_data()
    {
        if ($this->BRequest->get('export')) {
            if ($this->BRequest->csrf('referrer', 'GET')) {
                $this->BResponse->status('403', (('Invalid referrer')), 'Invalid referrer');
                return;
            }
        } else {
            if (!$this->BRequest->xhr()) {
                $this->BResponse->status('403', (('Available only for XHR')), 'Available only for XHR');
                return;
            }
        }
        $view = $this->_couponGridView();
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
                $grid['config']['orm'] = $this->Sellvana_Promo_Model_PromoCoupon->orm($mainTableAlias)
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

            $oc = $this->Sellvana_Promo_Model_PromoCoupon->origClass();
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
    protected function _couponGridView($promoId = null)
    {
        $gridDataUrl = $this->BApp->href($this->_gridHref . '/coupons_grid_data');
        $orm         = $this->Sellvana_Promo_Model_PromoCoupon->orm('pc');
        if(null !== $promoId){
            $orm->where('promo_id', $promoId);
        }
        $config      = [
            'id' => $this->getCouponGridId(),
            'data_mode' => 'local',
            'data' => $this->BDb->many_as_array($orm->find_many()),
            /*'data_url' => $gridDataUrl,
            'edit_url' => $gridDataUrl,*/
            'grid_url' => null,
            'form_url' => null,
            'columns' => [
                ['type' => 'row_select', 'width'=>40],
                ['name' => 'id', 'label' => (('ID')), 'hidden' => true],
                ['type' => 'btn_group', 'buttons' => [['name' => 'delete']]],
                ['name' => 'code', 'label' => (('Code')), 'index' => 'code', 'width' => 400, 'sorttype' => 'string'],
                ['name' => 'total_used', 'label' => (('Used')), 'index' => 'total_used', 'sorttype' => 'number', 'width'=>40]
            ],
            'actions' => [
                'delete' => true,
            ],
            'filters' => [
                ['field' => 'code', 'type' => 'text'],
                ['field' => 'total_used', 'type' => 'number-range'],
            ],
            // 'grid_after_built' => 'couponsGridRegister',
            'callbacks' => [
                'componentDidMount' => 'couponsGridRegister'
            ]
        ];
        $view = $this->view($this->_gridViewName)->set('grid',['config' => $config]);
        return $view;
    }

    public function action_coupons_grid()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        //if(!$id){
        //    $html = $this->_(("Promotion id not found"));
        //    $status = 'error';
        //    $this->BResponse->status(400, $html, false);
        //} else {
            $status = "success";
            $html = $this->_couponGridView($id)->render();
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
            $message = $this->_(("No data received."));
            $this->BResponse->status(400, $message, $message);
        } else {
            $pattern = isset($data['code_pattern'])? $data['code_pattern']: null;
            $length = isset($data['code_length'])? $data['code_length']: 8;
            //$usesPerCustomer = isset($data['code_uses_per_customer'])? $data['code_uses_per_customer']: 1;
            //$usesTotal = isset($data['code_uses_total'])? $data['code_uses_total']: 1;
            $couponCount = isset($data['coupon_count'])? $data['coupon_count']: 1;
            $model = $this->Sellvana_Promo_Model_PromoCoupon;
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
                $message .= $this->_((("\nFailed to generate %d coupons")), $generated['failed']);
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
            $this->BResponse->json(['msg' => (("Nothing found"))]);
            return;
        }
        $this->BResponse->setContentType('application/json');
        /** @var Sellvana_Promo_Model_PromoCoupon $importer */
        $importer = $this->Sellvana_Promo_Model_PromoCoupon;
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
                } elseif ($uploads['error'][$i]) {
                    $error = $uploads['error'][$i];
                } elseif (!$this->BUtil->moveUploadedFileSafely($uploads['tmp_name'][$i], $fullFileName)) {
                    $error = $this->_(("Problem storing uploaded file."));
                } elseif ($importer->validateImportFile($fullFileName)) {
                    $this->BResponse->startLongResponse(false);
                    $imported = $importer->importFromFile($fullFileName);
                    $error = '';
                    $fileSize = $uploads['size'][$i];
                } else {
                    $error = $this->_(("Invalid import file."));
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
        return $this->Sellvana_Promo_Model_PromoCoupon->origClass() . '_grid';
    }

    protected function _getPromoCouponCodesCount($id)
    {
        $couponOrm = $this->Sellvana_Promo_Model_PromoCoupon->orm('pc');
        $couponOrm->where('promo_id', $id);
        return $couponOrm->count();
    }

    /**
     * @param Sellvana_Promo_Model_Promo $model
     * @return $this|bool
     */
    protected function _processCoupons($model)
    {
        $this->_processSingleCoupon($model);
        $this->_processMultiCoupons($model);
    }

    /**
     * @param Sellvana_Promo_Model_Promo $model
     * @throws BException
     */
    protected function _processFrontendDisplay($model)
    {
        $data = $this->BRequest->post('display');
        if(!$data) {
            return;
        }
        $displayModel = $this->Sellvana_Promo_Model_PromoDisplay;
        foreach ($data as $id => $displayData) {
            $serialData = $displayData['data'];
            unset($displayData['data']);
            $displayData['promo_id'] = $model->id();
            /** @var Sellvana_Promo_Model_PromoDisplay $dModel */
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
     * @param Sellvana_Promo_Model_Promo $model
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
        $coupon = $this->Sellvana_Promo_Model_PromoCoupon;

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
     * @param Sellvana_Promo_Model_Promo $model
     * @return $this|bool
     */
    protected function _processMultiCoupons($model)
    {
        $couponRemovedIds = $model->get('__multi_codes_removed');
        if ($couponRemovedIds) {
            $this->Sellvana_Promo_Model_PromoCoupon->delete_many(['id' => $couponRemovedIds]);
        }

        $couponData = $model->get('__multi_codes');
        if (!$couponData) {
            return null;
        }

        $codes = [];
        foreach ($couponData as $cd) {
            $codes[] = $cd['code'];
        }

        try {
            $created = $this->Sellvana_Promo_Model_PromoCoupon->createCouponCodes($codes, $model->id());
            $this->message($this->_((("Created %d coupon codes.")), $created));
            return $created;
        } catch(Exception $e) {
            $this->message($e->getMessage(), 'error');
            return false;
        }
    }

    /***************** NOT USED RIGHT NOW ******************/


    /**
     * @param $model
     * @param $data
     * @return $this
     */
    public function processMediaPost($model, $data)
    {
        $hlp = $this->Sellvana_Promo_Model_PromoMedia;
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
     * @param array $args
     */
    public function onAttachmentsGridConfig($args)
    {
        array_splice($args['config']['grid']['colModel'], -1, 0, [
            ['name'          => 'promo_status',
                'label'         => (('Status')),
                'width'         => 80,
                'options'       => ['' => (('All')), 'A' => (('Active')), 'I' => (('Inactive'))],
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
        $args['orm']->join('Sellvana_Promo_Model_PromoMedia', ['pa.file_id', '=', 'a.id',  ], 'pa')
            ->where_null('pa.promo_id')
            ->select(['pa.promo_status']);
    }

    /**
     * @param $args
     */
    public function onAttachmentsGridUpload($args)
    {
        $hlp = $this->Sellvana_Promo_Model_PromoMedia;
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
        $this->Sellvana_Promo_Model_PromoMedia
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
                'caption' => (('Promotion Attachments')),
                'datatype' => 'local',
                'data' => $this->BDb->many_as_array($model->mediaORM(Sellvana_Catalog_Model_ProductMedia::MEDIA_TYPE_ATTACH)->select('a.id')->select('a.file_name')->find_many()),
                'colModel' => [
                    ['name' => 'id', 'label' => (('ID')), 'width' => 400, 'hidden' => true],
                    ['name' => 'file_name', 'label' => (('File Name')), 'width' => 400],
                ],
                'multiselect' => true,
                'multiselectWidth' => 30,
                'shrinkToFit' => true,
                'forceFit' => true,
            ],
            'navGrid' => ['add' => false, 'edit' => false, 'search' => false, 'del' => false, 'refresh' => false],
            ['navButtonAdd', 'caption' => (('Add')), 'buttonicon' => 'ui-icon-plus', 'title' => (('Add Attachments to Promotion')), 'cursor' => 'pointer'],
            ['navButtonAdd', 'caption' => (('Remove')), 'buttonicon' => 'ui-icon-trash', 'title' => (('Remove Attachments From Promotion')), 'cursor' => 'pointer'],
        ];
    }
}
