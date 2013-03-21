<?php

class FCom_CatalogIndex extends BClass
{
    
    protected static $_products;
    protected static $_indexData;
    protected static $_filterValues;
    protected static $_searchTerms;
    
    static public function bootstrap()
    {
        
    }
    
    static public function indexProducts($products)
    {
        static::_indexFetchProductsData($products);
        unset($products);
        static::_indexSaveDocs();
        static::_indexSaveFilterData();
        static::_indexSaveSearchData();
        static::indexCleanMemory();
    }
    
    static protected function _indexFetchProductsData($products)
    {
        
    }
    
    static protected function _indexSaveDocs()
    {
        
    }
    
    static protected function _indexSaveFilterData()
    {
        
    }
    
    static protected function _indexSaveSearchData()
    {
        
    }
    
    static protected function indexCleanMemory($all=false)
    {
        
    }
    
    static public function indexGC()
    {
        
    }
}