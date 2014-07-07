<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PushServer_Controller
 * @property FCom_PushServer_Model_Client FCom_PushServer_Model_Client
 */
class FCom_PushServer_Controller extends FCom_Core_Controller_Abstract
{
    public function action_index__POST()
    {
        $this->BResponse->nocache()->startLongResponse(false);

        $client = $this->FCom_PushServer_Model_Client->sessionClient();

        $request = $this->BRequest->post();

        $client->processRequest($request)->checkIn()->waitForMessages()->checkOut();

        $result = [
            'conn_id' => $request['conn_id'],
            'messages' => $client->getMessages(),
        ];

        $this->BResponse->json($result);
    }
}
