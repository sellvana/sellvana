<?php

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


class WalletUtil {

  public static function log( $msg ) {
    error_log( $msg );
  }

  /**
  * We use the dollar amounts as micro dollars to keep floating arithmetic sane
  */
  public static function to_dollars( $micro_dollars ) {
    $d = floatval( $micro_dollars ) / 1000000 ;
    return number_format( $d, 2 );
  }

  public static function assert_input( $input, $required ) {
    for ( $i = 0; $i < sizeof( $required ); $i++ ) {
      if ( !isset( $input[ $required[ $i ] ] ) ) {
        header( 'HTTP/1.0 400 Bad request', true, 400 );
        echo "Did not receive $required[$i] in the request" ;
        exit();
      }
    }
  }

  public static function encode_send_jwt( $json ) {
    $jwt = JWT::encode( $json, MERCHANT_SECRET );
    header( 'Content-Type: text/plain' );
    echo $jwt;
    exit();
  }

}
