<?php
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

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = array(
            array('type' => 'row_select'),
            array('name' => 'id', 'label' => 'ID', 'index' => 'id', 'width' => 55, 'sorttype' => 'number'),
            array('name' => 'description', 'label' => 'Description', 'index' => 'description', 'width' => 250),
            array('name' => 'from_date', 'label' => 'Start Date', 'index' => 'from_date', 'formatter' => 'date'),
            array('name' => 'to_date', 'label' => 'End Date', 'index' => 'to_date', 'formatter' => 'date'),
            array('type' => 'input', 'name' => 'status', 'label' => 'Status', 'index' => 'p.status',
                  'editable' => true, 'mass-editable' => true, 'options' => FCom_Promo_Model_Promo::i()->fieldOptions('status'), 'editor' => 'select'
            ),
            array('name' => 'details', 'label' => 'Details', 'index' => 'details', 'hidden' => true),
            array('name' => 'attachments', 'label' => 'Attachments', 'sortable' => false, 'hidden' => false),
            array(
                'type' =>'btn_group', 'name'=>'_actions','label'=> 'Actions', 'sortable' => false,
                'buttons'=> array(
                                   array('name'=>'edit', 'href' => BApp::href($this->_formHref.'?id='), 'col' => 'id'), 
                                   array('name'=>'delete')
                                )
                )            
        );
        $config['actions'] = array(
            'edit' => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'from_date', 'type' => 'date-range'),
            array('field' => 'to_date', 'type' => 'date-range'),
            array('field' => 'status', 'type' => 'multiselect'),
            array('field' => 'description', 'type' => 'text'),
        );
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        //load attachments
        $orm->select("(select group_concat(a.file_name separator ', ') from ".
                FCom_Promo_Model_Media::table().
                " pa inner join fcom_media_library a on a.id=pa.file_id where pa.promo_id=p.id)",
                'attachments')
        ;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        /*
        $actions = array('<input type="hidden" id="save_as" name="save_as" value=""/>');
        if ($m->status==='template') {
            $actions['save_as_new'] = '<button type="button" class="st1 sz2 btn btn-primary" onclick="if (adminForm.saveAll(this)) { $(\'#save_as\').val(\'copy\'); this.form.submit(); }"><span>Save as a New Promotion</span></button>';
        } else {
            $actions['save_as_tpl'] = '<button type="button" class="st1 sz2 btn btn-primary" onclick="if (adminForm.saveAll(this)) { $(\'#save_as\').val(\'template\'); this.form.submit(); }"><span>Save as a Template</span></button>';
        }
        $args['view']->actions = BUtil::arrayMerge($args['view']->actions, $actions);
        */
        $args['view']->title = $m->id ? 'Edit Promo: '.$m->description: 'Create New Promo';
    }

    public function processFormTabs($view, $model=null, $mode='edit', $allowed=null)
    {
        if ($model && $model->id) {
            $view->addTab("details", array('label' => BLocale::_("Details"), 'pos' => 20, 'async' => true));
            $view->addTab("history", array('label' => BLocale::_("History"), 'pos' => 40, 'async' => true));
        }
        return parent::processFormTabs($view, $model, $mode, $allowed);
    }

    public function formPostBefore($args)
    {
        parent::formPostBefore($args);
        if (!empty($args['data']['save_as'])) {
            switch ($args['data']['save_as']) {
                case 'copy': $args['model'] = $args['model']->createClone(); $id = $args['model']->id; break;
                case 'template': $args['data']['model']['status'] = 'template'; break;
            }
        }
        if (!empty($args['data']['model'])) {
            $args['data']['model'] = BLocale::i()->parseRequestDates($args['data']['model'], 'from_date,to_date');
            $args['model']->set($args['data']['model']);
        }
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        $this->processGroupsPost($args['model'], $_POST);
        $this->processMediaPost($args['model'], $_POST);
    }

    public function processGroupsPost($model, $data)
    {
        $groups     = $model->groups();
        $groupData  = array();
        $groupProds = FCom_Promo_Model_Product::i()->orm()->where( 'promo_id', $model->id )->find_many();
        foreach ( $groupProds as $gp ) {
            $groupData[ $gp->group_id ][ $gp->product_id ] = 1;
        }
        if ( !empty( $data[ '_del_group_ids' ] ) ) {
            $deleteGroups = explode( ',', trim( $data[ '_del_group_ids' ], ',' ) );
            FCom_Promo_Model_Group::i()->delete_many(array(
                  'id'       => $deleteGroups,
                  'promo_id' => $model->id,
                )
            );
            foreach ( $deleteGroups as $gId ) {
                unset( $groups[ $gId ], $groupData[ $gId ] );
            }
        }
        $gIdMap = array();
        if ( !empty( $data[ 'group' ] ) ) {
            foreach ( $data[ 'group' ] as $gId => $g ) {
                if ( $gId < 0 ) {
                    $group  = FCom_Promo_Model_Group::i()->create(array(
                              'promo_id'   => $model->id,
                              'group_type' => $g[ 'group_type' ],
                              'group_name' => $g[ 'group_name' ],
                        ))->save();
                    $gIdMap[ $gId ]       = $group->id;
                    $groups[ $group->id ] = $group;
                } elseif ( !empty( $groups[ $gId ] ) ) {
                    $groups[ $gId ]->set( 'group_name', $g[ 'group_name' ] )->save();
                }

                if ( !empty( $g[ 'product_ids_add' ] ) ) {
                    foreach ( explode( ',', $g[ 'product_ids_add' ] ) as $pId ) {
                        if ( !$pId ) {
                            continue;
                        }
                        //list($gId, $pId) = explode(':', $gp);
                        if ( !empty( $groupData[ $gId ][ $pId ] ) ) {
                            continue;
                        }
                        FCom_Promo_Model_Product::i()->create(array(
                                'promo_id'   => $model->id,
                                'group_id'   => $gId,
                                'product_id' => $pId,
                            ))->save();
                        $groupData[ $gId ][ $pId ] = 1;
                    }
                }

                if ( !empty( $g[ 'product_ids_remove' ] ) ) {
                    $pIds = array();
                    foreach ( explode( ',', $g[ 'product_ids_remove' ] ) as $pId ) {
                        if ( !empty( $groupData[ $gId ][ $pId ] ) ) {
                            $pIds[ ] = $pId;
                            unset( $groupData[ $gId ][ $pId ] );
                        }
                    }
                    if ( $pIds ) {
                        FCom_Promo_Model_Product::i()->delete_many(array(
                                'promo_id'   => $model->id,
                                'group_id'   => $gId,
                                'product_id' => $pIds,
                            )
                        );
                    }
                }

            }
        }

        return $this;
    }

    public function processMediaPost($model, $data)
    {
        $hlp = FCom_Promo_Model_Media::i();
        if (!empty($data['grid']['promo_attachments']['del'])) {
            $hlp->delete_many(array(
                'promo_id' => $model->id,
                'file_id'=>explode(',', $data['grid']['promo_attachments']['del']),
            ));
        }
        if (!empty($data['grid']['promo_attachments']['add'])) {
            $oldAtt = $hlp->orm()->where('promo_id', $model->id)->find_many_assoc('file_id');
            foreach (explode(',', $data['grid']['promo_attachments']['add']) as $attId) {
                if ($attId && empty($oldAtt[$attId])) {
                    $m = $hlp->create(array(
                        'promo_id' => $model->id,
                        'file_id' => $attId,
                    ))->save();
                }
            }
        }
        return $this;
    }

    public function productGridConfig($model, $type, $groupId=null)
    {
        static $groups = array(), $groupData = array();

        if ($model && $model->id && empty($groups[$model->id])) {
            $groups[$model->id] = FCom_Promo_Model_Promo::i()->load($model->id)->groups();
            $data = FCom_Promo_Model_Product::i()->orm()->table_alias('pp')
                ->join('FCom_Catalog_Model_Product', array('p.id','=','pp.product_id'), 'p')
                ->select('pp.group_id')
                ->select('p.id')->select('p.product_name')->select('p.local_sku')
                ->where('promo_id', $model->id)->find_many();
            foreach ($data as $p) {
                $groupData[$p->group_id][] = $p->as_array();
            }

        }

        $groupName = $model ? htmlspecialchars( $groups[ $model->id ][ $groupId ]->group_name )
                            : 'Group ' . abs( $groupId );
        $gridId    = 'promo_products_' . $type . '_' . $groupId;
        $config    = parent::gridConfig();
        unset( $config[ 'orm' ] );
        $config[ 'id' ]        = $gridId;
        $config[ 'data' ]      = !empty( $groupData[ $groupId ] ) ? $groupData[ $groupId ] : array();
        $config[ 'data_mode' ] = 'local';
        $config[ 'columns' ]   = array(
            array( 'type'=>'row_select'),
            array( 'name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 40, 'hidden' => true ),
            array( 'name' => 'product_name', 'label'   => 'Name', 'index'   => 'product_name',
                   'width'=> 450, 'addable' => true ),
            array( 'name' => 'local_sku', 'label' => 'SKU', 'index' => 'local_sku', 'width' => 70 ),
        );
        $actions = array(
            'add'    => array( 'caption' => 'Add products' ),
            'delete' => array( 'caption' => 'Remove products' ),
        );
        $config[ 'actions' ] = $actions;
        $config[ 'filters' ] = array(
            array( 'field' => 'product_name', 'type' => 'text' )
        );
        $config[ 'events' ] = array( 'init', 'add', 'mass-delete' );

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
//                    array('name'=>'local_sku', 'label'=>'Mfr Part #', 'index'=>'local_sku', 'width'=>70),
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


        return array(
            'config'  => $config,
            "group_name" => $groupName,
            'js' => array(
                "if (typeof productLibrary !== 'undefined'){ productLibrary.initTargetGrid('#$gridId'); }",
            )
        );
    }

    public function action_form_group()
    {
        BResponse::i()->nocache();
        $r = BRequest::i();
        $this->view('jqgrid')->set('config', $this->productGridConfig(false, $r->get('type'), $r->get('group_id')));
        BLayout::i()->rootView('jqgrid');
    }

    public function action_form_products()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')->select('p.*')
            ->join('FCom_Promo_Model_Product', array('pp.product_id','=','p.id'), 'pp')
            ->select('pp.qty')
            ->join('FCom_Promo_Model_Promo', array('promo.id','=','pp.promo_id'), 'promo')
        ;
        $data = FCom_Admin_View_Grid::i()->processORM($orm, 'FCom_Promo_Admin_Controller::action_form_products');
        BResponse::i()->json($data);
    }

    public function onAttachmentsGridConfig($args)
    {
        array_splice($args['config']['grid']['colModel'], -1, 0, array(
                array( 'name'          => 'promo_status',
                       'label'         => 'Status',
                       'width'         => 80,
                       'options'       => array( '' => 'All', 'A' => 'Active', 'I' => 'Inactive' ),
                       'editable'      => true,
                       'edittype'      => 'select',
                       'searchoptions' => array( 'defaultValue' => 'A' )
                ),
            )
        );
    }

    public function onAttachmentsGridGetORM($args)
    {
        $args['orm']->join('FCom_Promo_Model_Media', array('pa.file_id','=','a.id',), 'pa')
            ->where_null('pa.promo_id')
            ->select(array('pa.promo_status'));
    }

    public function onAttachmentsGridUpload($args)
    {
        $hlp = FCom_Promo_Model_Media::i();
        $id = $args['model']->id;
        if (!$hlp->load(array('promo_id'=>null, 'file_id'=>$id))) {
            $hlp->create(array('file_id' => $id))->save();
        }
    }

    public function onAttachmentsGridEdit($args)
    {
        $r = BRequest::i();
        FCom_Promo_Model_Media::i()
            ->load(array('promo_id'=>null, 'file_id'=>$args['model']->id))
            ->set(array(
                'promo_status' => $r->post('promo_status'),
            ))
            ->save();
    }

    public function attachmentGridConfig($model)
    {
        return array(
            'grid' => array(
                'id' => 'promo_attachments',
                'caption' => 'Promotion Attachments',
                'datatype' => 'local',
                'data' => BDb::many_as_array($model->mediaORM('a')->select('a.id')->select('a.file_name')->find_many()),
                'colModel' => array(
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>400),
                ),
                'multiselect' => true,
                'multiselectWidth' => 30,
                'shrinkToFit' => true,
                'forceFit' => true,
            ),
            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>false),
            array('navButtonAdd', 'caption' => 'Add', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Attachments to Promotion', 'cursor'=>'pointer'),
            array('navButtonAdd', 'caption' => 'Remove', 'buttonicon'=>'ui-icon-trash', 'title' => 'Remove Attachments From Promotion', 'cursor'=>'pointer'),
        );
    }
}
