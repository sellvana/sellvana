<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_FrontendCP_Frontend_Controller
 *
 * @property FCom_FrontendCP_Main $FCom_FrontendCP_Main
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Core_Main $FCom_Core_Main
 */
class FCom_FrontendCP_Frontend_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_upload__POST()
    {
        if (!$this->FCom_Admin_Model_User->sessionUser()->getPermission('frontendcp/edit')) {
            $this->BResponse->status(403);
        }
        $result = $this->BRequest->receiveFiles('image', $this->BConfig->get('fs/media_dir') . '/tmp');
        $imgUrl = $this->BConfig->get('web/media_dir') . '/tmp/' . $result['image']['name'];
        $imgUrl = $this->FCom_Core_Main->resizeUrl($imgUrl);
        $this->BResponse->json(['image' => ['url' => $imgUrl]]);
    }

    public function action_update__PUT()
    {
        if (!$this->FCom_Admin_Model_User->sessionUser()->getPermission('frontendcp/edit')) {
            $this->BResponse->status(403);
        }
        $request = $this->BRequest->json();

        $result = [];
        try {
            if (empty($request['content'])) {
                throw new Exception('Missing content');
            }
            $handlers = $this->FCom_FrontendCP_Main->getEntityHandlers();

            foreach ($request['content'] as $id => $params) {
                if (empty($params['data']['entity']) || empty($handlers[$params['data']['entity']])) {
                    $result['content'][$id] = ['error' => 'Missing or invalid entity'];
                    continue;
                }
                $handler = $handlers[$params['data']['entity']];
                if (is_callable($handler)) {
                    $params['id'] = $id;
                    $result['content'][$id] = call_user_func($handler, $params);
                }
            }
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $this->BEvents->fire(__METHOD__ . ':after', ['request' => $request, 'result' => $result]);

        $this->BResponse->json($result);
    }
}
