<?php
$prod = $this->prod;
$fields = $prod->customFieldsShowOnFrontend();
if (!$fields) {
    return;
}
?>
<h2>Custom fields here</h2>
<?php foreach($fields as $f): ?>
    <?=$f->frontend_label?>: <?=$prod->{$f->field_code}?><br/>
<?php endforeach; ?>
<hr/>