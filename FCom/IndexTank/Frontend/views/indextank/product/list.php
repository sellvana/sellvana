<? if (!$this->products_data['state']['c']): ?>

    <p class="note-msg">There are no products matching the selection.</p>

<? else: ?>

    <?=$this->view('indextank/product/pager')->set('state', $this->products_data['state'])?>

    <table class="product-list">
        <col width="30"/>
        <col width="60"/>
        <col/>
        <col width="180"/>
        <tbody>
            <?=$this->view('indextank/product/rows')
                ->set('products', $this->products_data['rows'])
                ->set('category', $this->category) ?>
        </tbody>
    </table>

<script>
$('.price-range').tooltip({effect:'slide',position:'bottom left', offset:[-30, 80], events:{def:'click,mouseleave'}}).dynamic({classNames:''});
</script>

<? endif ?>
