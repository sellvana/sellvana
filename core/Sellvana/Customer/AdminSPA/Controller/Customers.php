<?php

/**
 * Class Sellvana_Customer_AdminSPA_Controller_Customers
 *
 * @property Sellvana_Customer_Model_Customer Sellvana_Customer_Model_Customer
 * @property Sellvana_CustomerGroups_Model_Group Sellvana_CustomerGroups_Model_Group
 */
class Sellvana_Customer_AdminSPA_Controller_Customers extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig() 
    {
        $logInAsUrl = $this->BApp->href('customers/start_session?id=');
        $custGroupOptions = $this->Sellvana_CustomerGroups_Model_Group->groupsOptions();
        $custStatusOptions = $this->Sellvana_Customer_Model_Customer->fieldOptions('status');
        $countries = $this->BLocale->getAvailableCountries();

        return [
            static::ID => 'customers',
            static::DATA_URL => 'customers/grid_data',
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT],
                [static::TYPE => 'actions', static::ACTIONS => [
                    [static::TYPE => 'edit', static::LINK => '/customers/form?id={id}'],
                    [static::NAME => 'login', 'template' => "<a :href=\"'{$logInAsUrl}'+row.id\" target=\"AdminCustomer\" :title=\"(('Log in as customer'))|_\"><i class=\"fa fa-user\"></i></a>"],
                    [static::TYPE => 'delete', 'delete_url' => 'customers/grid_delete'],
                ]],
                [static::NAME => 'thumb_url', static::LABEL => (('Thumbnail')), static::DATACELL_TEMPLATE => '<td><img :src="row.thumb_url"></td>', 'sortable' => false],
                [static::NAME => 'id', static::LABEL => (('ID')), 'index' => 'c.id'],
                [static::NAME => 'firstname', static::LABEL => (('First Name')), 'index' => 'c.firstname'],
                [static::NAME => 'lastname', static::LABEL => (('Last Name')), 'index' => 'c.lastname'],
                [static::NAME => 'email', static::LABEL => (('Email')), 'index' => 'c.email'],
                [static::NAME => 'customer_group', static::LABEL => (('Customer Group')), 'index' => 'c.customer_group', static::OPTIONS => $custGroupOptions,
                    'edit' => ['bulk' => true, 'validation' => [static::REQUIRED => true]],
                ],
                [static::NAME => 'status', static::LABEL => (('Status')), 'index' => 'c.status', static::OPTIONS => $custStatusOptions,
                    'edit' => ['bulk' => true, 'validation' => [static::REQUIRED => true]]
                ],
                [static::NAME => 'street1', static::LABEL => (('Address')), 'index' => 'a.street1'],
                [static::NAME => 'city', static::LABEL => (('City')), 'index' => 'a.city', static::HIDDEN => true],
                [static::NAME => 'region', static::LABEL => (('Region')), 'index' => 'a.region', static::HIDDEN => true],
                [static::NAME => 'postcode', static::LABEL => (('Postal Code')), 'index' => 'a.postcode', static::HIDDEN => true],
                [static::NAME => 'country', static::LABEL => (('Country')), 'index' => 'a.country', static::HIDDEN => true, static::OPTIONS => $countries],
                [static::NAME => 'create_at', static::LABEL => (('Created')), 'index' => 'c.create_at', 'format' => 'datetime'],
                [static::NAME => 'last_login', static::LABEL => (('Last Login')), 'index' => 'c.last_login', static::HIDDEN => true, 'format' => 'datetime'],
            ],
            static::FILTERS => [
                [static::NAME => 'firstname'],
                [static::NAME => 'lastname'],
                [static::NAME => 'email'],
                [static::NAME => 'customer_group'],
                [static::NAME => 'street1'],
                [static::NAME => 'city'],
                [static::NAME => 'region'],
                [static::NAME => 'postcode'],
                [static::NAME => 'create_at', static::TYPE => 'date'],
                [static::NAME => 'last_login', static::TYPE => 'date'],
                [static::NAME => 'country'],
                [static::NAME => 'status'],
            ],
            static::BULK_ACTIONS => [
                'edit'   => true,
                'delete' => true
            ]
        ];
    }

    public function getGridOrm()
    {
        return $this->Sellvana_Customer_Model_Customer->orm();
    }

    public function processGridPageData($data)
    {
        foreach ($data['rows'] as $row) {
            $row->set('thumb_url', $this->BUtil->gravatar($row->get('email'), 48));
        }
        return parent::processGridPageData($data);
    }

    public function action_grid_delete__POST()
    {

    }

    public function action_form_data()
    {
        $result = [];
        $pId = $this->BRequest->get('id');
        try {
            $customer = $this->Sellvana_Customer_Model_Customer->load($pId);
            if (!$customer) {
                throw new BException('Customer not found');
            }
            $result[static::FORM][static::CONFIG][static::TABS] = $this->getFormTabs('/customers/form');
            $result[static::FORM][static::CONFIG][static::PAGE_ACTIONS] = $this->getDefaultFormPageActions();
            $result[static::FORM]['customer'] = $customer->as_array();

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
            $this->ok()->addMessage('Customer was saved successfully', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }
    
    public function onHeaderSearch($args)
    {
        $r = $this->BRequest->get();
        if (isset($r['q']) && $r['q'] != '') {
            $value = '%' . $r['q'] . '%';
            $result = $this->Sellvana_Customer_Model_Customer->orm()
                ->where(['OR' => [
                    ['id like ?', (int)$value],
                    ['firstname like ?', (string)$value],
                    ['lastname like ?', (string)$value],
                    ['email like ?', (string)$value],
                ]])->find_one();
            $args['result']['customer'] = null;
            if ($result) {
                $args['result']['customer'] = [
                    'priority' => 10,
                    static::LINK => '/customers/form?id=' . $result->id(),
                ];
            }
        }
    }
}