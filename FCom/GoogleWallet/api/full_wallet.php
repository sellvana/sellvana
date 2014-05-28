<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Copyright 2013 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 *
 * @author Nasir Khan <nasir@google.com>
 * @version 1.0
 */

require_once 'util.php';

class FullWallet {

  public static function post($input) {
    $now = (int)date('U');
    $cart_data = $input['cart'];
    $total_price = WalletUtil::to_dollars($cart_data['totalPrice']);
    $currency_code = $cart_data['currencyCode'];
    $line_items = $cart_data['lineItems'];
    for ($i = 0; $i < sizeof($line_items); $i++) {
      if (isset($line_items[$i]['totalPrice'])) {
        $line_items[$i]['totalPrice'] =
          WalletUtil::to_dollars($line_items[$i]['totalPrice']);
      }
      if (isset($line_items[$i]['unitPrice'])) {
        $line_items[$i]['unitPrice'] =
          WalletUtil::to_dollars($line_items[$i]['unitPrice']);
      }
    }

    $fwr = [
      'iat' => $now,
      'exp' => $now + 3600,
      'typ' => 'google/wallet/online/full/v2/request',
      'aud' => 'Google',
      'iss' => MERCHANT_ID,
      'request' => [
        'merchantName' => MERCHANT_NAME,
        'googleTransactionId' => $input['googleTransactionId'],
        'origin' => ORIGIN,
        'cart' => [
          'totalPrice' => $total_price,
          'currencyCode' => $currency_code,
          'lineItems' => $line_items
        ],
      ],
    ];
    $json = str_replace('\/', '/', json_encode($fwr));
    WalletUtil::encode_send_jwt($fwr);
  }
}
