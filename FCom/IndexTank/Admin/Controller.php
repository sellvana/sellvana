<?php
class FCom_IndexTank_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    static public function bootstrap()
    {
        BPubSub::i()->on('FCom_IndexTank_Admin_Controller_ProductFields::gridViewBefore',
                'FCom_IndexTank_Admin_Controller::onGridViewBefore');
    }
    static public function onGridViewBefore($args)
    {
        try {
            FCom_IndexTank_Index_Product::i()->status();
        } catch (Exception $e){
            return false;
        }

        $insert = '<button class="st1 sz2 btn" onclick="ajax_index_all_products();"><span>Index All Products</span></button>
            <button class="st1 sz2 btn" onclick="ajax_products_clear_all();"><span>Clear Products Index</span></button>
<script type="text/javascript">
function ajax_index_all_products() { $.ajax({ type: "GET", url: "'.BApp::href('indextank/products/index').'"})
    .done(function( msg ) { alert( msg ); }); }
function ajax_products_clear_all() { $.ajax({ type: "DELETE", url: "'.BApp::href('indextank/products/index').'"})
    .done(function( msg ) { alert( msg ); }); }
</script>
';

       $args['view']->set(array('actions' => array('new' => ($insert . $args['view']->actions['new']))));
        /*
        if (($el = BGanon::i()->find('header.adm-page-title div.btns-set', 0))) {
            $el->setInnerText($insert.$el->getInnerText());
        }
        */

    }
}