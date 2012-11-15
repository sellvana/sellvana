            <?php if(!empty($this->promoList)): ?>
                <section class="col2-set sub-cart">
                    <h2>Promotions</h2>
		    	<?php foreach($this->promoList as $promo):?>
                            <a href="#" onclick="window.open('<?=BApp::href('promo/media')?>?id=<?=$promo->id?>','','width=200,height=100')" ><?=$promo->description?></a><br/>
                        <?php endforeach; ?>
	    	</section>
            <?php endif; ?>