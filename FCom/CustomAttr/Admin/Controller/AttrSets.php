<?php

class FCom_CustomAttr_Admin_Controller_AttrSets extends FCom_Catalog_Admin_Controller_Products
{
    public function action_index()
    {
        $grid = BLayout::i()->view('jqgrid');
        $linkConf = array('formatter'=>'showlink', 'formatoptions'=>array(
            'baseLinkUrl' => BApp::url('FCom_CustomAttr', '/attrsets/form/'),
        ));
        $grid->config = array(
            'grid' => array(
                'id'            => 'attrsets',
                'url'           => 'attrsets/grid_data',
                'colModel'      => array(
                    array('name'=>'id', 'label'=>'ID', 'width'=>55, 'sorttype'=>'number'),
                    array('name'=>'set_code', 'label'=>'Code', 'width'=>100) + $linkConf,
                    array('name'=>'set_name', 'label'=>'Name', 'width'=>250) + $linkConf,
                ),
            ),
            'navGrid' => array(),
            #'searchGrid' => array('multipleSearch'=>true, 'multipleGroup'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
            array('navButtonAdd', 'caption' => 'Columns', 'title' => 'Reorder Columns', 'onClickButton' => 'function() {
                jQuery("#grid-attrsets").jqGrid("columnChooser");
            }'),
        );
        BPubSub::i()->fire('FCom_CustomAttr_Admin_Controller_AttrSets::action_index', array('grid'=>$grid));
        $this->layout('/customattr/attrsets');
    }

    public function action_grid_data()
    {
        $orm = FCom_CustomAttr_Model_Set::i()->orm()->table_alias('s')->select('s.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, 'FCom_CustomAttr_Admin_Controller_AttrSets::action_grid_data');
        BResponse::i()->json($data);
    }

    public function action_form()
    {
        $id = BRequest::i()->params('id');
        if (!$id) {
            $id = BRequest::i()->get('id');
        }
        if ($id) {
            $model = FCom_CustomAttr_Model_Set::i()->load($id);
            if (empty($model)) {
                BSession::i()->addMessage('Invalid attribute set ID', 'error', 'admin');
                BResponse::i()->redirect(BApp::url('FCom_CustomAttr', '/attrsets'));
            }
        } else {
            $model = FCom_CustomAttr_Model_Set::i()->create();
        }
        $this->layout('/customattr/attrsets/form');
        $view = BLayout::i()->view('customattr/attrsets/form');
        $this->initFormTabs($view, $model, $model->id ? 'view' : 'create', $promo->id ? null : 'main');
    }

    public function action_form_tab()
    {
        $r = BRequest::i();
        $id = $r->params('id');
        if (!$id) {
            $id = $r->request('id');
        }
        $this->layout('denteva_promo_form_tabs');
        $view = BLayout::i()->view('denteva/promo/form');
        $promo = Denteva_Model_Promo::i()->load($id);
        $this->outFormTabsJson($view, $promo);
    }

    public function action_form_post()
    {
        $r = BRequest::i();
        $id = $r->params('id');
        $data = $r->post();

        try {
            if ($id) {
                $model = Denteva_Model_Promo::i()->load($id);
            } else {
                $model = Denteva_Model_Promo::i()->create();
            }
            $data['model'] = BLocale::i()->parseRequestDates($data['model'], 'from_date,to_date');
            $model->set($data['model']);
            BPubSub::i()->fire('FCom_CustomAttr_Admin_Controller_AttrSets::form_post', array('id'=>$id, 'data'=>$data, 'model'=>$model));
            $model->save();
            if (!$id) {
                $id = $model->id;
            }
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }

        if ($r->xhr()) {
            $this->forward('form_tab', null, array('id'=>$id));
        } else {
            BResponse::i()->redirect(BApp::url('FCom_CustomAttr', '/customattr/form/'.$id));
        }
    }

}