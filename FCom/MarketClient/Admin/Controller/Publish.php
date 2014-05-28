<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_MarketClient_Admin_Controller_Publish extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'market_client/publish';

    public function action_index()
    {
        try {
            $result = FCom_MarketClient_RemoteApi::i()->getModulesVersions(true);
            foreach ($result as $modName => $modInfo) {
                if ($modInfo['status'] !== 'mine' && $modInfo['status'] !== 'available') {
                    unset($result[$modName]);
                    continue;
                }
                if (!empty($modInfo['edit_href'])) {
                    $result[$modName]['edit_href'] = BUtil::setUrlQuery(BApp::href('marketclient/site/connect'), [
                        'redirect_to' => $modInfo['edit_href'],
                    ]);
                }

            }
            if ($result) {
                uasort($result, function($a, $b) {
                    $a1 = !empty($a['can_update']);
                    $b1 = !empty($b['can_update']);
                    if ($a1 && !$b1) return -1;
                    if ($b1 && !$a1) return 1;

                    $a2 = !empty($a['status']) && $a['status'] === 'available';
                    $b2 = !empty($b['status']) && $b['status'] === 'available';
                    if ($a2 && !$b2) return -1;
                    if ($b2 && !$a2) return 1;

                    return strcmp($a['name'], $b['name']);
                });
            } else {
                $this->message('No modules are available for publishing', 'warning');
            }

            $view = $this->view('marketclient/publish');
            if (!empty($result['error'])) {
                $this->message($result['message'], 'error');
            } else {
                $view->set('modules', $result);
            }
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
        }
        $this->layout('/marketclient/publish');
    }

    public function action_module()
    {
        $modName = BRequest::i()->get('mod_name');
        $mod = BModuleRegistry::i()->module($modName);
        if (!$mod) {
            $this->forward(false);
            return;
        }
        $this->view('marketclient/publish/module')->set('mod_name', $mod);
        $this->layout('/marketclient/publish/module');
    }

    public function action_module__POST()
    {
        BResponse::i()->startLongResponse(false);
        $hlp = FCom_MarketClient_RemoteApi::i();
        $connResult = $hlp->setupConnection();

        list($action, $modName) = explode('/', BRequest::i()->post('mod_name')) + [''];
        $versionResult = $hlp->getModulesVersions($modName);

        #$redirectUrl = $hlp->getUrl('market/module/edit', array('mod_name' => $modName));
        $redirectUrl = BRequest::i()->referrer();
        #var_dump($modName, $versionResult); exit;
        if (!empty($versionResult[$modName]) && $versionResult[$modName]['status'] === 'available') {
            $createResult = $hlp->createModule($modName);
            if (!empty($createResult['error'])) {
                $this->message($createResult['error'], 'error');
                BResponse::i()->redirect('marketclient/publish');
                return;
            }
            if (!empty($createResult['redirect_url'])) {
                $redirectUrl = $createResult['redirect_url'];
            }
        }
        $uploadResult = $hlp->uploadPackage($modName);
        $this->message($uploadResult['message'], !empty($uploadResult['error']) ? 'error' : 'success', 'admin');

#echo "<pre>"; var_dump($uploadResult); exit;
        // TODO: why $this->message() doesn't work here?
        BResponse::i()->redirect($redirectUrl);
    }
}
