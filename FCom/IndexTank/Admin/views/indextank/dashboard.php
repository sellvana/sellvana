
<table style="font-size: 14px;">
    <tr>
        <td><?=$this->status['name']?> (created at <?=date("Y-m-d", strtotime($this->status['date']))?>)</td>
        <td><?=$this->status['status']?></td>
    </tr>
    <tr>
        <td><?=$this->status['size']?> documents</td>
        <td></td>
    </tr>
    <tr>
        <td><button class="st1 sz2 btn" onclick="ajax_index_all_products();"><span>Index All Products</span></button></td>
        <td><button class="st1 sz2 btn" onclick="ajax_products_clear_all();"><span>Clear Products Index</span></button></td>
    </tr>
</table>
<script type="text/javascript">
    function ajax_index_all_products() { $.ajax({ type: "GET", url: "<?=BApp::href('indextank/products/index')?>"})
        .done(function( msg ) { alert( msg ); window.location.reload(); }); }
    function ajax_products_clear_all() { $.ajax({ type: "DELETE", url: "<?=BApp::href('indextank/products/index')?>"})
        .done(function( msg ) { alert( msg ); window.location.reload(); }); }
</script>