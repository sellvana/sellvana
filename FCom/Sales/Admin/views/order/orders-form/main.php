<?php $m = $this->model; ?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Order: <?=$m->id?> </h4>
        </li>
        <li>
            <h4 class="label">Order Date: <?=$m->purchased_dt?> </h4>
        </li>
        <li>
            <h4 class="label">Order Status: <?=$m->status?> </h4>
        </li>
    </ul>
</fieldset>

<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Account information </h4>
        </li>

            <?php if(false == $m->customer->guest):?>
                <li>Customer name: <?=$m->customer->firstname?> <?=$m->customer->lastname?></li>
                <li>Email: <?=$m->customer->email?></li>
            <?php else:?>
                <li>Purchased by guest</li>
            <?php endif; ?>

    </ul>
</fieldset>

<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Billing info </h4>
        </li>
        <li>
            <?=$m->billing_address?>
        </li>
    </ul>
</fieldset>


<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Shipping info </h4>
        </li>
        <li>
            <?=$m->shipping_address?>
        </li>
    </ul>
</fieldset>

<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Payment info </h4>
        </li>
        <li>
            Payment method: <?=$m->payment_method?>
        </li>
        <?php if(BUtil::fromJson($m->payment_details)):?>
            <?php foreach(BUtil::fromJson($m->payment_details) as $paymentKey => $paymentValue):?>
                <li><?=$paymentKey?>: <?=$paymentValue?></li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</fieldset>


<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Shipping &amp; Handling information </h4>
        </li>
        <li>
            Shipping method: <?=$m->shipping_method?>
        </li>
        <li>
            Shipping service: <?=$m->shipping_service_title?>
        </li>
    </ul>
</fieldset>

<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Items ordered </h4>
        </li>
        <?php if($m->items):?>
            <?php foreach($m->items as $item):
                $product = BUtil::fromJson($item->product_info);
                ?>
                <li>Product: <?=$product['product_name']?></li>
                <li>SKU: <?=$product['manuf_sku']?></li>
                <li>Price: <?=$product['base_price']?></li>
                <li>Qty: <?=$item->qty?></li>
                <li>Total: <?=$item->total?></li>
                <li>---------------------------</li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</fieldset>