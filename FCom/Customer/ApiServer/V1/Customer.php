<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Customer_ApiServer_V1_Customer extends FCom_ApiServer_Controller_Abstract
{
    public function action_index()
    {
        $id = $this->BRequest->param('id');
        $len = $this->BRequest->get('len');
        if (!$len) {
            $len = 10;
        }
        $start = $this->BRequest->get('start');
        if (!$start) {
            $start = 0;
        }

        if ($id) {
            $customers[] = $this->FCom_Customer_Model_Customer->load($id);
        } else {
            $customers = $this->FCom_Customer_Model_Customer->orm()->limit($len, $start)->find_many();
        }
        if (empty($customers)) {
            $this->ok();
        }
        $result = $this->FCom_Customer_Model_Customer->prepareApiData($customers);
        $this->ok($result);
    }

    public function action_index__POST()
    {
        $post = $this->BUtil->fromJson($this->BRequest->rawPost());

        if (empty($post['email'])) {
            $this->badRequest("Email is required");
        }
        if (empty($post['password'])) {
            $this->badRequest("Password is required");
        }
        if (empty($post['firstname'])) {
            $this->badRequest("Firstname is required");
        }
        if (empty($post['lastname'])) {
            $this->badRequest("Lastname is required");
        }

        $data = $this->FCom_Customer_Model_Customer->formatApiPost($post);

        $customer = $this->FCom_Customer_Model_Customer->orm()->create($data)->save();

        if (!$customer) {
            $this->internalError("Can't create a customer");
        }

        $this->created(['id' => $customer->id]);
    }

    public function action_index__PUT()
    {
        $id = $this->BRequest->param('id');
        $post = $this->BUtil->fromJson($this->BRequest->rawPost());

        if (empty($id)) {
            $this->badRequest("Customer id is required");
        }

        $data = $this->FCom_Customer_Model_Customer->formatApiPost($post);

        $customer = $this->FCom_Customer_Model_Customer->load($id);
        if (!$customer) {
            $this->notFound("Customer id #{$id} not found");
        }

        $customer->set($data)->save();
        $this->ok();
    }

    public function action_index__DELETE()
    {
        $id = $this->BRequest->param('id');

        if (empty($id)) {
            $this->notFound("Customer id is required");
        }

        $customer = $this->FCom_Customer_Model_Customer->load($id);
        if (!$customer) {
            $this->notFound("Customer id #{$id} not found");
        }

        $customer->delete();
        $this->ok();
    }


}
