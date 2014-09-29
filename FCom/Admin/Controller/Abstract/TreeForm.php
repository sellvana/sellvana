<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_Controller_Abstract_TreeForm
 * @property FCom_Core_Model_TreeAbstract $FCom_Core_Model_TreeAbstract
 */
abstract class FCom_Admin_Controller_Abstract_TreeForm extends FCom_Admin_Controller_Abstract
{
    protected $_permission;
    protected $_navModelClass;
    protected $_treeLayoutName;
    protected $_formLayoutName;
    protected $_formViewName;

    public $formId = 'tree_form';

    public function action_index()
    {
        $this->layout($this->_treeLayoutName);
    }

    public function action_tree_data()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->status('403', 'Available only for XHR', 'Available only for XHR');
            return;
        }

        $class = $this->_navModelClass;
        $r = $this->BRequest;
        $result = null;
        switch ($r->get('operation')) {
            case 'get_children':
                if ($r->get('id') == 'NULL') {
                    $result = $this->_nodeChildren(null, 1);
                    /*
                    $rootNodes = $this->{$class}->orm()->where_null('parent_id')->find_many();
                    $result = array(
                        'data' => $node->node_name?$node->node_name:'ROOT',
                        'attr' => array('id'=>$node->id),
                        'state' => 'open',
                        'rel' => 'root',
                        'children' => $this->_nodeChildren($node, $r->get('expanded')=='true'?10:0),
                    );
                    */
                } else {
                    $node = $this->{$class}->load($r->get('id'));
                    /** @var FCom_Core_Model_TreeAbstract $node */
                    if ($node) {
                        $node->descendants();
                        $result = $this->_nodeChildren($node, 100);
                    } else {
                        $result = [];
                    }
                }
                break;
        }
        $this->BResponse->json($result);
    }

    /**
     * @param $node FCom_Core_Model_TreeAbstract
     * @param int $depth
     * @return array
     */
    protected function _nodeChildren($node, $depth = 0)
    {
        $class = $this->_navModelClass;
        /** @var FCom_Core_Model_TreeAbstract[] $nodeChildren */
        $nodeChildren = $node ? $node->children() : $this->{$class}->orm()->where_null('parent_id')->find_many();
        $children = [];
        foreach ($nodeChildren as $c) {
            $nodeName = $c->get('node_name');
            $numChildren = $c->get('num_children');
            $children[] = [
                'data'     => $nodeName ? $nodeName : 'ROOT',
                'attr'     => ['id' => $c->id()],
                'state'    => $numChildren ? ($depth ? 'open' : 'closed') : null,
                'rel'      => $node ? 'root' : ($numChildren ? 'parent' : 'leaf'),
                'position' => $c->get('sort_order'),
                'children' => $depth && $numChildren ? $this->_nodeChildren($c, $depth-1) : null,
            ];
        }
        return $children;
    }

    public function action_tree_data__POST()
    {
        $class = $this->_navModelClass;
        $r = $this->BRequest;
        try {
            if (!($node = $this->{$class}->load($r->post('id')))) {
                throw new BException('Invalid ID');
            }
            /** @var $node FCom_Core_Model_TreeAbstract */
            $result = ['status' => 1];

            $eventName = static::$_origClass . '::action_tree_data__POST.' . $r->post('operation');
            $this->BEvents->fire($eventName . ':before', $r->post());

            switch ($r->post('operation')) {
                case 'create_node':
                    if ($node->validateNodeName($r->post('title'), true)) {
                        $child = $node->createChild($r->post('title'));
                        $node->cacheSaveDirty();
                        $result['id'] = $child->id;
                    } else {
                        $result = ['status' => 0, 'message' => $this->_("Can't create node duplicate name node.")];
                    }
                    break;

                case 'rename_node':
                    if ($node->id < 2) {
                        throw new BException($this->_("Can't rename root"));
                    }
                    if ($node->validateNodeName($r->post('title'))) {
                        $node->rename($r->post('title'), true);
                        $node->cacheSaveDirty();
                    } else {
                        $result = ['status' => 0, 'message' => $this->_("Can't rename duplicate name node.")];
                    }

                    break;

                case 'move_node':
                    if ($node->id < 2) {
                        throw new BException("Can't move root");
                    }
                    if ($r->post('ref') != $node->parent()->id) {
                        $node->move($r->post('ref'));
                    }
                    if ($r->post('position') !== null) {
                        $node->reorder($r->post('position') + 1);
                    }
                    $node->cacheSaveDirty();
                    break;

                case 'remove_node':
                    if ($node->id < 2) {
                        throw new BException("Can't remove root");
                    }
                    $node->delete();
                    break;

                case 'clone':
                    if ($node->id < 2) {
                        throw new BException("Can't clone root");
                    }
                    $result['newNodeID'] = $this->cloneNode($node, $r->post('recursive'), true);
                    break;

                /* case 'check_node': case 'uncheck_node':
                     $product_id = $r->get('id');
                     if (!$product_id) {
                         break;
                     }

                     break;*/
                case 'reorderAZ':
                    $recursive = ($r->post('recursive')) ? true : false;
                    $node->reorderChildrenAZ($recursive);
                    break;
                default:
                    if (!$this->BEvents->fire($eventName, $r->post())) {
                        throw new BException('Not implemented');
                    }
            }

            $this->BEvents->fire($eventName . ':after', $r->post());
        } catch (Exception $e) {
            $result = ['status' => 0, 'message' => $e->getMessage()];
        }
        $this->BResponse->json($result);
    }

    public function action_tree_form()
    {
        $class = $this->_navModelClass;
        $this->layout($this->_formLayoutName);
        if ($id = $this->BRequest->param('id', true)) {
            $id = preg_replace('#^[^0-9]+#', '', $id);
            $model = $this->{$class}->load($id);
            $this->_prepareTreeForm($model);
        } else {
            $model = $this->{$class}->create();
        }
        $this->processFormTabs($this->view($this->_formViewName), $model, 'edit');
    }

    public function action_tree_form__POST()
    {
        $class = $this->_navModelClass;

        try {
            $id = $this->BRequest->param('id', true);
            $id = preg_replace('#^[^0-9]+#', '', $id);
            if (!$id || !($model = $this->{$class}->load($id))) {
                throw new Exception('Invalid node ID');
            }

            /** @var FCom_Core_Model_TreeAbstract $model */
            $model->set($this->BRequest->post('model'))
                ->set(['url_path' => null, 'full_name' => null]);

            if ($this->BRequest->post('action') === 'clone') {
                $parent = $model->parent();
                $cloneName = $model->get('node_name') . '-1';
                $cloned = $parent->createChild($cloneName);
                $cloned->set($this->BUtil->arrayMask($model->as_array(), 'id,id_path,node_name,full_name,sort_order,url_key,url_path', true));
                $model = $cloned;
            }

            //TODO figure out why validation always return false
            //if ($model->validate()) {
            //always return false -> update rules in FCom_Core_Model_Abstract
            /** @see FCom_Core_Model_Abstract */
            $formId = $this->formId;
            if ($model->validate($model->as_array(), [], $formId)) {

                $model->save();
                $model->refreshDescendants(true, true);
                $result = ['status' => 'success', 'message' => 'Node updated', 'path' => $model->full_name];
            } else {
                $this->message('Cannot save data, please fix above errors', 'error', 'validator-errors:' . $formId);
                $result = ['status' => 'error', 'message' => $this->getErrorMessages()];
            }
        } catch (Exception $e) {
//$this->BDebug->exceptionHandler($e);
#print_r(BORM::get_last_query());
#print_r($e); exit;
            $result = ['status' => 'error', 'message' => $e->getMessage()];
        }
        $this->BResponse->json($result);
    }

    public function getErrorMessages()
    {
        $messages = $this->BSession->messages('validator-errors:' . $this->formId);
        $errorMessages = [];
        foreach ($messages as $m) {
            if (is_array($m['msg']))
                $errorMessages[] = $m['msg']['error'];
            else
                $errorMessages[] = $m['msg'];
        }

        return implode("<br />", $errorMessages);
    }

    protected function _prepareTreeForm($model)
    {

    }

    /**
     * @param FCom_Core_Model_TreeAbstract $node
     * @param string $recursiveType 0: only this node, 1: plus immediately children, 2: plus all descendant
     * @param boolean $returnID
     * @return int|FCom_Core_Model_TreeAbstract
     * @throws BException
     */
    public function cloneNode($node, $recursiveType, $returnID = false)
    {
        if (!$node->id()) {
            throw new BException('Cannot clone unavailable node');
        }

        $cloneNode = $node->cloneMe();
        if ($cloneNode) {
            switch ($recursiveType) {
                case 0:
                default:
                    $result = true;
                    break;
                case 1:
                    $result = $node->cloneChildren($cloneNode);
                    break;
                case 2:
                    $result = $node->cloneChildren($cloneNode, true);
                    break;
            }
            if ($result) {
                return ($returnID) ? $cloneNode->id() : $cloneNode;
            }
        }
    }
}
