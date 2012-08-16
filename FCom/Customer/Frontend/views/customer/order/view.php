<h2><?= BLocale::_("Orders") ?> #<?=$this->order->id?></h2>

<a href="<?=Bapp::href('customer/order')?>"><?= BLocale::_("Back") ?></a>

<table border="1">
    <tr>
        <th width="200px;"><?= BLocale::_("Shipping method") ?></th>
        <td align="center"><?=$this->order->shipping_method?> </td>
    </tr>
    <tr>
        <th width="200px;"><?= BLocale::_("Payment method") ?></th>
        <td align="center"><?=$this->order->payment_method?> </td>
    </tr>
    <tr>
        <th width="200px;"><?= BLocale::_("Status") ?></th>
        <td align="center"><?=$this->order->status?> </td>
    </tr>
    <tr>
        <th width="200px;"><?= BLocale::_("Balance") ?></th>
        <td align="center"><?=$this->order->balance?> </td>
    </tr>
</table>


<?php if ($this->orderItems) :?>
<h2><?= BLocale::_("Order items") ?></h2>
    <table border="1">
        <tr>
            <th width="50px;">ID</th>
            <th width="200px;"><?= BLocale::_("Info") ?></th>
            <th width="200px;"><?= BLocale::_("Qty") ?></th>
            <th width="200px;"><?= BLocale::_("Total") ?></th>
        </tr>
    <?php foreach($this->orderItems as $item) :?>
        <tr>
            <td align="center"><?=$item->id?></td>
            <td align="center"><?=$this->view('customer/order/item')->set('product', BUtil::fromJson($item->product_info)) ?> </td>
            <td align="center"><?=$item->qty?> </td>
            <td align="center"><?=$item->total?> </td>
        </tr>
    <?php endforeach; ?>
    </table>
<?php endif; ?>