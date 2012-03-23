<?php

class FCom_Catalog_Admin_Controller_Families extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $grid = BLayout::i()->view('jqgrid')->set('config', array(
            'grid' => array(
                'id' => 'product_families',
                'url' => BApp::href('catalog/families/grid_data'),
                'columns' => array(
                    'id' => array('label'=>'ID', 'width'=>50),
                    'family_name' => array('label'=>'Family Name', 'width'=>250),
                ),
            ),
        ));
        BPubSub::i()->fire(__METHOD__, array('grid'=>$grid));
        $this->layout('/catalog/families');
    }

    public function action_grid_data()
    {
        $orm = FCom_Catalog_Model_Family::i()->orm()->table_alias('f')->select('f.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, __METHOD__);
        BResponse::i()->json($data);
    }

    public function action_form()
    {
        $this->layout('/catalog/families/form');
    }

    public function action_form_post()
    {
        $r = BRequest::i();
        try {
            $hlp = FCom_Catalog_Model_Family::i();
            $data = $r->post();
            $id = $r->params('id');

            if ($r->xhr()) {
                $model = $hlp->load($data['model']['family_name'], 'family_name');
            }
            if (!$model) {
                if (!$id) {
                    $model = $hlp->create($data['model']);
                } else {
                    $model->set($data['model']);
                }
                $model->save();
            }
            if ($r->xhr()) {
                BResponse::i()->json(array('model'=>$model->as_array()));
                exit;
            }

            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }
        BResponse::i()->redirect(BApp::href('catalog/families/form/'.$model->id));
    }

    public function action_autocomplete()
    {
        $orm = FCom_Catalog_Model_Family::i()->orm()
            ->where_like('family_name', '%'.BRequest::i()->get('term').'%')
            ->select('id')->select('family_name', 'value')
            ->limit(20)
        ;
        if (($manufId = BRequest::i()->get('manuf_id'))) {
            $orm->where('manuf_vendor_id', $manufId);
        }
        BResponse::i()->json(BDb::many_as_array($orm->find_many()));
    }

    public function action_product_data()
    {
        $orm = FCom_Catalog_Model_ProductFamily::i()->orm()->table_alias('pf')
            ->where('pf.family_id', BRequest::i()->get('family'))

            ->join('FCom_Catalog_Model_Product', array('p.id','=','pf.product_id'), 'p')
            ->select(array('p.id', 'p.product_name', 'p.manuf_sku'));

        BPubSub::i()->fire(__METHOD__, array('orm'=>$orm));

        BResponse::i()->json(BDb::many_as_array($orm->find_many()));
    }
}