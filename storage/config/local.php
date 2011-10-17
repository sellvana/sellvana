<?php

return array(
    'db' => array('dbname'=>'fulleron', 'user'=>'web', 'logging'=>1),
    'cookie' => array('domain'=>'a.dev', 'path'=>'/fulleron', 'namespace'=>'fulleron'), //DB
    'bootstrap' => array('modules' => array('fcom.catalog')),
    'encrypt' => array('key' => '024802374c029834nc0urewybt9'),
    'debug' => array('ip' => array('127.0.0.1'=>'debug')), //DB

    'fcom.catalog' => array(

    ),

    'fcom.checkout' => array(

    ),

    'fcom.freshbooks' => array(
        'api' => array(
            'url' => 'https://unirgy.freshbooks.com/api/2.1/xml-in', //DB (Company part)
            'key' => '667c6e62b92e85dd9fd9112163394e4d' //DB (encrypted)
        ),
        'email' => array(
            'autosend' => true, //DB (def)
            'subject' => 'Invoice #::invoice number:: for your records  (this is not a bill)', //DB
            'message' => "THIS IS NOT A BILL.\n\nTo view your invoice ::invoice number:: from Unirgy LLC for ::payment amount::, or to download a PDF copy for your records, click the link below:\n\n::invoice link::", //DB
        ),
        'order' => array(
            'status.collect' => 'pending', //DB (def)
            'status.set' => 'invoiced', //DB (def)
        ),
    ),

    'fcom.paypal' => array(
        'production' => array(
            'web' => array(
                'url' => 'https://www.paypal.com/cgi-bin/', //DB (def)
            ),
            'api' => array(
                'url' => 'https://api-3t.paypal.com/nvp', //DB (def)
                'username' => 'paypal_api1.unirgy.com', //DB
                'password' => 'F3NZNHDQ9369Q29H', //DB (encrypted)
                'signature' => 'Aw93-4ljEKoS7CBLEeEJe9JJpdkGAOsltfNpiQWWsj8uBcIwtX.IsoGv', //DB (encrypted)
            ),
        ),
        'sandbox' => array(
            'ip' => '', //DB
            'web' => array(
                'url' => 'https://www.sandbox.paypal.com/', //DB (def)
            ),
            'api' => array(
                'url' => 'https://api-3t.sandbox.paypal.com/nvp', //DB (def)
                'username' => 'm0sh3g_1237535915_biz_api1.gmail.com', //DB
                'password' => '1237536002', //DB (encrypted)
                'signature' => 'ALBf.y66AU1jYhtcAmEPnWSft2J7Ad1OQnziH.yZQZAL1NgegB4HyHT0', //DB (encrypted)
            ),
        ),
    ),
);