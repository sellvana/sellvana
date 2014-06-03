<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class FCom_AuthorizeNet_Model_Settings extends BClass
{
    protected static $gatewayUrl = "https://secure.authorize.net/gateway/transact.dll";
    protected static $testGatewayUrl = "https://test.authorize.net/gateway/transact.dll";

    public static $ccTransactions = [
        "AUTH_CAPTURE",
        "AUTH_ONLY",
        "CAPTURE_ONLY",
        "CREDIT", // for refunds
        "PRIOR_AUTH_CAPTURE", // capture after auth
        "VOID" // void payment
    ];

    protected static $reqFields = [
        "x_login",
        "x_tran_key",
        "x_type",
        "x_amount", // Up to 15 digits with a decimal point (no dollar symbol).
        "x_card_num", // Between 13 and 16 digits without spaces. When x_type=CREDIT, only the last four digits are required
        "x_exp_date", // Format: MMYY, MM/YY, MM-YY, MMYYYY, MM/YYYY, MMYYYY
        "x_relay_response",
        "x_delim_data",
        "x_version",
        "x_delim_char", // single character
    ];

    protected static $reqFieldsCapture = [
        "x_auth_code", // Required only for CAPTURE_ONLY transactions;Format: 6 characters
    ];

    protected static $reqFieldsAuthPrior = [
        "x_trans_id", // Required only for CREDIT, PRIOR_ AUTH_ CAPTURE, and VOID transactions
        "x_split_tender_id",
    ];

    protected static $otherFields = [
        "x_allow_partial_auth",
        "x_prepaid_balance_on_card",
        "x_prepaid_requested_amount",
        "x_card_type",
        "x_method", //CC or ECHECK
        "x_recurring_billing",
        "x_currency_code", // USD, CAD, EUR, or GBP
        "x_card_code", // The three- or four-digit number on the back of a credit card (on the front for American Express).
        "x_test_request", // TRUE, FALSE, T, F, YES, NO, Y, N, 1, 0
        "x_duplicate_window", // Format: Any value between 0 and 28800 (no comma)
        "x_invoice_num", // Format: Up to 20 characters (no symbols)
        "x_description", // Format: Up to 255 characters (no symbols)
        "x_line_item", // capable of including delimited item information. Item Information must be delimited by a bracketed pipe <|>.
        //Line item values must be included in specific order. The merchant can submit up to 30 distinct line items
        // Item ID<|>Item Name<|>Item Description<|>Item Quantity<|>Item Price<|>Item Taxable
        // Up to 31 characters<|>Up to 31 characters<|>Up to 255 characters<|>Up to two decimal places<|>Up to two decimal places<|>TRUE, FALSE, T, F, YES, NO, Y, N, 1, 0
        // x_line_item=item3<|>book<|>Golf for Dummies<|>1<|>21.99<|>Y
        "x_first_name", // Format: Up to 50 characters (no symbols)
        "x_last_name", // Format: Up to 50 characters (no symbols)
        "x_company", // Format: Up to 50 characters (no symbols)
        "x_address", // Format: Up to 60 characters (no symbols)
        // Required only when using a European Payment Processor.
        // Required if the merchant would like to use the Address Verification Service security feature.
        "x_city", // Format: Up to 40 characters (no symbols)
        // Required only when using a European Payment Processor.
        "x_state", // Format: Up to 40 characters (no symbols) or a valid two-character state code
        // Required only when using a European Payment Processor.
        "x_zip", // Format: Up to 20 characters (no symbols)
        // Required only when using a European Payment Processor.
        // Required if the merchant would like to use the Address Verification Service security feature.
        "x_country", // Format: Up to 60 characters (no symbols)
        // Required only when using a European Payment Processor.
        "x_phone", // Format: Up to 25 digits (no letters). For example, (123)123-1234
        "x_fax", // Format: Up to 25 digits (no letters). For example, (123)123-1234
        "x_email", // Format: Up to 255 characters. For example, janedoe@customer.com
        "x_cust_id", // Format: Up to 20 characters (no symbols)
        "x_customer_ip", // Format: Up to 15 characters (no letters). For example, 255.255.255.255
        // This field is required when using customer-IP based Advanced Fraud
        "x_ship_to_first_name", // Format: Up to 50 characters (no symbols)
        "x_ship_to_last_name", // Format: Up to 50 characters (no symbols)
        "x_ship_to_company", // Format: Up to 50 characters (no symbols)
        "x_ship_to_address", // Format: Up to 60 characters (no symbols)
        "x_ship_to_city", // Format: Up to 40 characters (no symbols)
        "x_ship_to_state", // Format: Up to 40 characters (no symbols) or a valid two-character
        "x_ship_to_zip", // Format: Up to 20 characters (no symbols)
        "x_ship_to_country", // Format: Up to 60 characters (no symbols)
        "x_tax", // valid tax amount or the delimited tax information, delimited by a bracketed pipe <|>
        // Example: x_tax=Tax1<|>state tax<|>0.09
        //          x_tax=Name<|>description<|>amnt
        "x_freight", // either the valid freight amount, or delimited freight information, delimited by a bracketed pipe <|>
        // Example: x_freight=Freight<|>ground overnight<|>12.95
        "x_duty", // Example: x_duty=Duty1<|>export<|>15.00; valid duty amount or delimited duty information.
        "x_tax_exempt", // tax exempt status of the order; TRUE, FALSE, T, F, YES, NO, Y, N, 1, 0
        "x_po_num", // merchant-assigned purchase order number, up to 25 characters, no symbols.
        "x_encap_char", // Value: The encapsulating character
        "x_header_email_receipt", //
        "x_footer_email_receipt", //
        "x_merchant_email", //
        "", //
    ];

    public function paymentActions()
    {
        return [
            "AUTH_ONLY"         => BLocale::i()->_("Authorize Only"),
            "AUTH_CAPTURE" => BLocale::i()->_("Authorize and Capture")
        ];
    }
/*

 */
    public function cardTypes()
    {
        return [
            "AE" => "American Express",
            "VI" => "Visa",
            "MC" => "MasterCard",
            "DI" => "Discover",
            "DC" => "Diners Club",
            "JC" => "JCB",
            "OT" => BLocale::i()->_("Other")
        ];
    }

    protected static $responseFormat = [
        1 => "Response Code",
        2 => "Response Subcode",
        3 => "Response Reason Code",
        4 => "Response Reason Text",
        5 => "Authorization Code",
        6 => "AVS Response",
        7 => "Transaction ID",
        8 => "Invoice Number",
        9 => "Description",
        10 => "Amount",
        11 => "Method",
        12 => "Transaction Type",
        13 => "Customer ID",
        14 => "First Name",
        15 => "Last Name",
        16 => "Company",
        17 => "Address",
        18 => "City",
        19 => "State",
        20 => "ZIP Code",
        21 => "Country",
        22 => "Phone",
        23 => "Fax",
        24 => "Email Address",
        25 => "Ship To First Name",
        26 => "Ship To Last Name",
        27 => "Ship To Company",
        28 => "Ship To Address",
        29 => "Ship To City",
        30 => "Ship To State",
        31 => "Ship To ZIP Code",
        32 => "Ship To Country",
        33 => "Tax",
        34 => "Duty",
        35 => "Freight",
        36 => "Tax Exempt",
        37 => "Purchase Order Number",
        38 => "MD5 Hash",
        39 => "Card Code Response",
        40 => "Cardholder Authentication Verification",
        51 => "Account Number",
        52 => "Card Type",
        53 => "Split Tender ID",
        54 => "Requested Amount",
        55 => "Balance On Card"
    ];

    public function countries()
    {
        $countries = [];
        foreach ($this->FCom_Geo_Model_Country->options() as $iso => $name) {
            if (empty($iso)) {
                continue;
            }
            $countries[$iso] = $name;
        }

        return $countries;
    }

    public function currencies()
    {
        // todo - update to be dynamically built
        return [
            "-"   => "-- Select One --",
            "usd" => "USD"
        ];
    }

    public function orderStatuses()
    {
        // todo - update to be dynamically built
        return [
            "-"          => "-- Select One --",
            "processing" => "Processing"
        ];
    }

    /**
     * @param BConfig $config
     * @return string
     */
    public function gatewayUrl($config)
    {
        $url = static::$gatewayUrl;
        if ($config->get('modules/FCom_AuthorizeNet/cgi_url')) {
            $url = $config->get('modules/FCom_AuthorizeNet/cgi_url');
        }
        return $url;
    }

    /**
     * @param BConfig $config
     * @return string
     */
    public function gatewayDpmUrl($config)
    {
        return $this->gatewayUrl($config);
    }
}
