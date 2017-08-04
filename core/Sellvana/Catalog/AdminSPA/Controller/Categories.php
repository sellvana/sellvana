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
            $this->BResponse->status('403', (('Available only for XHR')), 'Available only for XHR');
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

            $result[static::FORM]['category'] = $category->as_array();

            $result[static::FORM][static::CONFIG][static::TABS] = $this->getFormTabs('/catalog/categories/form');
            $result[static::FORM][static::CONFIG][static::FIELDS] = [
                static::DEFAULT_FIELD => [static::MODEL => 'category'],
                [static::NAME => 'node_name', static::LABEL => (('Label')), static::REQUIRED => true, static::I18N => true],
                [static::NAME => 'url_key', static::LABEL => (('URL Key'))],
                [static::NAME => 'sort_order', static::INPUT_TYPE => 'number', static::LABEL => (('Nav Sort Order'))],
                [static::NAME => 'page_title', static::LABEL => (('Page Title')), static::I18N => true],
                [static::NAME => 'meta_title', static::LABEL => (('Meta Title')), static::I18N => true],
                [static::NAME => 'meta_description', static::TYPE => 'textarea', static::LABEL => (('Meta Description')), static::I18N => true],
                [static::NAME => 'meta_keywords', static::TYPE => 'textarea', static::LABEL => (('Meta Keywords')), static::I18N => true],
            ];
            $result[static::FORM][static::CONFIG]['validation'] = [
                ['field' => 'node_name', static::REQUIRED => true],
            ];

            $result[static::FORM][static::I18N] = $this->getModelTranslations('category', $category->id());

            $result[static::FORM] = $this->normalizeFormConfig($result[static::FORM]);

            $this->ok();
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
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
                //'attr'     => [static::ID => $c->id()],
                //'rel'      => $node ? 'root' : ($numChildren ? 'parent' : 'leaf'),
                //'position' => $c->get('sort_order'),
            ];
        }
        return $children;
    }
}