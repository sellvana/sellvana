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
        $bool = [0 => 'no', 1 => 'Yes'];
        $backorderOptions = $invHlp->fieldOptions('allow_backorder');
        $packOptions = $invHlp->fieldOptions('pack_separate');
        return [
            'id' => 'inventory',
            'data_url' => 'inventory/grid_data',
            'columns' => [
                ['type' => 'row_select'],
                ['type' => 'btn_group', 'buttons' => [
                    ['name' => 'edit'],
                    ['name' => 'delete'],
                ]],
                ['name' => 'id', 'label' => 'ID', 'width' => 50],
                ['name' => 'title', 'label' => 'Title'],
                ['name' => 'inventory_sku', 'label' => 'SKU'],
                #['name' => 'manage_inventory', 'label' => 'Manage', 'options' => $manInvOptions, 'multirow_edit' => true],
                ['name' => 'allow_backorder', 'label' => 'Allow Backorder', 'options' => $backorderOptions, 'multirow_edit' => true],
                ['name' => 'pack_separate', 'label' => 'Pack Separate', 'options' => $packOptions, 'multirow_edit' => true],
                ['name' => 'qty_in_stock', 'label' => 'Quantity In Stock', 'multirow_edit' => true],
                ['name' => 'qty_reserved', 'label' => 'Qty Reserved', 'multirow_edit' => true],
                ['name' => 'qty_buffer', 'label' => 'Qty Buffer', 'multirow_edit' => true],
                ['name' => 'qty_warn_customer', 'label' => 'Qty to Warn Customer', 'multirow_edit' => true],
                ['name' => 'qty_notify_admin', 'label' => 'Qty to Notify Admin', 'multirow_edit' => true],
                ['name' => 'qty_cart_min', 'label' => 'Min Qty in Cart', 'multirow_edit' => true],
                ['name' => 'qty_cart_max', 'label' => 'Max Qty in Cart', 'multirow_edit' => true],
                ['name' => 'qty_cart_inc', 'label' => 'Cart Increment', 'multirow_edit' => true],
                ['name' => 'unit_cost', 'label' => 'Unit Cost', 'multirow_edit' => true],
                ['name' => 'net_weight', 'label' => 'Net Weight', 'multirow_edit' => true],
                ['name' => 'shipping_weight', 'label' => 'Ship Weight', 'multirow_edit' => true],
                ['name' => 'shipping_size', 'label' => 'Ship Size', 'multirow_edit' => true],
            ],
            'filters' => [
                ['field' => 'id', 'type' => 'number-range'],
                #['field' => 'manage_inventory', 'type' => 'multiselect'],
                ['field' => 'allow_backorder', 'type' => 'multiselect'],
                ['field' => 'title', 'type' => 'text'],
                ['field' => 'inventory_sku', 'type' => 'text'],
                ['field' => 'unit_cost', 'type' => 'number-range'],
                ['field' => 'net_weight', 'type' => 'number-range'],
                ['field' => 'shipping_weight', 'type' => 'number-range'],
                ['field' => 'qty_in_stock', 'type' => 'number-range'],
                ['field' => 'qty_reserved', 'type' => 'number-range'],
                ['field' => 'qty_buffer', 'type' => 'number-range'],
                ['field' => 'qty_warn_customer', 'type' => 'number-range'],
                ['field' => 'qty_notify_admin', 'type' => 'number-range'],
                ['field' => 'qty_cart_min', 'type' => 'number-range'],
                ['field' => 'qty_cart_max', 'type' => 'number-range'],
                ['field' => 'qty_cart_inc', 'type' => 'number-range'],
                ['field' => 'pack_separate', 'type' => 'multiselect'],
            ],
            'export' => true,
            'pager' => true,
            'bulk_actions' => [
                ['name' => 'edit', 'label' => 'Edit'],
                ['name' => 'delete', 'label' => 'Delete']
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

        $inventory = $this->Sellvana_Catalog_Model_InventorySku->load($pId);
        if (!$inventory) {
            throw new BException('Inventory not found');
        }

        $result = [];

        $result['form']['inventory'] = $inventory->as_array();

        if ($this->BModuleRegistry->isLoaded('Sellvana_CustomerGroups')) {
            $groups =  $this->Sellvana_CustomerGroups_Model_Group->groupsOptions();;
            if ($groups) {
                $result['form']['config']['options']['customer_groups'] = $this->BUtil->arrayMapToSeq($groups);
            }
        }
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite')) {
            $sites = $this->Sellvana_MultiSite_Model_Site->siteOptions();
            if ($sites) {
                $result['form']['config']['options']['multi_site'] = $this->BUtil->arrayMapToSeq($sites);
            }
        }
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiCurrency')) {
            $currencies = $this->Sellvana_MultiCurrency_Main->getAvailableCurrencies();
            if ($currencies) {
                $result['form']['config']['options']['multi_currency'] = $this->BUtil->arrayMapToSeq($currencies);
            }
        }

        $result['form']['config']['actions'] = true;

        $result['form']['config']['tabs'] = '/catalog/inventory/form';
        $result['form']['config']['default_field'] = ['model' => 'inventory'];
        $result['form']['config']['fields'] = [
            ['name' => 'qty_in_stock', 'label' => 'Qty In Stock', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'inventory_sku', 'label' => 'SKU', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
        ];

        $result['form']['i18n'] = 'inventory';

        return $result;
    }
}