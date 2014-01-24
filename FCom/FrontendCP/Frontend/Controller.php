<?php

class FCom_FrontendCP_Frontend_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_upload()
    {
        if (!FCom_Admin_Model_User::i()->sessionUser()->getPermission('frontendcp/edit')) {
            BResponse::i()->status(403);
        }
        $result = BRequest::i()->receiveFiles('image', BConfig::i()->get('fs/media_dir').'/tmp');
        $imgUrl = BConfig::i()->get('web/media_dir').'/tmp/'.$result['image']['name'];
        $imgUrl = FCom_Core_Main::i()->resizeUrl().'?f='.urlencode(ltrim($imgUrl, '/'));
        BResponse::i()->json(array('image'=>array('url'=>$imgUrl)));
    }

    public function action_update()
    {
        if (!FCom_Admin_Model_User::i()->sessionUser()->getPermission('frontendcp/edit')) {
            BResponse::i()->status(403);
        }
        $request = BRequest::i()->json();

        $result = array();
        try {
            if (empty($request['content'])) {
                throw new Exception('Missing content');
            }
            $handlers = FCom_FrontendCP_Main::i()->getEntityHandlers();

            foreach ($request['content'] as $id => $params) {
                if (empty($params['data']['entity']) || empty($handlers[$params['data']['entity']])) {
                    $result['content'][$id] = array('error' => 'Missing or invalid entity');
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

        BEvents::i()->fire(__METHOD__.'.after', array('request' => $request, 'result' => $result));

        BResponse::i()->json($result);
    }
}
