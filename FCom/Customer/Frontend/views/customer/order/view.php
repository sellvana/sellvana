<h2>Orders #<?=$this->order->id?></h2>

<a href="<?=Bapp::href('customer/order')?>">Back</a>

<table border="1">
    <tr>
        <th width="200px;">Shipping method</th>
        <td align="center"><?=$this->order->shipping_method?> </td>
    </tr>
    <tr>
        <th width="200px;">Payment method</th>
        <td align="center"><?=$this->order->payment_method?> </td>
    </tr>
    <tr>
        <th width="200px;">Status</th>
        <td align="center"><?=$this->order->status?> </td>
    </tr>
    <tr>
        <th width="200px;">Balance</th>
        <td align="center"><?=$this->order->balance?> </td>
    </tr>
</table>


<?php if ($this->orderItems) :?>
<h2>Order items</h2>
    <table border="1">
        <tr>
            <th width="50px;">ID</th>
            <th width="200px;">Info</th>
            <th width="200px;">Qty</th>
            <th width="200px;">Total</th>
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