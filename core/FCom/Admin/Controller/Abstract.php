<?php

/**
 * Class FCom_Admin_Controller_Abstract
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Core_Main $FCom_Core_Main
 * @property FCom_Core_Model_Config $FCom_Core_Model_Config
 * @property FCom_Core_Model_MediaLibrary $FCom_Core_Model_MediaLibrary
 * @property FCom_Core_Model_Module $FCom_Core_Model_Module
 */
class FCom_Admin_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    protected static $_origClass;
    protected $_permission;

    /**
     * @param array $args
     * @return bool
     */
    public function authenticate($args = [])
    {
        return $this->FCom_Admin_Model_User->isLoggedIn();
    }

    /**
     * @param array $args
     * @return bool
     */
    public function authorize($args = [])
    {
        if (!parent::authorize($args)) {
            return false;
        }
        if (!empty($this->_permission)) {
            $user = $this->FCom_Admin_Model_User->sessionUser();
            if (!$user) {
                return false;
            }
            return $user->getPermission($this->_permission);
        }
        return true;
    }

    public function action_unauthenticated()
    {
        $r = $this->BRequest;
        if ($r->xhr()) {
            $this->BSession->set('admin_login_orig_url', $r->referrer());
            $loginForm = $this->BLayout->getView('login')->render();
            $this->BResponse->json([
                'error' => 'login',
                'form' => $this->BLayout->getView('login/modal_form')->set('loginForm', $loginForm)->render()
            ]);
        } else {
            $this->BSession->set('admin_login_orig_url', $r->currentUrl());
            $this->layout('/login');
            $this->BResponse->status(401, 'Unauthorized'); // HTTP sic
        }
    }

    public function action_unauthorized()
    {
        $r = $this->BRequest;
        if ($r->xhr()) {
            $this->BSession->set('admin_login_orig_url', $r->referrer());
            $this->BResponse->json(['error' => 'denied']);
        } else {
            $this->BSession->set('admin_login_orig_url', $r->currentUrl());
            $this->layout('/denied');
            $this->BResponse->status(403, 'Forbidden');
        }
    }

    /**
     * @return bool
     */
    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) return false;

        $this->view('head')->addTitle($this->_('%s Admin', $this->BConfig->get('modules/FCom_Core/site_title')));

        return true;
    }

    /**
     * @param $view
     * @param null $model
     * @param string $mode
     * @param null $allowed
     * @return $this
     */
    public function processFormTabs($view, $model = null, $mode = 'edit', $allowed = null)
    {
        $r = $this->BRequest;
        if ($r->xhr() && !is_null($r->get('tabs'))) {
            $this->outFormTabsJson($view, $model, $mode);
        } else {
            $this->initFormTabs($view, $model, $mode, $allowed);
        }
        return $this;
    }

    /**
     * @param $msg
     * @param string $type
     * @param string $tag
     * @param array $options
     * @return $this
     */
    public function message($msg, $type = 'success', $tag = 'admin', $options = [])
    {
        if (is_array($msg)) {
            array_walk($msg, [$this->BLocale, 'translate']);
        } else {
            $msg = $this->_($msg);
        }
        $this->BSession->addMessage($msg, $type, $tag, $options);
        return $this;
    }

    /**
     * @param $view
     * @param $model
     * @param string $mode
     * @param null $allowed
     * @return $this
     */
    public function initFormTabs($view, $model, $mode = 'view', $allowed = null)
    {

        $r = $this->BRequest;
        $layout = $this->BLayout;
        $curTab = $r->request('tab');
        if (is_string($allowed)) {
            $allowed = explode(',', $allowed);
        }
        #$formId = $this->get('form_id');
        #$validator = $this->validator($formId, $model);
        $this->collectFormTabs($view);

        $tabs = $view->tab_groups ? $view->tabs : $view->sortedTabs();
        if ($tabs) {
            foreach ($tabs as $k => &$tab) {
                if (!is_null($allowed) && $allowed !== 'ALL' && !in_array($k, $allowed)) {
                    $tab['disabled'] = true;
                    continue;
                }
                if ($k === $curTab) {
                    $tab['active'] = true;
                    $tab['async'] = false;
                }
                if (!empty($tab['view'])) {
                    $tabView = $layout->getView($tab['view']);
                    if ($tabView) {
                        $tabView->set([
                            'model' => $model,
                            #'validator' => $validator,
                            'mode' => $mode,
                        ]);
                    } else {
                        $this->BDebug->warning('MISSING VIEW: ' . $tab['view']);
                    }
                }
            }
            unset($tab);
        }
        $view->tabs = $tabs;

        if ($view->tab_groups) {
            $tabGroups = $view->sortedTabGroups();
            foreach ($tabs as $k => &$tab) {
                $tabGroups[$tab['group']]['tabs'][$k] =& $tab;
                if (!empty($tab['active'])) {
                    $tabGroups[$tab['group']]['open'] = true;
                }
            }
            unset($tab);
            foreach ($tabGroups as $k => &$tabGroup) {
                if (empty($tabGroup['tabs'])) {
                    unset($tabGroups[$k]);
                } else {
                    uasort($tabGroup['tabs'], function($a, $b) {
                        return $a['pos'] < $b['pos'] ? -1 : ($a['pos'] > $b['pos'] ? 1 : 0);
                    });
                    if (!$curTab) {
                        foreach ($tabGroup['tabs'] as $tabId => &$tab) {
                            $curTab = $tabId;
                            $tabGroup['open'] = true;
                            $tab['active'] = true;
                            $tab['async'] = false;
                            break;
                        }
                        unset($tab);
                    }
                }
            }
            unset($tabGroup);
            $view->tab_groups = $tabGroups;
        } else {
            if (!$curTab) {
                $tabs = $view->tabs;
                foreach ($tabs as $k => &$tab) {
                    $curTab = $k;
                    $tab['active'] = true;
                    $tab['async'] = false;
                    break;
                }
                unset($tab);
                $view->tabs = $tabs;
            }
        }

        $view->set([
            'tabs' => $tabs,
            'model' => $model,
            'mode' => $mode,
            'cur_tab' => $curTab,
        ]);
        return $this;
    }

    /**
     * @param $formView
     * @return $this
     */
    public function collectFormTabs($formView)
    {
        $views = $this->BLayout->findViewsRegex('#^' . $formView->get('tab_view_prefix') . '#');
        foreach ($views as $viewName => $view) {
            $id = basename($viewName);
            if (!empty($formView->tabs[$id])) {
                continue;
            }
            $view->collectMetaData();
            $params = $view->getParam('meta_data');
            if (!empty($params['disabled'])) {
                continue;
            }
            /*if (!empty($params['model_new_hide'])) {
                $model = $formView->get('model');
                if (!$model || !$model->id()) {
                    continue;
                }
            }*/
            $formView->addTab($id, $params);
        }
        return $this;
    }

    /**
     * @param $view
     * @param $model
     * @param string $defMode
     */
    public function outFormTabsJson($view, $model, $defMode = 'view')
    {
        $r = $this->BRequest;
        $mode = $r->request('mode');
        if (!$mode) {
            $mode = $defMode;
        }
        $outTabs = $r->request('tabs');
        if ($outTabs && $outTabs !== 'ALL' && is_string($outTabs)) {
            $outTabs = explode(',', $outTabs);
        }

        $this->collectFormTabs($view);

        $out = [];
        if ($outTabs) {
            $layout = $this->BLayout;
            $tabs = $view->tabs;
            $this->BLayout->applyLayout('settings-tabs');
            foreach ($tabs as $k => $tab) {
                if ($outTabs !== 'ALL' && !in_array($k, $outTabs)) {
                    continue;
                }
                $view = $layout->getView($tab['view']);
                if (!$view) {
                    $this->BDebug->error('MISSING VIEW: ' . $tabs[$k]['view']);
                    continue;
                }
                $out['tabs'][$k] = (string)$view->set([
                    'model' => $model,
                    'mode' => $mode,
                ]);
            }
        }
        $out['messages'] = $this->BSession->messages('admin');
        $this->BResponse->json($out);
        die;
    }

    /**
     * @param $class
     * @param array $defData
     * @throws BException
     */
    protected function _processGridDataPost($class, $defData = [])
    {
        $r = $this->BRequest;
        $id = $r->post('id');
        $data = $defData + $r->post();
        $hlp = $this->{$class};
        /** @type BModel $hlp */
        unset($data['id'], $data['oper']);

        $args = ['data' => &$data, 'oper' => $r->post('oper'), 'helper' => $hlp];
        $this->gridPostBefore($args);

        switch ($args['oper']) {
        case 'add':
            //fix Undefined variable: set
            $model = $args['model'] = $hlp->create($data)->save();
            $result = $model->as_array();
            break;

        case 'edit':
            $model = $hlp->load($id);
            if ($model) {
                $args['model'] = $model->set($data)->save();
                $result = $model->as_array();
            } else {
                $result = ['error' => true];
            }
            break;

        case 'del':
            $model = $hlp->load($id);
            if ($model) {
                $args['model'] = $model->delete();
                $result = ['success' => true];
            } else {
                $result = ['error' => true];
            }
            break;

        case 'mass-delete':
            $args['ids'] = $this->BUtil->arrayCleanInt($id);
            foreach ($args['ids'] as $id) {
                $model = $hlp->load($id);
                if ($model) {
                    $model->delete();
                }
            }
            $result = ['success' => true];
            break;

        case 'mass-edit':
            $args['ids'] = $this->BUtil->arrayCleanInt($id);
            foreach ($args['ids'] as $id) {
                $model = $hlp->load($id);
                unset($data['_new']);
                //if (isset($data['_new'])) {
                if (!$model) {
                    $args['models'][] = $hlp->create($data)->save();
                } else {
                    $args['models'][] = $model->set($data)->save();
                }
            }
            $result = ['success' => true];
            break;
        }

        $args['result'] =& $result;
        $this->gridPostAfter($args);

        //$this->BResponse->redirect('fieldsets/grid_data');
        $this->BResponse->json($result);
        die;
    }

    /**
     * @param $args
     */
    public function gridPostBefore($args)
    {
        $this->BEvents->fire(static::$_origClass . '::gridPostBefore', $args);
    }

    /**
     * @param $args
     */
    public function gridPostAfter($args)
    {
        $this->BEvents->fire(static::$_origClass . '::gridPostAfter', $args);
    }
}
