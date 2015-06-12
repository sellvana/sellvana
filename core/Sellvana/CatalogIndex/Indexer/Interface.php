<?php

interface Sellvana_CatalogIndex_Indexer_Interface
{
    public function indexProducts($products);

    public function searchProducts(array $params = []);

    public function indexPendingProducts();

    public function reindexField($field);

    public function reindexFieldValue($field, $value);

    public function indexDropDocs($pIds);

    public function indexGC();
}