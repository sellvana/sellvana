<?=$this->view('indextank/product/pager')->set('state', $this->products_data['state'])?>

<?php if (!$this->products_data['state']['c']): ?>

    <p class="note-msg"><?= BLocale::_("There are no products matching the selection") ?>.</p>

<?php else: ?>
<div style="width: 750px; float: right; margin-top: 20px;">
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
</div>
    <?=$this->view('indextank/product/filters')->set('state', $this->products_data['state'])?>

<?php endif ?>
