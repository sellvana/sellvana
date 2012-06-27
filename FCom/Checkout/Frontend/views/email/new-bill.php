<? $order = $this->order ?>
<!--{ Content-Type: text/html; charset=UTF-8 }-->
<!--{ From: "Fulleron" <support@fulleron.com> }-->
<!--{ To: "<?=$order->billing()->firstname.' '.$order->billing()->lastname?>" <<?=$order->billing()->email?>> }-->
<!--{ Subject: Thank your for payment. }-->

Hello, <?=$order->billing()->firstname?>.

<p>Thank you </p>
