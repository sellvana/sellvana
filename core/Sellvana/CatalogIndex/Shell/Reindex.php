<?php

/**
 * Class Sellvana_CatalogIndex_Shell_Reindex
 *
 * @property Sellvana_CatalogIndex_Model_Doc $Sellvana_CatalogIndex_Model_Doc
 * @property Sellvana_CatalogIndex_Main $Sellvana_CatalogIndex_Main
 */
class Sellvana_CatalogIndex_Shell_Reindex extends FCom_Core_Shell_Abstract
{
    static protected $_origClass = __CLASS__;

    static protected $_actionName = 'catalog:reindex';

    static protected $_availOptions = [
        'f' => 'force',
    ];

    protected function _run()
    {
        $this->BDebug->disableAllLogging();

        $this->println('Starting reindexing...');

        if ($this->getOption('f')) {
            $this->Sellvana_CatalogIndex_Model_Doc->update_many(['flag_reindex' => 1]);
        }

        $this->BCache->save('index_progress_total', 0);
        $this->BCache->save('index_progress_reindexed', 0);

        $this->Sellvana_CatalogIndex_Main->getIndexer()->indexPendingProducts()->indexGC();

        $this->println('Reindexing complete');
    }

    public function getShortHelp()
    {
        return 'Reindex catalog';
    }

    public function getLongHelp()
    {
        return <<<EOT

Reindex catalog

Options:
    {white*}-f
    --force{/}     Force reindex the whole catalog

EOT;
    }
}