<?php

class FCom_Catalog_Api extends BClass
{
	static public function bootstrap()
	{
		BRouting::i()
            //api route for category
            ->any('/v1/catalog/category', 'FCom_Catalog_ApiServer_V1_Category.index')
            ->any('/v1/catalog/category/:id', 'FCom_Catalog_ApiServer_V1_Category.index')

            //api route for product
            ->any('/v1/catalog/product', 'FCom_Catalog_ApiServer_V1_Product.index')
            ->any('/v1/catalog/product/:id', 'FCom_Catalog_ApiServer_V1_Product.index')
        ;
	}
}