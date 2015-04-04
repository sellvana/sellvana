<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_IndexTank_Admin_Controller
 *
 * @property FCom_LibGanon_Main $FCom_LibGanon_Main
 * @property Sellvana_IndexTank_Index_Product $Sellvana_IndexTank_Index_Product
 */
class Sellvana_IndexTank_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function bootstrap()
    {
        $this->BEvents->on('Sellvana_IndexTank_Admin_Controller_ProductFields::gridViewBefore',
                'Sellvana_IndexTank_Admin_Controller::initButtons');
        $this->BEvents->on('Sellvana_IndexTank_Admin_Controller_ProductFunctions::gridViewBefore',
                'Sellvana_IndexTank_Admin_Controller::initButtons');
    }
    public function initButtons()
    {
        $this->FCom_LibGanon_Main->ready('Sellvana_IndexTank_Admin_Controller::initIndexButtons', ['on_path' => [
            '/catalog/products',
            '/indextank/product_fields',
            '/indextank/product_functions',
        ]]);
    }

    public function initIndexButtons($args)
    {
        try {
            $this->Sellvana_IndexTank_Index_Product->status();
        } catch (Exception $e) {
            return false;
        }

        //$this->BConfig->set('modules/Sellvana_IndexTank/cron_indexing', 1);

        //echo $cronIndexing;exit;

         $insert = ' <button class="st1 sz2 btn" onclick="control_index_dialog();"><span>Index Control Page</span></button>';


        $insert .= '
            <button class="st1 sz2 btn" onclick="ajax_index_all_products_start();"><span>Start Products Indexing</span></button>
            <button class="st1 sz2 btn" onclick="ajax_products_clear_all();"><span>Clear Products Index</span></button>
<script type="text/javascript">
function ajax_index_all_products_start() { $.ajax({ type: "GET", url: "' . $this->BApp->href('indextank/products/index') . '"})
    .done(function( msg ) { alert( "Products re-indexing started" ); }); }
function ajax_index_all_products_stop() { $.ajax({ type: "GET", url: "' . $this->BApp->href('indextank/products/index-stop') . '"})
    .done(function( msg ) { alert( "Products re-indexing interrupted" ); }); }
function ajax_products_clear_all() { $.ajax({ type: "DELETE", url: "' . $this->BApp->href('indextank/products/index') . '"})
    .done(function( msg ) { alert( "Index recreated" ); }); }
</script>
';
        if (($el = $this->FCom_LibGanon_Main->find('header.adm-page-title div.btns-set', 0))) {
            $el->setInnerText($insert . $el->getInnerText());
        }

    }
}
