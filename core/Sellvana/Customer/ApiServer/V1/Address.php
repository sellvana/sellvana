<?php

/**
 * Class Sellvana_Customer_ApiServer_V1_Address
 *
 * @property Sellvana_Customer_Model_Address $Sellvana_Customer_Model_Address
 */
class Sellvana_Customer_ApiServer_V1_Address extends FCom_ApiServer_Controller_Abstract
{
    public function action_index()
    {
        $id = $this->BRequest->param('id');
        $customerId = $this->BRequest->param('customer_id');
        $len = $this->BRequest->get('len');
        if (!$len) {
            $len = 10;
        }
        $start = $this->BRequest->get('start');
        if (!$start) {
            $start = 0;
        }

        if ($id) {
            $customerAddress[] = $this->Sellvana_Customer_Model_Address->load($id);
        } else if ($customerId) {
            $customerAddress = $this->Sellvana_Customer_Model_Address->orm()->where('customer_id', $customerId)
                ->limit($len, $start)->find_many();
        } else {
            $customerAddress = $this->Sellvana_Customer_Model_Address->orm()->limit($len, $start)->find_many();
        }
        if (empty($customerAddress)) {
            $this->ok();
        }
        $result = $this->Sellvana_Customer_Model_Address->prepareApiData($customerAddress);
        $this->ok($result);
    }

    public function action_index__POST()
    {
        $post = $this->BUtil->fromJson($this->BRequest->rawPost());

        if (empty($post['customer_id'])) {
            $this->badRequest("Customer id is required");
        }

        $data = $this->Sellvana_Customer_Model_Address->formatApiPost($post);
        $data['customer_id'] = $post['customer_id'];

        $address = $this->Sellvana_Customer_Model_Address->create($data)->save();

        if (!$address) {
            $this->internalError("Can't create a customer address");
        }

        $this->created(['id' => $address->id]);
    }

    public function action_index__PUT()
    {
        $id = $this->BRequest->param('id');
        $post = $this->BUtil->fromJson($this->BRequest->rawPost());

        if (empty($id)) {
            $this->badRequest("Customer address id is required");
        }

        $data = $this->Sellvana_Customer_Model_Address->formatApiPost($post);

        $address = $this->Sellvana_Customer_Model_Address->load($id);
        if (!$address) {
            $this->notFound("Customer address id #{$id} not found");
        }

        $address->set($data)->save();
        $this->ok();
    }

    public function action_index__DELETE()
    {
        $id = $this->BRequest->param('id');

        if (empty($id)) {
            $this->notFound("Customer address id is required");
        }

        $address = $this->Sellvana_Customer_Model_Address->load($id);
        if (!$address) {
            $this->notFound("Customer address id #{$id} not found");
        }

        $address->delete();
        $this->ok();
    }


}
