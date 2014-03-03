<?php

class FCom_Admin_Controller_Settings extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'system/settings';

    public function action_index()
    {
        $view = $this->view('settings');
        $tabViews = BLayout::i()->findViewsRegex('#^settings/#');
        $tabGroups = array();

        foreach ($tabViews as $tabViewName=>$tabView) {
            $tabName = preg_replace('#^settings/#', '', $tabViewName);
            if (empty($view->tabs[$tabName])) {
                $view->addTab($tabName, array('async'=>true, 'label'=>str_replace('_', ' ', $tabName), 'view'=>$tabViewName));
            }
        }
        $this->layout('/settings');
        $this->processFormTabs($view, BConfig::i());
    }

    public function action_index__POST()
    {
        $xhr = BRequest::i()->xhr();

        try {
            $post = BRequest::i()->post();

            BEvents::i()->fire(__METHOD__, array('post'=>&$post));
            BConfig::i()->add($post['config'], true);

            if (!empty($post['config']['db'])) {
                try {
                    BDb::connect();
                    //FCom_Core_Main::i()->writeConfigFiles('db');
                } catch (Exception $e) {
                    $this->message('Invalid DB configuration, not saved: '.$e->getMessage(), 'error');
                }
            }
            FCom_Core_Main::i()->writeConfigFiles();
            $message = 'Settings have been saved successfully';
            $status = 'success';
        } catch (Exception $e) {

            BDebug::logException($e);
            $message = $e->getMessage();
            $status = 'error';
        }

        if (!$xhr) {
            $this->message($message, $status);
            if ( !empty( $post[ 'current_tab' ] ) ) {
                $tab = $post[ 'current_tab' ];
            } else {
                $tab = 'FCom_Admin';
            }
            BResponse::i()->redirect('settings'.'?tab='.$tab);
        } else {
            $result = array('message' => BLocale::_($message), 'status' => $status);
            BResponse::i()->json($result);
        }
    }

    public function action_dismiss() {
        $code = BRequest::i()->get('code');
        $conf      = BConfig::i();
        $dismissed = $conf->get('modules/FCom_Core/dismissed/notifications');
        $dirty = false;
        if(!$dismissed){
            $dismissed = array($code);
            $dirty = true;
        } elseif(!in_array($code, $dismissed)) {
            $dismissed[] = $code;
            $dirty = true;
        }
        if ($dirty) {
            $conf->set('modules/FCom_Core/dismissed/notifications', $dismissed, false, true);
            FCom_Core_Main::i()->writeConfigFiles('local');
        }

        BResponse::i()->json("success");
    }
}
