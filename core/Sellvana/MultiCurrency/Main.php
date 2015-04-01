<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Main
 *
 */
class Sellvana_MultiCurrency_Main extends BClass
{
    public function getCurrentCurrency()
    {
        return $this->BApp->get('current_currency');
    }

    public function curencyOptions()
    {
        return [
            'USD' => 'USD',
            'CAD' => 'CAD',
            'AUD' => 'AUD',
        ];
    }
}
