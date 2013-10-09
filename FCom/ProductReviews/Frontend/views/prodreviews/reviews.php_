<?php
$prod = $this->product;
$reviews = $this->product_reviews;
$isLoggedIn = FCom_Customer_Model_Customer::isLoggedIn();
?>
<?php if (!$reviews) :?>
    <p><a href="<?=Bapp::href('prodreviews/add')?>?pid=<?=$prod->id?>"><?= BLocale::_("Be the first to review this product") ?></a></p>
<?php else:?>
    <a href="<?=Bapp::href('prodreviews/add')?>?pid=<?=$prod->id?>"><?= BLocale::_("Review the product") ?></a><br/><br/>
    Total reviews: <?=$prod->num_reviews?><br/>
    <?php foreach ($reviews as $review) :?>
    <div>
        <input name="review[rating<?=$review->id?>]" type="radio" class="star" disabled="disabled" value="1" <?=$review->rating == 1 ? 'checked': ''?>/>
        <input name="review[rating<?=$review->id?>]" type="radio" class="star" disabled="disabled" value="2" <?=$review->rating == 2 ? 'checked': ''?> />
        <input name="review[rating<?=$review->id?>]" type="radio" class="star" disabled="disabled" value="3" <?=$review->rating == 3 ? 'checked': ''?>/>
        <input name="review[rating<?=$review->id?>]" type="radio" class="star" disabled="disabled" value="4" <?=$review->rating == 4 ? 'checked': ''?>/>
        <input name="review[rating<?=$review->id?>]" type="radio" class="star" disabled="disabled" value="5" <?=$review->rating == 5 ? 'checked': ''?>/>
        <span style="font-weight: bold; padding-left: 15px;"><?=$review->title?></span>
        <?=date("F d, Y", strtotime($review->create_at))?>
    <br/>
        <?=nl2br($review->text)?><br/>

        <?php if ($isLoggedIn): ?>
            <a href="javascript:void(0)"
                onclick="$.get('<?=Bapp::href('prodreviews/offensive')?>?rid=<?=$review->id?>');$('#offensive_msg_<?=$review->id?>').show()"
                class="error">Offensive review</a>
            <div id="offensive_msg_<?=$review->id?>" class="alert alert-success">Thank you for your feedback!</div>
            <div id="block_review_helpful_<?=$review->id?>">
                <form action="<?=Bapp::href('prodreviews/helpful')?>" method="post"  onsubmit="return false;">
                    <input type="hidden" name="pid" value="<?=$prod->id?>">
                    <input type="hidden" name="rid" value="<?=$review->id?>">
                    <?= BLocale::_("Was this review helpful to you") ?>?
                    <button type="submit" name="review_helpful" value="yes"
                        onclick="FCom.add_review_rating('<?=Bapp::href('prodreviews/helpful')?>', <?=$review->id?>, 'yes');"><?= BLocale::_("Yes") ?></button>
                    <button type="submit" name="review_helpful" value="no"
                        onclick="FCom.add_review_rating('<?=Bapp::href('prodreviews/helpful')?>', <?=$review->id?>, 'no');"><?= BLocale::_("No") ?></button>
                </form>
            </div>
            <span id="block_review_helpful_done_<?=$review->id?>" style="color:green"></span>


        <?php endif; ?>

        <br/><br/>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<script>require(['fcom.productreviews'])</script>
