<?php

class FCom_Admin_Controller_Roles extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'admin/roles';

    public function gridConfig()
    {
        $baseHref = BApp::m('FCom_Admin')->baseHref();
        $linkConf = array('formatter'=>'showlink', 'formatoptions'=>array('baseLinkUrl'=>$baseHref.'/roles/form/'));
        $config = array(
            'grid' => array(
                'id'            => 'roles',
                'url'           => $baseHref.'/roles/grid_data',
                'editurl'       => $baseHref.'/roles/grid_data',
                'columns'       => array(
                    'id'          => array('label'=>'ID', 'index'=>'u.id', 'width'=>55),
                    'role_name'   => array('label'=>'Role Name', 'width'=>100) + $linkConf,
                ),
                'sortname'      => 'r.id',
                'sortorder'     => 'asc',
                'multiselect'   => true,
            ),
            'navGrid' => array(),
            'custom' => array('personalize'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true),
        );
        BPubSub::i()->fire(__METHOD__, array('config'=>&$config));
        return $config;
    }

    public function action_index()
    {
        $grid = BLayout::i()->view('jqgrid')->set('config', $this->gridConfig());
        BPubSub::i()->fire(__METHOD__, array('grid'=>$grid));
        $this->layout('/roles');
    }

    public function action_grid_data()
    {
        $orm = FCom_Admin_Model_Role::i()->orm()->table_alias('r')->select('r.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, __METHOD__);
        BResponse::i()->json($data);
    }

    public function action_grid_data__POST()
    {
        switch (BRequest::i()->post('oper')) {
        case 'del':
            $ids = explode(',', BRequest::i()->post('id'));
            FCom_Admin_Model_User::i()->delete_many(array('id'=>$ids));
            break;
        }
    }

    public function action_form()
    {
        $id = BRequest::i()->params('id', true);
        if ($id) {
            $model = FCom_Admin_Model_Role::i()->load($id);
            if (empty($model)) {
                BSession::i()->addMessage('Invalid role ID', 'error', 'admin');
                BResponse::i()->redirect(BApp::href('roles'));
            }
        } else {
            $model = FCom_Admin_Model_Role::i()->create();
        }
        $this->layout('/roles/form');
        $view = BLayout::i()->view('roles-form');
        $this->processFormTabs($view, $model, $model->id ? 'view' : 'create');
    }

    public function action_form__POST()
    {
        $r = BRequest::i();
        $id = $r->params('id', true);
        $data = $r->post();
        try {
            if ($id) {
                $model = FCom_Admin_Model_Role::i()->load($id);
            } else {
                $model = FCom_Admin_Model_Role::i()->create();
            }
            BPubSub::i()->fire(__METHOD__, array('id'=>$id, 'data'=>$data, 'model'=>$model));
            if (empty($data['model']['permissions'])) {
                $data['model']['permissions'] = array();
            }
            $model->set($data['model']);
            $model->save();

        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }

        if ($r->xhr()) {
            $this->forward('form', null, array('id'=>$id));
        } else {
            if (!$id) {
                $id = $model->id;
            }
            BResponse::i()->redirect(BApp::href('roles/form/?id='.$id));
        }
    }
}