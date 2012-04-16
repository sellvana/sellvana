<?php

class FCom_Admin_Controller_Users extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'admin/users';

    public function gridConfig()
    {
        $baseHref = BApp::m('FCom_Admin')->baseHref();
        $linkConf = array('formatter'=>'showlink', 'formatoptions'=>array('baseLinkUrl'=>$baseHref.'/users/form/'));
        $config = array(
            'grid' => array(
                'id'            => 'users',
                'url'           => $baseHref.'/users/grid_data',
                'editurl'       => $baseHref.'/users/grid_data',
                'columns'       => array(
                    'id'          => array('label'=>'ID', 'index'=>'u.id', 'width'=>55),
                    'username'    => array('label'=>'User Name', 'width'=>100) + $linkConf,
                    'email'       => array('label'=>'Email', 'width'=>150) + $linkConf,
                    'firstname'   => array('label'=>'First Name', 'width'=>150),
                    'lastname'    => array('label'=>'First Name', 'width'=>150),
                    'is_superadmin' => array('label'=>'Super?', 'width'=>100,
                        'options'=>FCom_Admin_Model_User::i()->fieldOptions('is_superadmin')),
                    'status'      => array('label'=>'Status', 'width'=>100,
                        'options'=>FCom_Admin_Model_User::i()->fieldOptions('status')),
                    'last_login ' => array('label'=>'Last Login', 'formatter'=>'date', 'width'=>100),
                ),
                'sortname'      => 'u.id',
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
        $this->layout('/users');
    }

    public function action_grid_data()
    {
        $orm = FCom_Admin_Model_User::i()->orm()->table_alias('u')->select('u.*');
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
            $model = FCom_Admin_Model_User::i()->load($id);
            if (empty($model)) {
                BSession::i()->addMessage('Invalid user ID', 'error', 'admin');
                BResponse::i()->redirect(BApp::href('users'));
            }
        } else {
            $model = FCom_Admin_Model_User::i()->create();
        }
        $this->layout('/users/form');
        $view = BLayout::i()->view('users-form');
        $this->processFormTabs($view, $model, $model->id ? 'view' : 'create');
    }

    public function action_form__POST()
    {
        $r = BRequest::i();
        $id = $r->params('id', true);
        $data = $r->post();

        try {
            if ($id) {
                $model = FCom_Admin_Model_User::i()->load($id);
            } else {
                $model = FCom_Admin_Model_User::i()->create();
            }
            BPubSub::i()->fire(__METHOD__, array('id'=>$id, 'data'=>$data, 'model'=>$model));
            $model->set($data['model']);
            unset($data['model']['password_hash']);
            if (!empty($data['model']['password'])) {
                $model->setPassword($data['model']['password']);
            }
            $model->save();
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }

        if ($r->xhr()) {
            $this->forward('form_tab', null, array('id'=>$id));
        } else {
            if (!$id) {
                $id = $model->id;
            }
            BResponse::i()->redirect(BApp::href('users/form/?id='.$id));
        }
    }
}