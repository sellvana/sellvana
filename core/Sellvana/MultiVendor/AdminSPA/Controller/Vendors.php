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
            static::DATA_URL => 'vendors/grid_data',
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT],
                [static::NAME => 'id', static::LABEL => (('ID')), 'index' => 'v.id'],
                [static::NAME => 'vendor_name', static::LABEL => (('Vendor Name')), 'index' => 'v.vendor_name'],
                [static::NAME => 'notify_type', static::LABEL => (('Notification')), static::OPTIONS => $notifyTypeOptions],
                [static::NAME => 'email_notify', static::LABEL => (('Email for Notification')), 'index' => 'v.email_notify'],
                [static::NAME => 'email_support', static::LABEL => (('Email for Support')), 'index' => 'v.email_support'],
                [static::NAME => 'create_at', static::LABEL => (('Created')), 'index' => 'v.create_at', 'format' => 'date'],
                [static::NAME => 'update_at', static::LABEL => (('Updated')), 'index' => 'v.update_at', 'format' => 'date'],
            ],
            static::FILTERS => [
                [static::NAME => 'id', static::TYPE => 'number'],
                [static::NAME => 'vendor_name', static::TYPE => 'text'],
                [static::NAME => 'notify_type', static::TYPE => 'select'],
                [static::NAME => 'email_notify', static::TYPE => 'text'],
                [static::NAME => 'email_support', static::TYPE => 'text'],
                [static::NAME => 'create_at', static::TYPE => 'date'],
                [static::NAME => 'update_at', static::TYPE => 'date'],
            ],
            static::BULK_ACTIONS => [
                [static::TYPE => 'delete'],
            ],
            static::PAGER => true,
            static::EXPORT => true,
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
            $result[static::FORM][static::TABS] = $this->getFormTabs('/vendors/form');
            if ($pId) {
                $vendor = $this->Sellvana_MultiVendor_Model_Vendor->load($pId);
                if (!$vendor) {
                    throw new BException('Vendor not found');
                }
                $result[static::FORM]['vendor'] = $vendor->as_array();
            } else {
                $result[static::FORM]['vendor'] = new stdClass;
            }
        } catch (Exception $e) {
            $this->addMessage($e);
        }

        $this->respond($result);
    }
}