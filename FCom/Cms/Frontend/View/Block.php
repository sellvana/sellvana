<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Cms_Frontend_View_Block extends FCom_Core_View_Abstract
{
    static protected $_layoutHlp;
    static protected $_origClass = __CLASS__;
    /**
     * Create a new block view instance within layout
     *
     * @param string $block block handle or instance
     * @param array $params block view creation parameters
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

    /**
     * Renderer for use with other views
     *
     * @param BView $view
     * @return string
     */
    public function renderer($view)
    {
        $model = $this->getBlockModel($view);
        if (!$model) {
            return '';
        }

        $subRenderer = $this->BLayout->getRenderer($model->renderer ? $model->renderer : 'FCom_LibTwig');

        $view->setParam([
            //'renderer'    => $subRenderer,
            'source'      => $model->content ? $model->content : ' ',
            'source_name' => 'cms_block:' . get_class($model) . ':' . $model->handle,
            'source_mtime' => $model->modified_time,
            'source_untrusted' => true,
        ]);

        $content = $this->BUtil->call($subRenderer['callback'], $view);

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
