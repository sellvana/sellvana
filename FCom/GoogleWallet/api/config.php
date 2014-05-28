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
 *
 * The Config file holds configuration constants for the sample JWT server
 * change these values to ones for your setup before using the sample.
 * You get MERCHANT_ID, MERCHANT_SECRET after the sign up process, the CLIENT_ID is your
 * client ID that you created on the Google API console for this project.
 * FINGERPRINT the SHA-1 fingerprint of the APK signing certficate.
 * ORIGIN is the name of the package (for the sample leave it unchanged)
 */


  define('MERCHANT_ID', "your_id_goes_here");

  define('MERCHANT_SECRET', "your_merchant_secret");

  define('MERCHANT_NAME', "name_with_which_you_signed_up_merchant_account");

  define('CLIENT_ID',  "your_client_id_from_api_console");

  define('ORIGIN', "http://localhost");

