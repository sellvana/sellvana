<?php foreach($this->promo->media() as $promoMedia): ?>
    <img src="/<?=$promoMedia->folder.'/'.$promoMedia->file_name?>">
    <div style="clear:both;">
<?php endforeach; ?>