<?php

return array(
    'db' => array('dbname'=>'fulleron', 'user'=>'web', 'logging'=>1),
    'cookie' => array('domain'=>'a.dev', 'path'=>'/fulleron', 'namespace'=>'fulleron'),

    'fcom.catalog' => array(

    ),

    'fcom.checkout' => array(

    ),

    'fcom.freshbooks' => array(
        'api' => array(
            'url' => 'https://unirgy.freshbooks.com/api/2.1/xml-in',
            'key' => '667c6e62b92e85dd9fd9112163394e4d'
        ),
        'email' => array(
            'autosend' => true,
            'subject' => 'Invoice #::invoice number:: for your records  (this is not a bill)',
            'message' => "THIS IS NOT A BILL.\n\nTo view your invoice ::invoice number:: from Unirgy LLC for ::payment amount::, or to download a PDF copy for your records, click the link below:\n\n::invoice link::",
        ),
        'order' => array(
            'status.collect' => 'pending',
            'status.set' => 'invoiced',
        ),
    ),

    'fcom.paypal' => array(
        'production' => array(
            'web' => array(
                'url' => 'https://www.paypal.com/cgi-bin/',
            ),
            'api' => array(
                'url' => 'https://api-3t.paypal.com/nvp',
                'username' => 'paypal_api1.unirgy.com',
                'password' => 'F3NZNHDQ9369Q29H',
                'signature' => 'Aw93-4ljEKoS7CBLEeEJe9JJpdkGAOsltfNpiQWWsj8uBcIwtX.IsoGv',
            ),
        ),
        'sandbox' => array(
            'ip' => '',
            'web' => array(
                'url' => 'https://www.sandbox.paypal.com/',
            ),
            'api' => array(
                'url' => 'https://api-3t.sandbox.paypal.com/nvp',
                'username' => 'm0sh3g_1237535915_biz_api1.gmail.com',
                'password' => '1237536002',
                'signature' => 'ALBf.y66AU1jYhtcAmEPnWSft2J7Ad1OQnziH.yZQZAL1NgegB4HyHT0',
            ),
        ),
    ),
);