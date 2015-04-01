<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Admin_Controller_Inventory
 *
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_Catalog_Admin_Controller_Inventory extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'catalog/inventory';
    protected $_modelClass = 'Sellvana_Catalog_Model_InventorySku';
    protected $_gridHref = 'catalog/inventory';
    protected $_gridTitle = 'Inventory Management';
    protected $_recordName = 'Inventory SKU';
    protected $_mainTableAlias = 's';
    protected $_navPath = 'catalog/inventory';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 50, 'index' => 's.id'],
            ['type' => 'input', 'name' => 'inventory_sku', 'label' => 'SKU', 'width' => 300,
                    'editable' => true, 'addable' => true, 'editor' => 'text',
                    'validation' => ['required' => true, 'unique' => $this->BApp->href('catalog/inventory/unique')]
            ],
            ['name' => 'product_name', 'label' => 'Product Name', 'width' => 300],
            ['type' => 'input', 'name' => 'cost', 'label' => 'Cost', 'width' => 300,
                'editable' => true, 'addable' => true, 'edit_inline' => true,'editor' => 'text', 'validation' => ['number' => true]],
            ['type' => 'input', 'name' => 'stock_qty', 'label' => 'Quantity', 'width' => 150,
                'editable' => true, 'addable' => true, 'edit_inline' => true,
                'editor' => 'text', 'validation' => ['required' => true, 'number' => true]],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit', 'icon' => 'icon-pencil ', 'cssClass' => 'btn-xs btn-edit-inline'],
                ['name' => 'save-inline', 'icon' => ' icon-ok-sign', 'cssClass' => 'btn-xs btn-save-inline hide'],
                ['name' => 'delete'],
            ]],
        ];
        $config['actions'] = [
            'edit' => true,
            'delete' => true
        ];
        $config['callbacks']['before_edit_inline'] = '
            this.$el.find("input").addClass("input-stock");
            this.$el.find("input[name=\'cost\']").val(this.model.get("tmp_cost"));
            function stockInputValidate(value, elem, params) {
                if (value < 0) {
                    return false;
                }
                return true;
            }
            $.validator.addMethod("stockInputValidate",stockInputValidate , function(params, element) {
                if ($(element).attr("name") == "cost") {
                    return "'.$this->BLocale->_('The cost of an item cannot be less than zero').'";
                }
                if ($(element).attr("name") == "stock_qty") {
                    return "'.$this->BLocale->_('Stock Item cannot have less than 0 quantity in stock').'";
                }

            });
            $.validator.addClassRules("input-stock", {
                stockInputValidate: true
            });
        ';
        $config['filters'] = [
            ['field' => 'inventory_sku', 'type' => 'text'],
            ['field' => 'product_name', 'type' => 'text'],
            ['field' => 'is_salable', 'type' => 'multiselect'],
            ['field' => 'qty_in_stock', 'type' => 'number-range'],
        ];
        $config['grid_before_create'] = 'stockGridRegister';
        $config['new_button'] = '#add_new_sku';
        return $config;
    }

    public function productStockPolicy($model)
    {
        $stock_policy = [
            'manage_stock' => 0,
            'stock_qty' => '',
            'out_stock' => 'keep_selling',
            'maximum_quantity_shopping' => '',
            'quantity_items_status' => '',
            'notify_administrator_quantity' => '',
        ];
        if (isset($model->data_serialized)) {
            $data = $this->BUtil->objectToArray(json_decode($model->data_serialized));
            if (isset($data['stock_policy'])) {
                $stock_policy = $data['stock_policy'];
            }
        }
        return $stock_policy;
    }

    public function action_restore_stock_policy()
    {
        $post = $this->BRequest->post();
        $config = $this->BConfig->get('modules/Sellvana_Catalog');
        $result = '';
        if (isset($post['restore'])) {
            switch($post['restore']) {
                case 'maximum_quantity_shopping':case 'quantity_items_status':case 'notify_administrator_quantity':
                    if ($config) {
                        $result = $config[$post['restore']];
                    }
                     break;
                case 'out_stock':
                    $result = 'back_order';
                    break;
                default:
                    break;
            }
        }
        $this->BResponse->json(['result' => $result]);
    }
    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(['actions' => [
            'new' => '<button type="button" id="add_new_sku" class="btn grid-new btn-primary _modal">'
                . $this->BLocale->_('New Sku') . '</button>']]);
    }

    /**
     * ajax check code is unique
     */
    public function action_unique__POST()
    {
        try {
            $post = $this->BRequest->post();
            $data = each($post);
            if (!isset($data['key']) || !isset($data['value'])) {
                throw new BException('Invalid post data');
            }
            $key = $this->BDb->sanitizeFieldName($data['key']);
            $value = $data['value'];
            $exists = $this->Sellvana_Catalog_Model_InventorySku->load($value, $key);
            $result = ['unique' => !$exists, 'id' => !$exists ? -1 : $exists->id()];
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
        $this->BResponse->json($result);
    }

    public function action_grid_data__POST()
    {
        $r = $this->BRequest;
        $p = $r->post();
        switch ($p['oper']) {
            case 'edit':
                // avoid error when edit
                $p['tmp_cost'] = $p['cost'];
                unset($p['oper']);
                if (isset($p['sku'])) {
                    $prod = $this->Sellvana_Catalog_Model_Product->load($p['sku'], 'product_sku');
                    $this->Sellvana_Catalog_Model_InventorySku->load($p['id'])->set('status', $p['status'])->save();
                    if ($prod) {
                        $data_serialized = $this->BUtil->objectToArray(json_decode($prod->get('data_serialized')));
                        if (!isset($data_serialized['stock_policy']))  {
                            $data_serialized['stock_policy'] = ['stock_qty' => $p['stock_qty'], 'out_stock' => $p['out_stock'], 'manage_stock' => $p['manage_stock']];
                        } else {
                            $data_serialized['stock_policy']['stock_qty'] = $p['stock_qty'];
                            $data_serialized['stock_policy']['out_stock'] = $p['out_stock'];
                            $data_serialized['stock_policy']['manage_stock'] = $p['status'];
                        }
                        $prod->setData('stock_policy', $data_serialized['stock_policy']);
                        $prod->set('cost', $p['cost']);
                        $prod->save();
                    }
                }
                if ($p['cost'] != '') {
                    $p['cost'] = $this->BLocale->currency($p['cost']);
                }
                $this->BResponse->json($p);
                break;
            case 'mass-edit':
                $id = $p['id'];
                $args['ids'] = explode(',', $id);
                $data = $p;
                $hlp = $this->Sellvana_Catalog_Model_InventorySku;
                $models = $hlp->orm()->where_in('id', $args['ids'])->find_many_assoc();
                $skus = $this->BUtil->arrayToOptions($models, 'sku');
                $products = $this->Sellvana_Catalog_Model_Product->orm()->where_in('product_sku', $skus)->find_many_assoc('product_sku');
                foreach ($models as $stock) {
                    $stock->set('status', $data['status'])->save();
                    if (!empty($products[$stock->get('sku')])) {
                        $prod = $products[$stock->get('sku')];
                        $data_serialized = $this->BUtil->objectToArray(json_decode($prod->get('data_serialized')));
                        if (!isset($data_serialized['stock_policy']))  {
                            $data_serialized['stock_policy'] = ['out_stock' => $p['out_stock'], 'manage_stock' => $p['status']];
                        } else {
                            $data_serialized['stock_policy']['out_stock'] = $p['out_stock'];
                            $data_serialized['stock_policy']['manage_stock'] = $p['status'];
                        }
                        $prod->setData('stock_policy', $data_serialized['stock_policy']);
                        $prod->save();
                    }
                }
                $this->BResponse->json(['success' => true]);
                break;
            default:
                $this->_processGridDataPost($this->_modelClass);
                break;
        }
    }
}
