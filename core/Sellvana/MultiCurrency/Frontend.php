<?php

/**
 * Class Sellvana_MultiCurrency_Frontend
 *
 */
class Sellvana_MultiCurrency_Frontend extends BClass
{
    public function bootstrap()
    {
        $cur = $this->BSession->get('current_currency');
        if ($cur) {
            $this->BLocale->setCurrency($cur);
        }
    }
}