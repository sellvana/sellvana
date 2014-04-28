<?php


class FCom_IndexTank_Search extends BClass
{
    /**
     *
     * @param type $q - query
     * @param type $sc - sort by
     * @param type $f - filters
     * @param type $v - filter by range
     * @param int $page - current page
     * @param int $resultPerPage - results per page
     * @return Array
     */
    public function search( $q, $sc, $f, $v, $page, $resultPerPage )
    {
        $r = BRequest::i()->get(); // GET request
        $q = trim( $q );

        if ( $sc ) {
            FCom_IndexTank_Index_Product::i()->scoringBy( $sc );
        }

        $productFields = FCom_IndexTank_Model_ProductField::i()->getList();
        $inclusiveFields = FCom_IndexTank_Model_ProductField::i()->getInclusiveList();

        $filtersSelected = [];

        $categorySelected = '';

        if ( $f ) {
            foreach ( $f as $key => $values ) {
                $is_category = false;
                if ( $key == 'category' ) {
                    $is_category = true;
                    $kv = explode( ":", $values );
                    if ( empty( $kv ) ) {
                        continue;
                    }
                    $key = $kv[ 0 ];
                    $values = [ $kv[ 1 ] ];
                    $categorySelected = $key;
                }

                if ( !is_array( $values ) ) {
                    $values = [ $values ];
                }
                if ( isset( $inclusiveFields[ $key ] ) ) {
                    FCom_IndexTank_Index_Product::i()->rollupBy( $key );
                }

                foreach ( $values as $value ) {
                    FCom_IndexTank_Index_Product::i()->filterBy( $key, $value );
                }
                $filtersSelected[ $key ] = $values;
            }
        }

        if ( $v ) {
            $variablesFields = FCom_IndexTank_Model_ProductField::i()->getVariablesList();
            foreach ( $v as $key => $values ) {
                if ( !is_array( $values ) ) {
                    $values = [ $values ];
                }
                if ( in_array( $key, $variablesFields ) ) {
                    if ( $values[ 'from' ] < $values[ 'to' ] ) {
                        FCom_IndexTank_Index_Product::i()->filterRange( $variablesFields[ $key ]->var_number, $values[ 'from' ], $values[ 'to' ] );
                    }
                }
            }
        }

        if ( empty( $resultPerPage ) ) {
            $resultPerPage = 25;
        }
        if ( empty( $page ) ) {
            $page = 1;
        }
        $start = ( $page - 1 ) * $resultPerPage;

        $productsORM = FCom_IndexTank_Index_Product::i()->search( $q, $start, $resultPerPage );
        $facets = FCom_IndexTank_Index_Product::i()->getFacets();


        $productsData = FCom_IndexTank_Index_Product::i()->paginate( $productsORM, $r,
                [ 'ps' => 25, 'c' => FCom_IndexTank_Index_Product::i()->totalFound() ] );

        //get all facets exclude categories
        $facetsData = FCom_IndexTank_Index_Product::i()->collectFacets( $facets );
        $categoriesData = FCom_IndexTank_Index_Product::i()->collectCategories( $facets, $categorySelected );


        $productsData[ 'state' ][ 'fields' ] = $productFields;
        $productsData[ 'state' ][ 'facets' ] = $facets;
        $productsData[ 'state' ][ 'filter_selected' ] = $filtersSelected;
        $productsData[ 'state' ][ 'available_facets' ] = $facetsData;
        $productsData[ 'state' ][ 'available_categories' ] = $categoriesData;
        $productsData[ 'state' ][ 'category_selected' ] = $categorySelected;
        $productsData[ 'state' ][ 'filter' ] = $v;
        $productsData[ 'state' ][ 'save_filter' ] = BConfig::i()->get( 'modules/FCom_IndexTank/save_filter' );

        BEvents::i()->fire( __METHOD__, [ 'data' => &$productsData ] );

        return $productsData;
    }

    public function publicApiUrl()
    {
        $url = '';
        if ( BConfig::i()->get( 'modules/FCom_IndexTank/api_url' ) ) {
            $url = BConfig::i()->get( 'modules/FCom_IndexTank/api_url' );
            $parsed = parse_url( $url );
            unset( $parsed[ 'pass' ] );
            $url = BUtil::unparseUrl( $parsed );
        }
        return $url;
    }

    public function indexName()
    {
        if ( BConfig::i()->get( 'modules/FCom_IndexTank/index_name' ) ) {
            return BConfig::i()->get( 'modules/FCom_IndexTank/index_name' );
        }
        return '';
    }

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Search
    */
    public static function i( $new = false, array $args = [] )
    {
        return BClassRegistry::instance( __CLASS__, $args, !$new );
    }
}
