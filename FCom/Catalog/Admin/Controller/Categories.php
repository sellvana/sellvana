<?php

class FCom_Catalog_Admin_Controller_Categories extends BActionController
{

    public function action_categories()
    {
        $orm = FCom_Catalog_Model_Category::i()->orm();
        $data = BuckyUI::i()->jqgridData($orm);
        BResponse::i()->json($data);
    }
    public function action_index()
    {
        BLayout::i()->hookView('main', 'nav')->hookView('main', 'categories');
        BResponse::i()->render();
    }

    public function action_config()
    {
        $config = array(
            'products_grid' => array(
                'url' => BApp::m('FCom_Catalog')->baseHref().'/products',
                'grid' => array(
                    //'forceFitColumns'=>true, // https://github.com/mleibman/SlickGrid/issues/223
                    'editable'=>true,
                    'autoEdit'=>false,
                    'asyncEditorLoading'=>true,
                    'enableAddRow'=>true,
                    'enableCellNavigation'=>true,
                    'enableColumnReorder'=>true
                ),
                'columns'=>array(
                    array('id'=>'id', 'name'=>'#', 'field'=>'id', 'width'=>60, 'sortable'=>true),
                    array('id'=>'product_name', 'name'=>'Name', 'field'=>'product_name', 'width'=>300, 'editor'=>'LongTextCellEditor', 'sortable'=>true),
                    array('id'=>'base_price', 'name'=>'Price', 'field'=>'base_price', 'width'=>80, 'editor'=>'TextCellEditor', 'sortable'=>true),
                    array('id'=>'manuf_sku', 'name'=>'Part #', 'field'=>'manuf_sku', 'width'=>100, 'sortable'=>true),
                    #array('id'=>'%', 'name'=>'%', 'field'=>'percent', 'formatter'=>'GraphicalPercentCompleteCellFormatter', 'editor'=>'PercentCompleteCellEditor'),
                    #array('id'=>'bool', 'name'=>'bool', 'field'=>'bool', 'formatter'=>'BoolCellFormatter', 'editor'=>'YesNoCheckboxCellEditor'),
                ),
                'sub'=>array('resize'=>'#details-pane/center'),
                'pager'=>array('id'=>'#products-grid-pager'),
                'columnpicker'=>true,
                //'checkboxSelector'=>true,
                //'reorder'=>true,
                'dnd'=>true,
                'undo'=>true,
            ),
            'aliases_grid' => array(
                'url' => BApp::m('Denteva_Admin')->baseHref().'/categories/aliases',
                'grid' => array(
                    //'forceFitColumns'=>true, // https://github.com/mleibman/SlickGrid/issues/223
                    'editable'=>true,
                    'autoEdit'=>false,
                    'asyncEditorLoading'=>true,
                    'enableAddRow'=>true,
                    'enableCellNavigation'=>true,
                    'enableColumnReorder'=>true
                ),
                'columns'=>array(
                    array('id'=>'vendor_code', 'name'=>'Vendor', 'field'=>'vendor_code', 'width'=>100, 'editor'=>'TextCellEditor', 'sortable'=>true),
                    array('id'=>'alias_name', 'name'=>'Alias', 'field'=>'alias_name', 'width'=>300, 'editor'=>'TextCellEditor', 'sortable'=>true),
                ),
                'sub'=>array('resize'=>'#details-pane/center'),
                'pager'=>array('id'=>'#aliases-grid-pager'),
                'columnpicker'=>true,
                'dnd'=>true,
                'undo'=>true,
            ),
        );
        BResponse::i()->json($config);
    }

    public function action_aliases()
    {
        BResponse::i()->json(Denteva_Merge_Model_CategoryAlias::i()->orm()->paginate(null, array('as_array'=>true)));
    }


    public function action_category_tree_get()
    {
        $r = BRequest::i();
        $result = null;
        switch ($r->get('operation')) {
        case 'get_children':
            $category = FCom_Catalog_Model_Category::i()->load($r->get('id'));
            if ($r->get('id')==1 && !$r->get('refresh')) {
                $result = array(
                    'data' => $category->node_name?$category->node_name:'ROOT',
                    'attr' => array('id'=>$category->id),
                    'state' => 'open',
                    'rel' => 'root',
                    'children' => $this->_categoryChildren($category, $r->get('expanded')=='true'?10:0),
                );
            } else {
                $category->descendants();
                $result = $this->_categoryChildren($category, 100);
            }
            break;
        }
        BResponse::i()->json($result);
    }

    protected function _categoryChildren($category, $depth=0)
    {
        $children = array();
        foreach ($category->children() as $c) {
            $children[] = array(
                'data'=>$c->node_name,
                'attr'=>array('id'=>$c->id),
                'state'=>$c->num_children?($depth?'open':'closed'):null,
                'rel'=>$c->num_children?'parent':'leaf',
                'position' => $c->sort_order,
                'children'=>$depth && $c->num_children ? $this->_categoryChildren($c, $depth-1) : null,
            );
        }
        return $children;
    }

    public function action_category_tree_post()
    {
        $r = BRequest::i();
        try {
            if (!($c = FCom_Catalog_Model_Category::i()->load($r->post('id')))) {
                throw new BException('Invalid category');
            }
            $result = array('status'=>1);

            $eventName = 'category_tree_post.'.$r->post('operation');
            BPubSub::i()->fire($eventName.'.before', $r->post());

            switch ($r->post('operation')) {
            case 'create_node':
                $child = $c->createChild($r->post('title'));
                $c->cacheSaveDirty();
                $result['id'] = $child->id;
                break;

            case 'rename_node':
                if ($c->id<2) throw new BException("Can't rename root");
                $c->rename($r->post('title'), true);
                $c->cacheSaveDirty();
                break;

            case 'move_node':
                if ($c->id<2) throw new BException("Can't move root");
                if ($r->post('ref')!=$c->parent()->id) $c->move($r->post('ref'));
                if ($r->post('position')!==null) $c->reorder($r->post('position')+1);
                $c->cacheSaveDirty();
                break;

            case 'remove_node':
                if ($c->id<2) throw new BException("Can't remove root");
                $c->delete();
                break;

            default:
                if (!BPubSub::i()->fire($eventName, $r->post())) {
                    throw new BException('Not implemented');
                }
            }

            BPubSub::i()->fire($eventName.'.after', $r->post());
        } catch (Exception $e) {
            $result = array('status'=>0, 'message'=>$e->getMessage());
        }
        BResponse::i()->json($result);
    }
}