<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MarketClient_Admin_Controller_Publish
 *
 * @property Sellvana_MarketClient_RemoteApi $Sellvana_MarketClient_RemoteApi
 */
class Sellvana_MarketClient_Admin_Controller_Publish extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'market_client/publish';

    public function action_index()
    {
        try {
            $result = $this->Sellvana_MarketClient_RemoteApi->getModulesVersions(true);
            foreach ($result as $modName => $modInfo) {
                if ($modInfo['status'] !== 'mine' && $modInfo['status'] !== 'available') {
                    unset($result[$modName]);
                    continue;
                }
                if (!empty($modInfo['edit_href'])) {
                    $result[$modName]['edit_href'] = $this->BUtil->setUrlQuery($this->BApp->href('marketclient/site/connect'), [
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

            $this->layout('/marketclient/publish');
            $view = $this->view('marketclient/publish');
            if (!empty($result['error'])) {
                $this->message($result['message'], 'error');
            } else {
                $view->set('modules', $result);
            }
        } catch (Exception $e) {
            $this->layout('/marketclient/publish');
            $this->message($e->getMessage(), 'error');
        }
    }

    public function action_module()
    {
        $modName = $this->BRequest->get('mod_name');
        $mod = $this->BModuleRegistry->module($modName);
        if (!$mod) {
            $this->forward(false);
            return;
        }
        $this->layout('/marketclient/publish/module');
        $this->view('marketclient/publish/module')->set('mod_name', $mod);
    }

    public function action_module__POST()
    {
        $this->BResponse->startLongResponse(false);
        $hlp = $this->Sellvana_MarketClient_RemoteApi;
        $connResult = $hlp->setupConnection();

        list($action, $modName) = explode('/', $this->BRequest->post('mod_name')) + [''];
        $versionResult = $hlp->getModulesVersions($modName);

        #$redirectUrl = $hlp->getUrl('market/module/edit', array('mod_name' => $modName));
        $redirectUrl = $this->BRequest->referrer();
        #var_dump($modName, $versionResult); exit;
        if (!empty($versionResult[$modName]) && $versionResult[$modName]['status'] === 'available') {
            $createResult = $hlp->createModule($modName);
            if (!empty($createResult['error'])) {
                $this->message($createResult['error'], 'error');
                $this->BResponse->redirect('marketclient/publish');
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
        $this->BResponse->redirect($redirectUrl);
    }
}
