<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Cms_Frontend_Controller
 *
 * @property Sellvana_Cms_Model_Block         $Sellvana_Cms_Model_Block
 * @property Sellvana_Cms_Model_Nav           $Sellvana_Cms_Model_Nav
 * @property Sellvana_Cms_Frontend_View_Block $Sellvana_Cms_Frontend_View_Block
 * @property FCom_Core_LayoutEditor           $FCom_Core_LayoutEditor
 * @property Sellvana_Cms_Model_FormData      $Sellvana_Cms_Model_FormData
 */
class Sellvana_Cms_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_page()
    {
        $pageUrl = $this->BRequest->param('page');
        if (!($pageUrl === '' || is_null($pageUrl))) {
            $block = $this->Sellvana_Cms_Model_Block->loadWhere(['page_enabled' => 1, 'page_url' => (string) $pageUrl]);
        } else {
            $pageHandle = $this->BRequest->param('block');
            if (!($pageHandle === '' || is_null($pageHandle))) {
                $block = $this->Sellvana_Cms_Model_Block->load($pageHandle, 'handle');
            }
        }
        /** @var Sellvana_Cms_Model_Block $block */
        if (empty($block) || !$block->validateBlock()) {
            $this->forward(false);

            return;
        }

        $this->layout('cms_page');

        $view     = $this->Sellvana_Cms_Frontend_View_Block->createView($block);
        $viewName = $view->param('view_name');
        $this->BLayout->hookView('main', $viewName);

        if (($root = $this->BLayout->view('root'))) {
            $root->addBodyClass('cms-' . $block->handle)
                 ->addBodyClass('page-' . $block->handle);
        }

        if (($head = $this->BLayout->view('head'))) {
            /** @var BViewHead $head */
            $head->addTitle($block->page_title);
            foreach (['title', 'description', 'keywords'] as $f) {
                if (($v = $block->get('meta_' . $f))) {
                    $head->meta($f, $v);
                }
            }
        }

        $layoutData = $block->getData('layout');
        if ($layoutData) {
            $context      = ['type' => 'cms_page', 'main_view' => $viewName];
            $layoutUpdate = $this->FCom_Core_LayoutEditor->compileLayout($layoutData, $context);
            if ($layoutUpdate) {
                $this->BLayout->addLayout('cms_page', $layoutUpdate)->applyLayout('cms_page');
            }
        }
    }

    public function action_page__POST()
    {
        $pageUrl = $this->BRequest->param('page');
        try {
            if (!($pageUrl === '' || is_null($pageUrl))) {
                $block = $this->Sellvana_Cms_Model_Block->loadWhere([
                    'page_enabled' => 1,
                    'page_url'     => (string) $pageUrl
                ]);
            }
            if (empty($block) || !$block->validateBlock()) {
                $this->forward(false);

                return;
            }
            // todo save form data to fcom_cms_form_data ?
            // send email
            $this->processForms($block);
        } catch(Exception $e) {
            $this->BDebug->logException($e);
        }
        $this->BResponse->redirect($pageUrl);
    }

    public function action_nav()
    {
        $handle = $this->BRequest->param('nav');
        /** @var Sellvana_Cms_Model_Nav $nav */
        $nav = $this->Sellvana_Cms_Model_Nav->load($handle, 'url_path');
        if (!$nav || !$nav->validateNav()) {
            $this->forward(false);

            return;
        }

        $this->layout('cms_nav');

        $this->BLayout->view('cms/nav-content')->set('nav', $nav);

        if (($root = $this->BLayout->view('root'))) {
            $htmlClass = $this->BUtil->simplifyString($nav->url_path);
            $root->addBodyClass('cms-' . $htmlClass)
                 ->addBodyClass('page-' . $htmlClass);
        }

        if (($head = $this->BLayout->view('head'))) {
            $head->addTitle($nav->title);
            foreach (['title', 'description', 'keywords'] as $f) {
                if (($v = $nav->get('meta_' . $f))) {
                    $head->meta($f, $v);
                }
            }
        }

        if ($nav->layout_update) {
            $layoutUpdate = $this->BYAML->parse($nav->layout_update);
            if (!is_null($layoutUpdate)) {
                $this->BLayout->addLayout('cms_nav', $layoutUpdate)->applyLayout('cms_nav');
            } else {
                $this->BDebug->warning('Invalid layout update for CMS nav node');
            }
        }
    }

    /**
     * @param Sellvana_Cms_Model_Block $block
     */
    public function processForms($block)
    {
        if (!$block->get('form_enable')) {
            return;
        }
        $formFields = $this->BUtil->fromJson($block->get('form_fields'));
        $fieldData  = $this->BRequest->post('form');
        /** @var Sellvana_Customer_Model_Customer $customer */
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        $ip       = $this->BRequest->ip();

        $date = $this->BDb->now();
        $data = [
            'handle' => $block->get('handle'),
            'date'   => $date,
            'ip'     => $ip,
        ];
        if ($customer) {
            $data['customer']    = $customer->get('email');
            $data['customer_id'] = $customer->id();
        } else {
            $data['customer_id'] = null;
        }

        foreach ($formFields as $f) {
            $name = $f['name'];
            if (empty($fieldData[$name])) {
                continue;
            }
            $idx        = !empty($f['label'])? $f['label']: $name;
            $data[$idx] = $fieldData[$name];
        }

        $this->persistFormData($block, $data);
        $this->formSendEmail($block, $data);
        $this->formNotifyAdmin($block, $data);
        $this->formNotifyCustomer($block, $data);
    }

    /**
     * @param Sellvana_Cms_Model_Block $block
     * @param array                    $data
     */
    private function persistFormData($block, $data)
    {
        $sessId = $this->BSession->sessionId();
        $email  = $block->get('form_custom_email');
        if (!$email) {
            $email = $this->BConfig->get('modules/FCom_Core/' . $block->get('form_email'));
        }

        $model = $this->Sellvana_Cms_Model_FormData->create();
        $model->set('form_id', $block->id())
              ->set('customer_id', $data['customer_id'])
              ->set('session_id', $sessId)
              ->set('remote_ip', $data['ip'])
              ->set('email', $email)
              ->setData($data);
        $model->save();
    }

    /**
     * @param Sellvana_Cms_Model_Block $block
     * @param array                    $data
     */
    private function formSendEmail($block, $data)
    {
        $email = $block->get('form_custom_email');
        if (!$email) {
            $email = $this->BConfig->get('modules/FCom_Core/' . $block->get('form_email'));
        }
        if(!$email){
            return;
        }
        $params = ['to' => $email, 'from' => $this->BConfig->get('modules/FCom_Core/admin_email')];
        //$response = $this->view('cms/email/form')->set('data', $data)->render($params);
        //die($response);
        $this->view('cms/email/form')->set('data', $data)->email($params);
    }

    /**
     * @param Sellvana_Cms_Model_Block $block
     * @param array                    $data
     */
    private function formNotifyAdmin($block, $data)
    {
        if(!$block->get('form_notify_admin') || $block->get('form_notify_admin_user')){
            // if admin notification is not enabled, or admin users are not selected, do nothing
            return;
        }

        $admins = explode(',', $block->get('form_notify_admin_user'));
        $emails = [];
        foreach ($admins as $a) {
            $admin = $this->FCom_Admin_Model_User->load($a);
            if($admin){
                $emails[] = $admin->get('email');
            }
        }
        if(empty($emails)){
            return;
        }

        $email = array_shift($emails);

        $params = ['to' => $email, 'from' => $this->BConfig->get('modules/FCom_Core/admin_email')];
        if(!empty($emails)){
            $params['cc'] = $emails;
        }
        //$response = $this->view('cms/email/admin')->set('data', $data)->render($params);
        //die($response);
        $this->view('cms/email/admin')->set('data', $data)->email($params);
    }

    /**
     * @param Sellvana_Cms_Model_Block $block
     * @param array                    $data
     */
    private function formNotifyCustomer($block, $data)
    {
        if (!$block->get('form_notify_customer') || !$block->get('form_user_email_field') || empty($data[$block->get('form_user_email_field')])) {
            // if notification is not enabled, or email field is not defined, or email field is empty, do nothing
            return;
        }

        $email = $data[$block->get('form_user_email_field')];
        if ($this->BValidate->ruleEmail(['email' => $email], ['field'=>'email'])) {
            $params = ['to' => $email, 'from' => $this->BConfig->get('modules/FCom_Core/admin_email')];
            $viewTpl = $block->get('form_notify_customer_tpl');
            $view = $this->view($viewTpl);
            if($view){
                //$response = $view->set('data', $data)->render($params);
                //die($response);
                $view->set('data', $data)->email($params);
            }
        }
    }
}
