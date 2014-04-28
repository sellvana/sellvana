<?php $p = $this->get( 'prod' ); $basePrice = $p->get( 'base_price' ); $salePrice = $p->get( 'sale_price' ); ?>
<?php if ( $basePrice != $salePrice ): ?>
    <div class="price-box">
        <div class="old-price"><span class="title"><?=$this->_( 'Was:' )?></span><span class="price"><?=BLocale::currency( $basePrice )?></span></div>
        <div class="new-price"><span class="title"><?=$this->_( 'Now:' )?></span><span class="price"><?=BLocale::currency( $salePrice )?></span></div>
    </div>
<?php else : ?>
    <div class="price-box">
        <span class="price"><?=BLocale::currency( $basePrice )?></span>
    </div>
<?php endif ?>