<?php

class FCom_Admin_Controller_Users extends FCom_Admin_Controller_Abstract
{
    public function gridConfig()
    {
        $baseHref = BApp::m('FCom_Admin')->baseHref();
        $linkConf = array('formatter'=>'showlink', 'formatoptions'=>array('baseLinkUrl'=>$baseHref.'/users/form/'));
        $config = array(
            'grid' => array(
                'id'            => 'users',
                'url'           => $baseHref.'/users/grid_data',
                'editurl'       => $baseHref.'/users/grid_data',
                'colModel'      => array(
                    array('name'=>'id', 'label'=>'ID', 'index'=>'u.id', 'width'=>55),
                    array('name'=>'username', 'label'=>'User Name', 'width'=>100) + $linkConf,
                    array('name'=>'email', 'label'=>'Email', 'width'=>150) + $linkConf,
                    array('name'=>'firstname', 'label'=>'First Name', 'width'=>150),
                    array('name'=>'lastname', 'label'=>'First Name', 'width'=>150),
                    array('name'=>'status', 'label'=>'Status', 'width'=>100,
                        'options'=>FCom_Admin_Model_User::i()->fieldOptions('status')),
                    array('name'=>'last_login', 'label'=>'Last Login', 'formatter'=>'date', 'width'=>100),
                ),
                'sortname'      => 'u.id',
                'sortorder'     => 'asc',
                'multiselect'   => true,
            ),
            'navGrid' => array(),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true),
            array('navButtonAdd', 'caption' => 'Columns', 'title' => 'Reorder Columns', 'onClickButton' => 'function() {
                jQuery("#grid-users").jqGrid("columnChooser");
            }'),
        );
        BPubSub::i()->fire('FCom_Admin_Controller_Users::gridConfig', array('config'=>&$config));
        return $config;
    }

    public function action_index()
    {
        $grid = BLayout::i()->view('jqgrid')->set('config', $this->gridConfig());
        BPubSub::i()->fire('FCom_Admin_Controller_Users::action_index', array('grid'=>$grid));
        $this->layout('/users');
    }

    public function action_grid_data()
    {
        $orm = FCom_Admin_Model_User::i()->orm()->table_alias('u')->select('u.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, 'FCom_Admin_Model_User::action_grid_data');
        BResponse::i()->json($data);
    }

    public function action_grid_post()
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
        $id = BRequest::i()->params('id');
        if (!$id) {
            $id = BRequest::i()->get('id');
        }
        if ($id) {
            $model = FCom_Admin_Model_User::i()->load($id);
            if (empty($model)) {
                BSession::i()->addMessage('Invalid user ID', 'error', 'admin');
                BResponse::i()->redirect(BApp::m('FCom_Admin')->baseHref().'/users');
            }
        } else {
            $model = FCom_Admin_Model_User::i()->create();
        }
        $this->layout('/users/form');
        $view = BLayout::i()->view('users/form');
        $this->initFormTabs($view, $model, $model->id ? 'view' : 'create');
    }

    public function action_form_tab()
    {
        $r = BRequest::i();
        $id = $r->params('id');
        if (!$id) {
            $id = $r->request('id');
        }
        $model = FCom_Admin_Model_User::i()->load($id);
        $this->layout('/users/form');
        $view = BLayout::i()->view('users/form');
        $this->outFormTabsJson($view, $model);
    }

    public function action_form_post()
    {
        $r = BRequest::i();
        $id = $r->params('id');
        $data = $r->post();

        try {
            if ($id) {
                $model = FCom_Admin_Model_User::i()->load($id);
            } else {
                $model = FCom_Admin_Model_User::i()->create();
            }
            BPubSub::i()->fire('FCom_Admin_Controller_Users::form_post', array('id'=>$id, 'data'=>$data, 'model'=>$model));
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
            BResponse::i()->redirect(BApp::m('FCom_Admin')->baseHref().'/users/form/'.$id);
        }
    }
}