<?php

/**
 * Class Sellvana_MultiVendor_AdminSPA_Controller_Vendors
 *
 * @property Sellvana_MultiVendor_Model_Vendor Sellvana_MultiVendor_Model_Vendor
 */
class Sellvana_MultiVendor_AdminSPA_Controller_Vendors extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        $notifyTypeOptions = $this->Sellvana_MultiVendor_Model_Vendor->fieldOptions('notify_type');
        return [
            'data_url' => 'vendors/grid_data',
            'columns' => [
                ['type' => 'row-select'],
                ['name' => 'id', 'label' => (('ID')), 'index' => 'v.id'],
                ['name' => 'vendor_name', 'label' => (('Vendor Name')), 'index' => 'v.vendor_name'],
                ['name' => 'notify_type', 'label' => (('Notification')), 'options' => $notifyTypeOptions],
                ['name' => 'email_notify', 'label' => (('Email for Notification')), 'index' => 'v.email_notify'],
                ['name' => 'email_support', 'label' => (('Email for Support')), 'index' => 'v.email_support'],
                ['name' => 'create_at', 'label' => (('Created')), 'index' => 'v.create_at', 'format' => 'date'],
                ['name' => 'update_at', 'label' => (('Updated')), 'index' => 'v.update_at', 'format' => 'date'],
            ],
            'filters' => [
                ['name' => 'id', 'type' => 'number'],
                ['name' => 'vendor_name', 'type' => 'text'],
                ['name' => 'notify_type', 'type' => 'select'],
                ['name' => 'email_notify', 'type' => 'text'],
                ['name' => 'email_support', 'type' => 'text'],
                ['name' => 'create_at', 'type' => 'date'],
                ['name' => 'update_at', 'type' => 'date'],
            ],
            'bulk_actions' => [
                ['type' => 'delete'],
            ],
            'pager' => true,
            'export' => true,
        ];
    }

    public function getGridOrm()
    {
        return $this->Sellvana_MultiVendor_Model_Vendor->orm();
    }

    public function action_form_data()
    {
        $result = [];

        $pId = $this->BRequest->get('id');
        try {
            $result['form']['tabs'] = $this->getFormTabs('/vendors/form');
            if ($pId) {
                $vendor = $this->Sellvana_MultiVendor_Model_Vendor->load($pId);
                if (!$vendor) {
                    throw new BException('Vendor not found');
                }
                $result['form']['vendor'] = $vendor->as_array();
            } else {
                $result['form']['vendor'] = new stdClass;
            }
        } catch (Exception $e) {
            $this->addMessage($e);
        }

        $this->respond($result);
    }
}