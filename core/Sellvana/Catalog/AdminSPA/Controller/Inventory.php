<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Inventory
 *
 * @property Sellvana_Catalog_Model_InventorySku Sellvana_Catalog_Model_InventorySku
 */
class Sellvana_Catalog_AdminSPA_Controller_Inventory extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        $invHlp = $this->Sellvana_Catalog_Model_InventorySku;
        $backorderOptions = $invHlp->fieldOptions('allow_backorder');
        $packOptions = $invHlp->fieldOptions('pack_separate');

        return [
            static::ID => 'inventory',
            static::DATA_URL => 'inventory/grid_data',
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT],
                [static::NAME => 'id', static::LABEL => (('ID')), static::WIDTH => 50],
                [static::NAME => 'title', static::LABEL => (('Title'))],
                [static::NAME => 'inventory_sku', static::LABEL => (('SKU')),
                    static::DATACELL_TEMPLATE => '<td><a :href="\'#/catalog/inventory/form?id=\'+row.id">{{row.inventory_sku}}</a></td>'],
                #[static::NAME => 'manage_inventory', static::LABEL => 'Manage', static::OPTIONS => $manInvOptions, 'multirow_edit' => true],
                [static::NAME => 'allow_backorder', static::LABEL => (('Allow Backorder')), static::OPTIONS => $backorderOptions, 'multirow_edit' => true],
                [static::NAME => 'pack_separate', static::LABEL => (('Pack Separate')), static::OPTIONS => $packOptions, 'multirow_edit' => true],
                [static::NAME => 'qty_in_stock', static::LABEL => (('Quantity In Stock')), 'multirow_edit' => true],
                [static::NAME => 'qty_reserved', static::LABEL => (('Qty Reserved')), 'multirow_edit' => true],
                [static::NAME => 'qty_buffer', static::LABEL => (('Qty Buffer')), 'multirow_edit' => true],
                [static::NAME => 'qty_warn_customer', static::LABEL => (('Qty to Warn Customer')), 'multirow_edit' => true],
                [static::NAME => 'qty_notify_admin', static::LABEL => (('Qty to Notify Admin')), 'multirow_edit' => true],
                [static::NAME => 'qty_cart_min', static::LABEL => (('Min Qty in Cart')), 'multirow_edit' => true],
                [static::NAME => 'qty_cart_max', static::LABEL => (('Max Qty in Cart')), 'multirow_edit' => true],
                [static::NAME => 'qty_cart_inc', static::LABEL => (('Cart Increment')), 'multirow_edit' => true],
                [static::NAME => 'unit_cost', static::LABEL => (('Unit Cost')), 'multirow_edit' => true],
                [static::NAME => 'net_weight', static::LABEL => (('Net Weight')), 'multirow_edit' => true],
                [static::NAME => 'shipping_weight', static::LABEL => (('Ship Weight')), 'multirow_edit' => true],
                [static::NAME => 'shipping_size', static::LABEL => (('Ship Size')), 'multirow_edit' => true],
            ],
            static::FILTERS => [
                ['field' => 'id', static::TYPE => 'number-range'],
                #['field' => 'manage_inventory', static::TYPE => 'multiselect'],
                ['field' => 'allow_backorder', static::TYPE => 'multiselect'],
                ['field' => 'title', static::TYPE => 'text'],
                ['field' => 'inventory_sku', static::TYPE => 'text'],
                ['field' => 'unit_cost', static::TYPE => 'number-range'],
                ['field' => 'net_weight', static::TYPE => 'number-range'],
                ['field' => 'shipping_weight', static::TYPE => 'number-range'],
                ['field' => 'qty_in_stock', static::TYPE => 'number-range'],
                ['field' => 'qty_reserved', static::TYPE => 'number-range'],
                ['field' => 'qty_buffer', static::TYPE => 'number-range'],
                ['field' => 'qty_warn_customer', static::TYPE => 'number-range'],
                ['field' => 'qty_notify_admin', static::TYPE => 'number-range'],
                ['field' => 'qty_cart_min', static::TYPE => 'number-range'],
                ['field' => 'qty_cart_max', static::TYPE => 'number-range'],
                ['field' => 'qty_cart_inc', static::TYPE => 'number-range'],
                ['field' => 'pack_separate', static::TYPE => 'multiselect'],
            ],
            static::EXPORT => true,
            static::PAGER => true,
            static::BULK_ACTIONS => [
                [static::NAME => 'edit', static::LABEL => (('Edit'))],
                [static::NAME => 'delete', static::LABEL => (('Delete'))]
            ]
        ];
    }

    public function getGridOrm()
    {
        return $this->Sellvana_Catalog_Model_InventorySku->orm('p');
    }

    public function getFormData()
    {
        $pId = $this->BRequest->get('id');
        $bool = [0 => (('no')), 1 => (('Yes'))];

        $inventory = $this->Sellvana_Catalog_Model_InventorySku->load($pId);
        if (!$inventory) {
            throw new BException('Inventory not found');
        }

        $countries = $this->BLocale->getAvailableCountries();

        $result = [];

        $result[static::FORM]['inventory'] = $inventory->as_array();

        $result[static::FORM][static::CONFIG][static::PAGE_ACTIONS] = $this->getDefaultFormPageActions();

        $result[static::FORM][static::CONFIG][static::TABS] = '/catalog/inventory/form';
        $result[static::FORM][static::CONFIG][static::FIELDS] = [
            static::DEFAULT_FIELD => [static::MODEL => 'inventory', static::TAB => 'main'],
            [static::NAME => 'inventory_sku', static::LABEL => (('Inventory SKU')), static::REQUIRED => true],
            [static::NAME => 'qty_in_stock', static::LABEL => (('Qty In Stock')), static::INPUT_TYPE => 'number'],
            [static::NAME => 'unit_cost', static::LABEL => (('Inventory Unit Cost')), static::INPUT_TYPE => 'text'],
            [static::NAME => 'allow_backorder', static::LABEL => (('Allow Backorders')), static::TYPE => 'checkbox'],
            [static::NAME => 'qty_warn_customer', static::LABEL => (('Minimal Qty to warn customer on frontend')), static::INPUT_TYPE => 'number'],
            [static::NAME => 'qty_notify_admin', static::LABEL => (('Minimal Qty to notify admin')), static::INPUT_TYPE => 'number'],
            [static::NAME => 'qty_cart_min', static::LABEL => (('Minimal Qty in Cart')), static::INPUT_TYPE => 'number'],
            [static::NAME => 'qty_cart_max', static::LABEL => (('Maximum Qty in Cart')), static::INPUT_TYPE => 'number'],
            [static::NAME => 'qty_cart_inc', static::LABEL => (('Qty in Cart Increment')), static::INPUT_TYPE => 'number'],
            [static::NAME => 'qty_buffer', static::LABEL => (('Buffer Qty In Stock')), static::INPUT_TYPE => 'number'],
            [static::NAME => 'pack_separate', static::LABEL => (('Pack Separately for Shipment')), static::TYPE => 'checkbox'],
            [static::NAME => 'net_weight', static::LABEL => (('Net Weight')), static::INPUT_TYPE => 'number'],
            [static::NAME => 'shipping_weight', static::LABEL => (('Shipping Weight')), static::INPUT_TYPE => 'number'],
            [static::NAME => 'shipping_size', static::LABEL => (('Shipping Size (WxDxH)')), static::INPUT_TYPE => 'number'],
            [static::NAME => 'hs_tariff_number', static::LABEL => (('Harmonized Tariff Number')), static::INPUT_TYPE => 'number'],
            [static::NAME => 'origin_country', static::LABEL => (('Country of Origin')), static::INPUT_TYPE => 'number', static::OPTIONS => $countries],
        ];

        $result[static::FORM][static::I18N] = 'inventory';

        return $result;
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            $r = $this->BRequest;
            $data = $r->post();
            $id = $r->param('id', true);
            $model = $this->Sellvana_Catalog_Model_InventorySku->load($id);
            if (!$model) {
                throw new BException("This item does not exist");
            }

            if ($data) {
                $model->set($data);
            }

            $origModelData = $modelData = $model->as_array();
            $validated = $model->validate($modelData, [], 'product');
            if ($modelData !== $origModelData) {
                var_dump($modelData);
                $model->set($modelData);
            }


            if ($validated) {
                $model->save();
                $result = $this->getFormData();
                $result[static::FORM] = $this->normalizeFormConfig($result[static::FORM]);
                $this->ok()->addMessage('Inventory was saved successfully', 'success');
            } else {
                $result = ['status' => 'error'];
                $this->error()->addMessage('Cannot save data, please fix above errors', 'error');
            }

        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function action_form_delete__POST()
    {
        try {
            $data = $this->BRequest->post();
            $id = $data['id'];
            $model = $this->Sellvana_Catalog_Model_InventorySku->load($id);
            if (!$model) {
                throw new BException("This item does not exist");
            }

            $model->delete();
            $this->ok()->addMessage('Product was deleted successfully', 'success');
            $result = ['status' => true];
            $this->respond($result);
        } catch (Exception $e) {
            $this->addMessage($e);
        }
    }
}