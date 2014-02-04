<?php

class FCom_Feedback_Controller extends FCom_Core_Controller_Abstract
{
    public function action_index__POST()
    {
        $r = BRequest::i();
        $result = array();
        try {
            $data = BUtil::arrayMask($r->post('feedback'), 'name,email,comments');
            $data['url'] = $r->referrer();
            if (BConfig::i()->get('modules/FCom_Feedback/send_mod_versions')) {
                foreach (BModuleRegistry::i()->getAllModules() as $modName => $mod) {
                    if ($mod->run_level === 'LOADED') {
                        $data['mod_versions'][$modName] = array(
                            'version' => $mod->version,
                            'channel' => $mod->channel,
                        );
                    }
                }
            }
#
            $response = BUtil::remoteHttp('POST', 'https://www.sellvana.com/api/v1/feedback', BUtil::toJson($data));
            $result = BUtil::fromJson($response);
        } catch (Exception $e) {
            $result['msg'] = $e->getMessage();
            $result['error'] = true;
        }
        if ($r->xhr()) {
            BResponse::i()->json($result);
        } else {
            BResponse::i()->redirect($r->referrer());
        }
    }
}
