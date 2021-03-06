<?php

/**
 * Class Sellvana_CurrencyManager_Main
 *
 * @property Sellvana_CurrencyManager_Model_Manager $Sellvana_CurrencyManager_Model_Manager
 * @property FCom_Frontend_Main $FCom_Frontend_Main
 */
class Sellvana_CurrencyManager_Main extends BClass
{
    protected static $_viewStack = [];

    /**
     * @param array $args
     */
    public function onCurrencyFormat($args)
    {
        if (!$this->_isEnabled()) {
            return;
        }

        /** @var NumberFormatter $formatter */
        $formatter = &$args['formatter'];
        $value = &$args['value'];
        $currency = $args['currency'];

        $this->Sellvana_CurrencyManager_Model_Manager->setFormatterPattern($formatter, $value, $currency);
    }

    /**
     * @param array $args
     */
    public function onGetSymbol($args)
    {
        if (!$this->_isEnabled()) {
            return;
        }

        $value = &$args['value'];
        $currency = $args['currency'];

        $symbol = $this->Sellvana_CurrencyManager_Model_Manager->getConfig('replace_symbol_with', $currency, false);
        if ($symbol) {
            $value = $symbol;
        }
    }

    /**
     * Check if extension is enabled for current view
     *
     * @return bool
     */
    protected function _isEnabled()
    {
        $area = $this->BRequest->area();

        if ($area == 'FCom_Frontend' && !$this->Sellvana_CurrencyManager_Model_Manager->getConfig('enabled_on_frontend')) {
            return false;
        }

        if ($area == 'FCom_Admin' && !$this->Sellvana_CurrencyManager_Model_Manager->getConfig('enabled_on_admin')) {
            return false;
        }

        $disabledViews = $this->Sellvana_CurrencyManager_Model_Manager->getConfig('disabled_in_views', null, []);
        $currentView = $this->_getCurrentView();

        return !($area == 'FCom_Frontend' && in_array($currentView, $disabledViews));
    }

    /**
     * Get current view
     *
     * @return string
     */
    protected function _getCurrentView()
    {
        return end(self::$_viewStack);
    }

    /**
     * @param $args
     */
    public function onBeforeViewRender($args)
    {
        if ($args['view'] instanceof BView) {
            array_push(self::$_viewStack, $args['view']->getParam('view_name'));
        }
    }

    /**
     * @param $args
     */
    public function onAfterViewRender($args)
    {
        if ($args['view'] instanceof BView) {
            array_pop(self::$_viewStack);
        }
    }
}