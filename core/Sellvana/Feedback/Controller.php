<?php

class Sellvana_Feedback_Controller extends FCom_Core_Controller_Abstract
{
    public function action_index__POST()
    {
        $r = $this->BRequest;
        $result = [];
        try {
            $data = $this->BUtil->arrayMask($r->post('feedback'), 'name,email,comments');
            $data['url'] = $r->referrer();
            if ($this->BConfig->get('modules/Sellvana_Feedback/send_mod_versions')) {
                foreach ($this->BModuleRegistry->getAllModules() as $modName => $mod) {
                    if ($mod->run_status === 'LOADED') {
                        $data['mod_versions'][$modName] = [
                            'version' => $mod->version,
                            'channel' => $mod->channel,
                        ];
                    }
                }
            }
            $response = $this->BUtil->remoteHttp('POST', 'https://www.sellvana.com/api/v1/feedback', $this->BUtil->toJson($data));
            $result = $this->BUtil->fromJson($response);
            if (!$result) {
                $info = $this->BUtil->lastRemoteHttpInfo();
//echo '<pre>'; var_dump($info); exit;
                throw new Exception('Server error (' . $info['headers']['status'] . ')');
            }
        } catch (Exception $e) {
            $result['msg'] = $this->_((('Sending Feedback: ')), $e->getMessage());
            $result['error'] = true;
        }
        if ($r->xhr()) {
            $this->BResponse->json($result);
        } else {
            $status = !empty($result['error']) ? 'error' : 'success';
            $tag = $this->BRequest->area() === 'FCom_Admin' ? 'admin' : 'frontend';
            $this->BSession->addMessage($result['msg'], $status, $tag);
            $this->BResponse->redirect($r->referrer());
        }
    }
}
