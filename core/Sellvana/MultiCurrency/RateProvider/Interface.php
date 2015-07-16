<?php defined('BUCKYBALL_ROOT_DIR') || die();

interface Sellvana_MultiCurrency_RateProvider_Interface
{
    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return array
     */
    public function fetchRates();
}