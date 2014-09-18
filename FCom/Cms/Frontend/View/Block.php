<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Cms_Frontend_View_Block extends FCom_Core_View_Abstract
{
    /**
     * @var BLayout
     */
    static protected $_layoutHlp;
    static protected $_origClass = __CLASS__;

    /**
     * Create a new block view instance within layout
     *
     * @param string|FCom_Cms_Model_Block|array $block block handle or instance
     * @param array $params block view creation parameters
     * @throws BException
     * @return FCom_Cms_Frontend_View_Block
     */
    public function createView($block, array $params = [])
    {
        if (!static::$_layoutHlp) {
            static::$_layoutHlp = $this->BLayout;
        }
        if (is_array($block) && empty($params)) {
            $params = $block;
            $block = $params['block'];
        }
        if (empty($params['view_class'])) {
            $params['view_class'] = static::$_origClass;
        }
        if ($block instanceof FCom_Cms_Model_Block) {
            $params['block'] = $block->handle;
            $params['model'] = $block;
        } elseif (is_string($block)) {
            $params['block'] = $block;
        } else {
            throw new BException('Invalid block name');
        }
        $viewName = !empty($params['view_name']) ? $params['view_name'] : ('_cms_block/' . $params['block']);
        $view = static::$_layoutHlp->getView($viewName);
        if (!$view instanceof FCom_Cms_Frontend_View_Block) {
            $view = static::$_layoutHlp->addView($viewName, $params)->getView($viewName);
        }
        return $view;
    }

    /**
     * Get block model instance for the current view
     * @param $view
     * @return bool
     */
    public function getBlockModel($view)
    {
        $model = $view->getParam('model');
        if (!$model  || !is_object($model) || !$model instanceof FCom_Cms_Model_Block) {
            $model = $view->getParam('block');
            if (is_numeric($model)) {
                $model = $this->FCom_Cms_Model_Block->load($model);
            } elseif (is_string($model)) {
                $model = $this->FCom_Cms_Model_Block->load($model, 'handle');
            }
            if (!$model || !is_object($model) || !$model instanceof FCom_Cms_Model_Block) {
                $this->BDebug->warning('CMS Block not found or invalid');
                return false;
            }
            $view->setParam('model', $model);
        }
        return $model;
    }
    protected $_formFieldsPlaceholder = '__FORM_FIELDS__';

    /**
     * Renderer for use with other views
     *
     * @param BView $view
     * @return string
     */
    public function renderer($view)
    {
        /** @var FCom_Cms_Model_Block $model */
        $model = $this->getBlockModel($view);

        if (!$model) {
            return '';
        }

        $subRenderer = $this->BLayout->getRenderer($model->renderer ? $model->renderer : 'FCom_LibTwig');

        $blockContent = $model->getContent();
        if (strpos($blockContent, $this->_formFieldsPlaceholder) !== false) {
            $formText = $this->_prepareFormFields();
            $blockContent = str_replace($this->_formFieldsPlaceholder, $formText, $blockContent);
        }
        $view->setParam([
            //'renderer'    => $subRenderer,
            'source'      => $blockContent,
            'source_name' => 'cms_block:' . get_class($model) . ':' . $model->handle,
            'source_mtime' => $model->modified_time,
            'source_untrusted' => true,
        ]);

        $content = $this->BUtil->call($subRenderer['callback'], $view);

        return $content;
    }

    protected function _prepareFormFields()
    {
        $model = $this->getBlockModel($this);
        $block = ['block' => 'test'];
        $view = $this->createView($block);
        $content = "{% include THIS.view('core/form-elements').twigName() as forms %}\n";
        $content .= "<form method='post' >";
        $formFields = $this->BUtil->fromJson($model->get('form_fields'));
        foreach ($formFields as $fieldConfig) {
            $fieldConfig['options'] = $this->BUtil->fromJson($fieldConfig['options']);
            $content .= print_r($fieldConfig, 1);
        }

        $content .= "</form>";
        $view->setParam([
            //'renderer'    => $subRenderer,
            'source'      => $content,
            'source_name' => 'cms_block:' . 'test'. ':' . true,
            'source_mtime' => time(),
            'source_untrusted' => false,
        ]);
        $content = $view->render();
        return $content;
    }

    /**
     * Override _render() for performance, instead of using renderer callback
     *
     * @return string
     */
    public function _render()
    {
        return $this->renderer($this);
    }
}
