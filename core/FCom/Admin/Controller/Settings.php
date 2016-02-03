<?php

class FCom_Admin_Controller_Settings extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'system/settings';

    public function action_index()
    {
        $this->layout('/settings');
        $view = $this->view('settings');
        /** @var FCom_Admin_View_Abstract $view */
        $tabViews = $this->BLayout->findViewsRegex('#^settings/#');
        $tabGroups = [];

        foreach ($tabViews as $tabViewName => $tabView) {
            $tabName = preg_replace('#^settings/#', '', $tabViewName);
            $parts = explode('/', $tabName, 2);
            if (sizeof($parts) === 2) {
                list($group, $tabName) = $parts;
                if (empty($view->tab_groups[$group])) {
                    $groupName = ucwords(str_replace('_', ' ', $group));
                    $view->addTabGroup($group, ['label' => $groupName]);
                }
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
        $model = $this->BConfig;

        $this->BEvents->fire(__METHOD__, ['model' => &$model]);

        $this->processFormTabs($view, $model);
#echo "<pre>"; var_dump($view);echo "</pre>"; exit;
    }

    public function action_index__POST()
    {
        $xhr = $this->BRequest->xhr();
        try {
            $post = $this->BRequest->post();

            $skipDefaultHandler = false;

            $this->BEvents->fire(__METHOD__, ['post' => &$post, 'skip_default_handler' => &$skipDefaultHandler]);

            if (!$skipDefaultHandler) {
                $this->BConfig->add($post['config'], true);

                if (!empty($post['config']['db'])) {
                    try {
                        $this->BDb->connect();
                        //$this->BConfig->writeConfigFiles('db');
                    } catch (Exception $e) {
                        $this->message('Invalid DB configuration, not saved: ' . $e->getMessage(), 'error');
                    }
                }
                $this->BConfig->writeConfigFiles();
            }

            if (!$xhr) {
                $this->message('Settings updated');
            } else {
                $result = ['message' => $this->BLocale->_('Settings has been saved successfully'), 'status' => 'success'];
            }

        } catch (Exception $e) {

            $this->BDebug->logException($e);
            if (!$xhr) {
                $this->message($e->getMessage(), 'error');
            } else {
                $result = ['message' => $this->BLocale->_($e->getMessage()), 'status' => 'error'];
            }
        }
        if (!empty($post['current_tab'])) {
            $tab = $post['current_tab'];
        } else {
            $tab = 'FCom_Admin';
        }
        if (!$xhr) {
            $this->BResponse->redirect('settings' . '?tab=' . $tab);
        } else {
            $this->BResponse->json($result);
        }
    }

    public function indexSettingsLabels()
    {
        $cacheKey = 'settings-index-' . $this->FCom_Admin_Model_User->sessionUserId();
        $cached = $this->BCache->load($cacheKey);
        if ($cached) {
            return $cached;
        }
        $this->layout('/settings');
        $tabViews = $this->BLayout->findViewsRegex('#^settings/#');

        $index = [];
        foreach ($tabViews as $tabViewName => $tabView) {
            $tabName = preg_replace('#^settings/#', '', $tabViewName);
            $parts = explode('/', $tabName, 2);
            if (sizeof($parts) === 2) {
                $tabName = $parts[1];
            }
            $contents = $tabView->render();
            $re = '~(<div\s+class="panel-heading">\s*<a\s+.*\s+href="(.*?)"[^>]*>\s*(.*?)\s*</a>|<label\s+.*\s+for="(.*?)"[^>]*>\s*(.*?)\s*</label>)~m';
            if (preg_match_all($re, $contents, $matches, PREG_SET_ORDER)) {
#echo "<xmp>"; var_dump($matches); echo "</xmp>"; exit;
                $curPanelId = null;
                $curPanelLabel = null;
                foreach ($matches as $m) {
                    if (!empty($m[3])) {
                        $curPanelId = $m[2];
                        $curPanelLabel = strip_tags(str_replace('&nbsp;', '', $m[3]));
                    } elseif (!empty($m[5])) {
                        $fieldId = $m[4];
                        $fieldLabel = strip_tags(str_replace('&nbsp;', '', $m[5]));
                        $index[] = [
                            'tab_id' => '#tab-' . $tabName,
                            'tab_label' => str_replace('_', ' ', $tabName),
                            'panel_id' => $curPanelId,
                            'panel_label' => $curPanelLabel,
                            'field_id' => '#' . $fieldId,
                            'field_label' => $fieldLabel,
                        ];
                    }
                }
            }
        }
        $this->BCache->save($cacheKey, $index);
        return $index;
    }

    public function action_search()
    {
        $q = $this->BRequest->get('q');
        if (!$q) {
            $this->BResponse->json([]);
            return;
        }
        $index = $this->indexSettingsLabels();

        $result = [];
        foreach ($index as $id => $item) {
            $label = $item['tab_label'] . '|' . $item['panel_label'] . '|' . $item['field_label'];
            if (stripos($label, $q) !== false) {
                $result[] = $item;
            }
        }

        $this->BResponse->json($result);
    }

    public function action_dismiss() {
        $code = $this->BRequest->get('code');
        $conf      = $this->BConfig;
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
            $this->BConfig->writeConfigFiles('local');
        }

        $this->BResponse->json("success");
    }

    public function getAllModes()
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
