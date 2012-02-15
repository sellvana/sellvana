<?php

class FCom_CustomAttr_Admin_Controller_AttrSets extends FCom_Catalog_Admin_Controller_Products
{
    public function attrSetsGridConfig()
    {
        $config = array(
            'grid' => array(
                'id'      => 'attrsets',
                'url'     => BApp::url('FCom_CustomAttr', '/attrsets/grid_data'),
                'editurl' => BApp::url('FCom_CustomAttr', '/attrsets/grid_data'),
                'columns' => array(
                    'id' => array('label'=>'ID', 'width'=>55, 'sorttype'=>'number', 'key'=>true),
                    'set_code' => array('label'=>'Code', 'width'=>100, 'editable'=>true),
                    'set_name' => array('label'=>'Name', 'width'=>100, 'editable'=>true),
                    'attr_codes' => array('labek' => 'Attributes', 'width'=>250),
                ),
            ),
            'subGrid' => array(
                'grid' => array(
                    'datatype' => 'local',
                    'data' => new BType('data'),
                    'columns' => array(
                        'attr_code' => array('name' => 'attr_code', 'label'=>'Attr.Code', 'width'=>100),
                    ),
                    'multiselect' => true,
                ),
                'custom' => array(
                    'jsBefore' => "
var data = [], attrs = jQuery('#attrsets').jqGrid('getRowData', row_id).attr_codes;
var src = attrs ? attrs.split(',') : [], i;
for (i=0; i<src.length; i++) data.push({attr_code:src[i]});
                    ",
                ),
            ),
            'navGrid' => array('search'=>false, 'refresh'=>false),
            'inlineNav' => array(),
            #'searchGrid' => array('multipleSearch'=>true, 'multipleGroup'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
            array('navButtonAdd', 'caption' => 'Columns', 'title' => 'Reorder Columns', 'onClickButton' => 'function() {
                jQuery("#attrsets").jqGrid("columnChooser");
            }'),
        );
        BPubSub::i()->fire(__METHOD__, array('config'=>&$config));
        return $config;
    }

    public function attributesGridConfig()
    {
        $config = array(
            'grid' => array(
                'id' => 'attributes',
                'url' => BApp::url('FCom_CustomAttr', '/attrsets/attr_grid_data'),
                'columns' => array(
                    'id' => array('label' => 'ID'),
                    'attr_code' => array('label' => 'Code'),
                    'attr_name' => array('label' => 'Name'),
                    'frontend_label' => array('label' => 'Frontend Label'),
                ),
                'multiselect' => true,
            ),
        );
        return $config;
    }

    public function action_index()
    {
        $this->layout('/customattr/attrsets');
    }

    public function action_grid_data()
    {
        $orm = FCom_CustomAttr_Model_Set::i()->orm()->table_alias('s')->select('s.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, __METHOD__);
        BResponse::i()->json($data);
    }

    public function action_grid_data_post()
    {
        $r = BRequest::i();
        switch ($r->post('oper')) {
        case 'add':
            $result = array('id'=>2, 'set_code'=>$r->post('set_code'), 'set_name'=>$r->post('set_name'));
            break;
        }
        BResponse::i()->redirect(BApp::url('FCom_CustomAttr', '/attrsets/grid_data'));
        BResponse::i()->json($result);
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