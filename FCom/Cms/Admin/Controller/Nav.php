<?php

class FCom_Cms_Admin_Controller_Nav extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'cms/nav';

    public function action_index()
    {
        $this->layout('/cms/nav');
    }

    public function action_tree_data()
    {
        $r = BRequest::i();
        $result = null;
        switch ($r->get('operation')) {
        case 'get_children':
            $node = FCom_Cms_Model_Nav::i()->load($r->get('id'));
            if ($r->get('id')==1 && !$r->get('refresh')) {
                $result = array(
                    'data' => $node->node_name?$node->node_name:'ROOT',
                    'attr' => array('id'=>$node->id),
                    'state' => 'open',
                    'rel' => 'root',
                    'children' => $this->_nodeChildren($node, $r->get('expanded')=='true'?10:0),
                );
            } else {
                $node->descendants();
                $result = $this->_nodeChildren($node, 100);
            }
            break;
        }
        BResponse::i()->json($result);
    }

    protected function _nodeChildren($node, $depth=0)
    {
        $children = array();
        foreach ($node->children() as $c) {
            $children[] = array(
                'data'=>$c->node_name,
                'attr'=>array('id'=>$c->id),
                'state'=>$c->num_children?($depth?'open':'closed'):null,
                'rel'=>$c->num_children?'parent':'leaf',
                'position' => $c->sort_order,
                'children'=>$depth && $c->num_children ? $this->_nodeChildren($c, $depth-1) : null,
            );
        }
        return $children;
    }

    public function action_tree_data__POST()
    {
        $r = BRequest::i();
        try {
            if (!($c = FCom_Cms_Model_Nav::i()->load($r->post('id')))) {
                throw new BException('Invalid category');
            }
            $result = array('status'=>1);

            $eventName = __METHOD__.'.'.$r->post('operation');
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

    public function action_tree_form()
    {
        $this->layout('/cms/nav/tree_form');
        $view = $this->view('cms/nav-tree-form');
        $nodeTypes = array('cms_page'=>'CMS Page');
        BPubSub::i()->fire(__METHOD__, array('node_types'=>&$nodeTypes));
        $view->node_types = $nodeTypes;
        if (!$model = FCom_Cms_Model_Page::i()->load(BRequest::i()->params('id'))) {
            $model = FCom_Cms_Model_Page::i()->create();
        }

        $this->initFormTabs($view, $model);
    }

    public function action_tree_form__POST()
    {

    }
}