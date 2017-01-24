<?php

/**
 * Class Sellvana_Catalog_AdminSPA_Controller_Categories
 *
 * @property Sellvana_Catalog_Model_Category Sellvana_Catalog_Model_Category
 */
class Sellvana_Catalog_AdminSPA_Controller_Categories extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    use FCom_AdminSPA_AdminSPA_Controller_Trait_Form;

    public function action_form_data()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->status('403', 'Available only for XHR', 'Available only for XHR');
            return;
        }

        $r = $this->BRequest;
        $result = [];
        try {
            if ($this->BRequest->get('tree')) {
                $result['tree'] = $this->_nodeChildren();
            }
            $cId = $this->BRequest->get('id') ?: 1;

            $category = $this->Sellvana_Catalog_Model_Category->load($cId);
            if (!$category) {
                throw new BException('Category not found');
            }
            $result['form']['tabs'] = $this->getFormTabs('/catalog/categories/form');
            $result['form']['category'] = $category->as_array();
            $this->ok();
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    /**
     * @param $node FCom_Core_Model_TreeAbstract
     * @param int $depth
     * @return array
     */
    protected function _nodeChildren($node = null, $depth = 0)
    {
        /** @var FCom_Core_Model_TreeAbstract[] $nodeChildren */
        $nodeChildren = $node ? $node->children() : $this->Sellvana_Catalog_Model_Category->orm()
            ->where_null('parent_id')->find_many();
        $children = [];
        foreach ($nodeChildren as $c) {
            $nodeName = $c->get('node_name');
            $numChildren = $c->get('num_children');
            $children[] = [
                'label'     => $nodeName ? $nodeName : 'ROOT',
                'id'        => $c->id(),
                'open'      => $numChildren ? ($depth === 0 ? true : false) : null,
                'children'  => $numChildren ? $this->_nodeChildren($c, $depth + 1) : null,
                //'attr'     => ['id' => $c->id()],
                //'rel'      => $node ? 'root' : ($numChildren ? 'parent' : 'leaf'),
                //'position' => $c->get('sort_order'),
            ];
        }
        return $children;
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            $data = $this->BRequest->post();
            $this->ok()->addMessage('Category was saved successfully', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }
}