<?php

class FCom_Admin_Controller_Settings extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'system/settings';

    public function action_index()
    {
        $view = $this->view('settings');
        $tabViews = BLayout::i()->findViewsRegex('#^settings/#');
        $tabGroups = [];

        foreach ($tabViews as $tabViewName => $tabView) {
            $tabName = preg_replace('#^settings/#', '', $tabViewName);
            $parts = explode('/', $tabName, 2);
            if (sizeof($parts) === 2) {
                list($group, $tabName) = $parts;
            } else {
                $group = null;
            }
            if (empty($view->tabs[$tabName])) {
                $view->addTab($tabName, [
                    'async' => true,
                    'label' => str_replace('_', ' ', $tabName),
                    'view'  => $tabViewName,
                    'group' => $group,
                ]);
            }
        }
        $this->layout('/settings');
        $this->processFormTabs($view, BConfig::i());
#echo "<pre>"; var_dump($view);echo "</pre>"; exit;
    }

    public function action_index__POST()
    {
        $xhr = BRequest::i()->xhr();
        try {
            $post = BRequest::i()->post();

            BEvents::i()->fire(__METHOD__, ['post' => &$post]);
            BConfig::i()->add($post['config'], true);

            if (!empty($post['config']['db'])) {
                try {
                    BDb::connect();
                    //FCom_Core_Main::i()->writeConfigFiles('db');
                } catch (Exception $e) {
                    $this->message('Invalid DB configuration, not saved: ' . $e->getMessage(), 'error');
                }
            }
            FCom_Core_Main::i()->writeConfigFiles();

            if (!$xhr) {
                $this->message('Settings updated');
            } else {
                $result = ['message' => BLocale::_('Settings has been saved successfully'), 'status' => 'success'];
            }

        } catch (Exception $e) {

            BDebug::logException($e);
            if (!$xhr) {
                $this->message($e->getMessage(), 'error');
            } else {
                $result = ['message' => BLocale::_($e->getMessage()), 'status' => 'error'];
            }
        }
        if (!empty($post['current_tab'])) {
            $tab = $post['current_tab'];
        } else {
            $tab = 'FCom_Admin';
        }
        if (!$xhr) {
            BResponse::i()->redirect('settings' . '?tab=' . $tab);
        } else {
            BResponse::i()->json($result);
        }
    }

    public function action_dismiss() {
        $code = BRequest::i()->get('code');
        $conf      = BConfig::i();
        $dismissed = $conf->get('modules/FCom_Core/dismissed/notifications');
        $dirty = false;
        if (!$dismissed) {
            $dismissed = [$code];
            $dirty = true;
        } elseif (!in_array($code, $dismissed)) {
            $dismissed[] = $code;
            $dirty = true;
        }
        if ($dirty) {
            $conf->set('modules/FCom_Core/dismissed/notifications', $dismissed, false, true);
            FCom_Core_Main::i()->writeConfigFiles('local');
        }

        BResponse::i()->json("success");
    }

    public function getAllMode()
    {
        return [
          BDebug::MODE_DEBUG => BDebug::MODE_DEBUG,
          BDebug::MODE_DEVELOPMENT => BDebug::MODE_DEVELOPMENT,
          BDebug::MODE_STAGING => BDebug::MODE_STAGING,
          BDebug::MODE_PRODUCTION => BDebug::MODE_PRODUCTION,
          BDebug::MODE_RECOVERY => BDebug::MODE_RECOVERY,
          BDebug::MODE_DISABLED => BDebug::MODE_DISABLED
        ];
    }
}
