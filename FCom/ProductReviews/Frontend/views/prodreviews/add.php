<form action="" method="post">
    <input type="hidden" name="pid" value="<?=$this->pid?>">
    <input name="review[rating]" type="radio" class="star" value="1"/>
    <input name="review[rating]" type="radio" class="star" value="2"/>
    <input name="review[rating]" type="radio" class="star" checked="checked" value="3"/>
    <input name="review[rating]" type="radio" class="star" value="4"/>
    <input name="review[rating]" type="radio" class="star" value="5"/>
    <br/>
    <input type="text" name="review[title]" placeholder="<?= BLocale::_("Title"); ?>" /><br/>
    <textarea name="review[text]" placeholder="<?= BLocale::_("Your review here"); ?>"></textarea><br/>
    <input type="submit">
</form>