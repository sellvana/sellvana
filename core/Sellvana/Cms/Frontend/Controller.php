<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Cms_Frontend_Controller
 *
 * @property Sellvana_Cms_Model_Block $Sellvana_Cms_Model_Block
 * @property Sellvana_Cms_Model_Nav $Sellvana_Cms_Model_Nav
 * @property Sellvana_Cms_Frontend_View_Block $Sellvana_Cms_Frontend_View_Block
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 */
class Sellvana_Cms_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_page()
    {
        $pageUrl = $this->BRequest->param('page');
        if (!($pageUrl === '' || is_null($pageUrl))) {
            $block = $this->Sellvana_Cms_Model_Block->loadWhere(['page_enabled' => 1, 'page_url' => (string)$pageUrl]);
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

        $view = $this->Sellvana_Cms_Frontend_View_Block->createView($block);
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
            $context = ['type' => 'cms_page', 'main_view' => $viewName];
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
                $block = $this->Sellvana_Cms_Model_Block->loadWhere(['page_enabled' => 1, 'page_url' => (string)$pageUrl]);
            }
            if (empty($block) || !$block->validateBlock()) {
                $this->forward(false);
                return;
            }
            // todo save form data to fcom_cms_form_data ?
            // send email
        } catch (Exception $e) {
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
}
