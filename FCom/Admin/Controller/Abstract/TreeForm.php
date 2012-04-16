<?php

abstract class FCom_Admin_Controller_Abstract_TreeForm extends FCom_Admin_Controller_Abstract
{
//    protected $_origClass = __CLASS__;
//    protected $_permission = 'cms/nav';
//    protected $_navModelClass = 'FCom_Cms_Model_Nav';
//    protected $_treeLayoutName = '/cms/nav';
//    protected $_formLayoutName = '/cms/nav/tree_form';
//    protected $_formViewName = 'cms/nav-tree-form';

    public function action_index()
    {
        $this->layout($this->_treeLayoutName);
    }

    public function action_tree_data()
    {
        $class = $this->_navModelClass;
        $r = BRequest::i();
        $result = null;
        switch ($r->get('operation')) {
        case 'get_children':
            $node = $class::i()->load($r->get('id'));
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
        $class = $this->_navModelClass;
        $r = BRequest::i();
        try {
            if (!($node = $class::i()->load($r->post('id')))) {
                throw new BException('Invalid ID');
            }
            $result = array('status'=>1);

            $eventName = static::$_origClass.'::action_tree_data__POST.'.$r->post('operation');
            BPubSub::i()->fire($eventName.'.before', $r->post());

            switch ($r->post('operation')) {
            case 'create_node':
                $child = $node->createChild($r->post('title'));
                $node->cacheSaveDirty();
                $result['id'] = $child->id;
                break;

            case 'rename_node':
                if ($node->id<2) throw new BException("Can't rename root");
                $node->rename($r->post('title'), true);
                $node->cacheSaveDirty();
                break;

            case 'move_node':
                if ($node->id<2) throw new BException("Can't move root");
                if ($r->post('ref')!=$node->parent()->id) $node->move($r->post('ref'));
                if ($r->post('position')!==null) $node->reorder($r->post('position')+1);
                $node->cacheSaveDirty();
                break;

            case 'remove_node':
                if ($node->id<2) throw new BException("Can't remove root");
                $node->delete();
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
        $class = $this->_navModelClass;
        $this->layout($this->_formLayoutName);
        if ($id = BRequest::i()->params('id', true)) {
            $id = preg_replace('#^[^0-9]+#', '', $id);
            $model = $class::i()->load($id);
            $this->_prepareTreeForm($model);
        } else {
            $model = $class::i()->create();
        }
        $this->processFormTabs($this->view($this->_formViewName), $model, 'edit');
    }

    public function action_tree_form__POST()
    {
        $class = $this->_navModelClass;
        try {
            $id = BRequest::i()->params('id', true);
            if (!$id || !($model = $class::i()->load($id))) {
                throw new Exception('Invalid node ID');
            }
            $model->set(BRequest::i()->post('model'))
                ->set(array('url_path'=>null, 'full_name'=>null));
            $model->save();
            $model->refreshDescendants(true, true);

            $result = array('status'=>'success', 'message'=>'Node updated');
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        BResponse::i()->json($result);
    }

    protected function _prepareTreeForm($model)
    {

    }
}