<?php

class FCom_FreshBooks_RemoteApi extends BClass
{
    public function __construct()
    {
        require_once __DIR__ . '/lib/FreshBooks/Client.php';
        require_once __DIR__ . '/lib/FreshBooks/Invoice.php';
        require_once __DIR__ . '/lib/FreshBooks/Payment.php';

        $config = BConfig::i()->get('modules/FCom_FreshBooks/api');
        FreshBooks_HttpClient::init($config['url'], $config['key']);
    }

    public function createInvoiceFromOrder($args)
    {
        $order = $args['order'];
        $config = BConfig::i()->get('modules/FCom_FreshBooks');
        if (in_array($order->status, (array)$config['order']['status.collect'])) {
            $this->postInvoice($order, $config['email']['autosend'], $order->is('paid'));
            $order->set('status', $config['order']['status.set'])->save();
        }
        return $this;
    }

    public function postInvoice($order, $send = false, $paid = false, $newOrderStatus = null)
    {
        $er = error_reporting();
        error_reporting(E_ERROR | E_WARNING | E_PARSE);

        $client = new FreshBooks_Client();

        $rows = [];
        $resultInfo = [];

        $client->listing($rows, $resultInfo, 1, 25, ['email' => $order->email]);
        if (sizeof($rows)) {
            $client = $rows[0];
        } else {
            $client->firstName = htmlspecialchars($order->firstname);
            $client->lastName = htmlspecialchars($order->lastname);
            $client->organization = htmlspecialchars(!empty($order->company) ? $order->company : $order->firstname . ' ' . $order->lastname);

            $client->email = $order->email;
            $client->workPhone = $order->phone;
            $client->notes = "";

            $client->pStreet1 = htmlspecialchars($order->street1);
            $client->pStreet2 = htmlspecialchars($order->street2);
            $client->pCity = $order->city;
            $client->pState = $order->region;
            $client->pCountry = $order->country;
            $client->pCode = $order->postcode;

            $client->create();
        }
        if ($client->lastError) {
            error_reporting($er);
            var_dump($client); exit;
        }
        $clientId = $client->clientId;

        $invoice = new FreshBooks_Invoice();
        $invoice->clientId = $clientId;
        $invoice->number = sprintf('%07d', $order->id);
        $invoice->date = date('Y-m-d', strtotime($order->ts));
        $invoice->notes = "Order #" . $order->id;
        $totalAmount = 0;
        foreach ($order->items() as $item) {
            $totalAmount += $item->row_total;
            $invoice->lines[] = [
                'name' => $item->code,
                'description' => $item->product_name,
                'unitCost' => $item->price,
                'quantity' => $item->qty,
            ];
        }
        $invoice->create();
        if ($invoice->lastError) {
            error_reporting($er);
            var_dump($invoice); exit;
        }
        $invoiceId = $invoice->invoiceId;

        if ($paid) {
            $payment = new FreshBooks_Payment();
            $payment->invoiceId = $invoiceId;
            $payment->date = date('Y-m-d', $order->ts != '0000-00-00 00:00:00' ? strtotime($order->ts) : time());
            $payment->amount = $totalAmount;
            $payment->type = 'Paypal';
            $payment->notes = 'Imported; PayPal Transaction Id: ' . $order->paypal_transactionid;
            $payment->create();
        }
        if ($send) {
            $config = BConfig::i()->get('modules/FCom_FreshBooks/email');
            $invoice->subject = $config['subject'];
            $invoice->message = $config['message'];

            $invoice->sendByEmail();
        }
        error_reporting($er);
    }
}
