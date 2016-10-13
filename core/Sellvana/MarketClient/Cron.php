<?php

/**
 * Class Sellvana_MarketClient_Cron
 *
 * @property FCom_Admin_Model_Activity $FCom_Admin_Model_Activity
 * @property Sellvana_MarketClient_RemoteApi $Sellvana_MarketClient_RemoteApi
 */
class Sellvana_MarketClient_Cron extends BClass
{
    public function collectMarketUpdates()
    {
        if (!$this->BConfig->get('modules/Sellvana_MarketClient/auto_check_enable')) {
            return $this;
        }

        $updates = $this->Sellvana_MarketClient_RemoteApi->fetchUpdatesFeed();

        $items = [];
        if (!empty($updates['items'])) {
            foreach ($updates['items'] as $item) {
                //TODO: make sure correct structure
                $item['feed'] = 'remote';
                $items[] = $item;
            }
        }

        $this->FCom_Admin_Model_Activity->addActivityItems($items);

        return $this;
    }
}
