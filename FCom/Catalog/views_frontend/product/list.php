<?
$data = $this->productsORM->paginate(null, array('ps'=>25));
?>
<? if (!$data['state']['c']): ?>

    <p class="note-msg">There are no products matching the selection.</p>

<? else: ?>

    <?=$this->view('product/pager')->set('state', $data['state'])?>
    <?=$this->view('compare/block')?>
    <table class="product-list">
        <col width="30"/>
        <col width="60"/>
        <col/>
        <col width="180"/>
        <tbody>
            <?=$this->view('product/rows')->set('products', $data['rows']) ?>
        </tbody>
    </table>

<script>
$('.price-range').tooltip({effect:'slide',position:'bottom left', offset:[-30, 80], events:{def:'click,mouseleave'}}).dynamic({classNames:''});
</script>

<? endif ?>
