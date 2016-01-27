<?php

/**
 * Class FCom_Admin_View_Abstract
 *
 * @property array $tabs
 * @property array $tab_groups
 */
class FCom_Admin_View_Abstract extends FCom_Core_View_Abstract
{
    /**
     * @param $id
     * @param array $params
     * @return $this
     */
    public function addTab($id, $params)
    {
        $tabs = (array)$this->tabs;
        if (!isset($params['view'])) {
            $params['view'] = $this->get('tab_view_prefix') . $id;
        }
        if (!isset($params['pos'])) {
            $pos = 0;
            foreach ($tabs as $tab) {
                $pos = max($pos, !empty($tab['pos']) ? $tab['pos'] : 0);
            }
            $params['pos'] = $pos + 10;
        }
        if (empty($params['group'])) {
            $params['group'] = 'other';
        }
        $tabs[$id] = $params;
        $this->tabs = $tabs;
        return $this;
    }

    /**
     * @param null $tabs
     * @return array|null
     */
    public function sortedTabs($tabs = null)
    {
        if (is_null($tabs)) {
            $tabs = (array)$this->tabs;
        }
        uasort($tabs, function($a, $b) {
            return $a['pos'] < $b['pos'] ? -1 : ($a['pos'] > $b['pos'] ? 1 : 0);
        });
        #$this->tabs = $tabs;
        return $tabs;
    }

    /**
     * @param $id
     * @param array $params
     * @return $this
     */
    public function addTabGroup($id, $params)
    {
        $tabGroups = (array)$this->tab_groups;

        if (!isset($params['pos'])) {
            $pos = 0;
            foreach ($tabGroups as $tabGroup) {
                $pos = max($pos, !empty($tabGroup['pos']) ? $tabGroup['pos'] : 0);
            }
            $params['pos'] = $pos + 10;
        }
        $tabGroups[$id] = $params;
        $this->tab_groups = $tabGroups;
        return $this;
    }

    /**
     * @return array
     */
    public function sortedTabGroups()
    {
        $tabGroups = (array)$this->tab_groups;
        uasort($tabGroups, function($a, $b) {
            return $a['pos'] < $b['pos'] ? -1 : ($a['pos'] > $b['pos'] ? 1 : 0);
        });
        #$this->tabs = $tabs;
        return $tabGroups;
    }
}
