<div class="col-main">
        <div class="page-title category-title">
            <h1><?= BLocale::_("Wishlist"); ?></h1>
        </div>
<?php if (!$this->wishlist || !$this->wishlist->items()): ?>
    <p class="note-msg"><?= BLocale::_("There are no products in wishlist"); ?>.</p>
<?php else: ?>
     <form name="cart" action="<?=BApp::href('wishlist')?>" method="post">
        <table class="product-list">
            <col width="30"/>
            <col width="60"/>
            <col/>
            <col width="180"/>
            <col width="70"/>
            <col width="70"/>
            <thead>
                <tr>
                    <td><?= BLocale::_("Remove"); ?></td>
                    <td colspan="2"><?= BLocale::_("Product"); ?></td>
                    <td><?= BLocale::_("Price"); ?></td>
                </tr>
            </thead>
            <tbody>
<? foreach ($this->wishlist->items() as $item): $p = $item->product() ?>
                <tr id="tr-product-<?=$p->id?>">
                    <td class="first a-center">
                        <label><input type="checkbox" name="remove[]" class="remove-checkbox" value="<?=$item->id?>"></label>
                    </td>
                    <td>
                        <img src="<?=$this->q($p->thumbUrl(85, 60))?>" width="85" height="60" class="product-img" alt="<?=$this->q($p->product_name)?>"/>
                    </td>
                    <td>
                        <h3 class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></h3>
                    </td>
                    <td class="actions last a-left">
                        <div class="price-box">
                            <span class="price">$<?=number_format($p->base_price)?></span>
                        </div>
                    </td>
                </tr>
<?php endforeach; ?>
             </tbody>
            <tfoot>
                <td></td>
                <td></td>
                <td></td>
                <td><input type="submit" class="button" value="<?= BLocale::_("Update Wishlist"); ?>"/></td>
            </tfoot>
        </table>
    </form>
<?php endif; ?>
</div>