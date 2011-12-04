<?php

class FCom_Catalog_Controller_Admin extends FCom_Admin_ControllerAbstract
{
    public function action_categories()
    {
        BResponse::i()->json(BuckyUI::i()->jqgridData(FCom_Catalog_Model_Category::i()->orm()));
    }

    public function action_products()
    {
        BResponse::i()->json(FCom_Catalog_Model_Product::i()->orm()->paginate(null, array('as_array'=>true)));
        //BResponse::i()->json(DAdminMain::jqgridData(AProduct::i()->orm()));
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
            if (!($c = FCom_Catalog_Model_Category::load($r->post('id')))) {
                throw new BException('Invalid category');
            }
            $result = array('status'=>1);

            BPubSub::i()->fire('category_tree_post.'.$r->post('operation').'.before', $r->post());

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
                if (!BPubSub::i()->fire('category_tree_post.'.$r->post('operation'), $r->post())) {
                    throw new BException('Not implemented');
                }
            }

            BPubSub::i()->fire('category_tree_post.'.$r->post('operation').'.after', $r->post());
        } catch (Exception $e) {
            $result = array('status'=>0, 'message'=>$e->getMessage());
        }
        BResponse::i()->json($result);
    }
}