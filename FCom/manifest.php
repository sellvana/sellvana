<?php return array(
    'modules' => array(
        // Core module
        'FCom_Core'=>array(
            'version' => '0.1.0',
            'root_dir' => 'Core',
            'bootstrap' => array('file'=>'Core.php', 'callback'=>'FCom_Core::bootstrap'),
            'run_level' => 'REQUIRED',
            'migrate' => 'FCom_Core_Migrate',
            'description' => "Base Fulleron classes and JS libraries",
        ),
        // Initial installation module
        'FCom_Install' => array(
            'version' => '0.1.0',
            'root_dir' => 'Install',
            'bootstrap' => array('file'=>'Install.php', 'callback'=>'FCom_Install::bootstrap'),
            'depends' => array('FCom_Core'),
            'description' => "Initial installation wizard",
        ),
        // API area
        'FCom_Api' => array(
            'version' => '0.1.0',
            'root_dir' => 'Api',
            'bootstrap' => array('file'=>'Api.php', 'callback'=>'FCom_Api::bootstrap'),
            'depends' => array('FCom_Core'),
            'description' => "API area",
        ),
        // Frontend collection of modules
        'FCom_Frontend' => array(
            'version' => '0.1.0',
            'root_dir' => 'Frontend',
            'bootstrap' => array('file'=>'Frontend.php', 'callback'=>'FCom_Frontend::bootstrap'),
            'depends' => array('FCom_Core', 'FCom_Frontend_DefaultTheme'),
            'description' => "Base frontend functionality",
        ),
        // Frontend collection of modules
        'FCom_Frontend_DefaultTheme' => array(
            'version' => '0.1.0',
            'root_dir' => 'Frontend',
            'bootstrap' => array('file'=>'DefaultTheme.php', 'callback'=>'FCom_Frontend_DefaultTheme::bootstrap'),
            'depends' => array('FCom_Core'),
            'description' => "Default frontend theme",
            'provides' => array('theme' => 'FCom_Frontend_DefaultTheme'),
        ),
        // administration panel views and controllers
        'FCom_Admin' => array(
            'version' => '0.1.2',
            'root_dir' => 'Admin',
            'bootstrap' => array('file'=>'Admin.php', 'callback'=>'FCom_Admin::bootstrap'),
            'depends' => array('FCom_Core', 'FCom_Admin_DefaultTheme'),
            'migrate' => 'FCom_Admin_Migrate',
            'description' => "Base admin functionality",
        ),
        // Frontend collection of modules
        'FCom_Admin_DefaultTheme' => array(
            'version' => '0.1.0',
            'root_dir' => 'Admin',
            'bootstrap' => array('file'=>'DefaultTheme.php', 'callback'=>'FCom_Admin_DefaultTheme::bootstrap'),
            'depends' => array('FCom_Core'),
            'description' => "Default admin theme",
            'provides' => array('theme' => 'FCom_Admin_DefaultTheme'),
        ),
        // cron jobs processing
        'FCom_Cron' => array(
            'version' => '0.1.0',
            'root_dir' => 'Cron',
            'depends' => array('FCom_Core'),
            'migrate' => 'FCom_Cron_Migrate',
            'description' => "Cron scheduled tasks manager",
            'areas' => array(
                'FCom_Cron' => array(
                    'bootstrap' => array('file'=>'Cron.php', 'callback'=>'FCom_Cron::bootstrap'),
                ),
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CronAdmin.php', 'callback'=>'FCom_Cron_Admin::bootstrap'),
                ),
            ),
        ),
        // FCom_Market description here
        'FCom_Market' => array(
            'version' => '0.1.0',
            'root_dir' => 'Market',
            'bootstrap' => array('file'=>'Market.php', 'callback'=>'FCom_Market::bootstrap'),
            'depends' => array('FCom_Core'),
            'description' => "FCom_Market description here",
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'MarketAdmin.php', 'callback'=>'FCom_Market_Admin::bootstrap'),
                ),
            ),
        ),
        // Geographic information about countries and states
        'FCom_Geo' => array(
            'version' => '0.1.0',
            'root_dir' => 'Geo',
            'migrate' => array('file'=>'Geo.php', 'callback'=>'FCom_Geo::migrate'),
            'bootstrap' => array('file'=>'Geo.php', 'callback'=>'FCom_Geo::bootstrap'),
            'depends' => array('FCom_Core'),
            'description' => "Geographic information about countries and states",
        ),
        // catalog views and controllers
        'FCom_Cms' => array(
            'version' => '0.1.1',
            'root_dir' => 'Cms',
            'depends' => array('FCom_Core', 'BPHPTAL'),
            'description' => "CMS for custom pages and forms",
            'bootstrap' => array('file'=>'CmsFrontend.php', 'callback'=>'FCom_Cms_Frontend::bootstrap'),
            'migrate' => 'FCom_Cms_Migrate',
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CmsAdmin.php', 'callback'=>'FCom_Cms_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CmsFrontend.php', 'callback'=>'FCom_Cms_Frontend::bootstrap'),
                ),
            ),
        ),
        // product reviews
        'FCom_ProductReviews' => array(
            'version' => '0.1.0',
            'root_dir' => 'ProductReviews',
            'depends' => array('FCom_Catalog', 'FCom_Customer'),
            'description' => "Product reviews by customers",
            'bootstrap' => array('file'=>'ProductReviewsFrontend.php', 'callback'=>'FCom_ProductReviews_Frontend::bootstrap'),
            'migrate' => 'FCom_ProductReviews_Migrate',
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'ProductReviewsAdmin.php', 'callback'=>'FCom_ProductReviews_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'ProductReviewsFrontend.php', 'callback'=>'FCom_ProductReviews_Frontend::bootstrap'),
                ),
            ),
        ),
        // catalog views and controllers
        'FCom_Catalog' => array(
            'version' => '0.1.2',
            'root_dir' => 'Catalog',
            'depends' => array('FCom_Core'),
            'description' => "Categories and products management, admin and frontend",
            'migrate' => 'FCom_Catalog_Migrate',
            //'bootstrap' => array('file'=>'CatalogFrontend.php', 'callback'=>'FCom_Catalog_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CatalogAdmin.php', 'callback'=>'FCom_Catalog_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CatalogFrontend.php', 'callback'=>'FCom_Catalog_Frontend::bootstrap'),
                ),
            ),
        ),
        // customer account and management
        'FCom_Customer' => array(
            'version' => '0.1.2',
            'root_dir' => 'Customer',
            'depends' => array('FCom_Core'),
            'description' => "Customer Accounts and Management",
            'migrate' => 'FCom_Customer_Migrate',
            'bootstrap' => array('file'=>'CustomerFrontend.php', 'callback'=>'FCom_Customer_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Api' => array(
                    'bootstrap' => array('file'=>'Api.php', 'callback'=>'FCom_Customer_Api::bootstrap'),
                ),
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CustomerAdmin.php', 'callback'=>'FCom_Customer_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CustomerFrontend.php', 'callback'=>'FCom_Customer_Frontend::bootstrap'),
                ),
            ),
        ),
        // catalog views and controllers
        'FCom_CustomField' => array(
            'version' => '0.1.1',
            'root_dir' => 'CustomField',
            'bootstrap' => array('file'=>'CustomField.php', 'callback'=>'FCom_CustomField::bootstrap'),
            'depends' => array('FCom_Catalog'),
            'after' => array('FCom_Customer'),
            'description' => "Base custom fields implementation, currently for catalog only",
            'migrate' => 'FCom_CustomField_Migrate',
            'bootstrap' => array('file'=>'CustomFieldFrontend.php', 'callback'=>'FCom_CustomField_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CustomFieldAdmin.php', 'callback'=>'FCom_CustomField_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CustomFieldFrontend.php', 'callback'=>'FCom_CustomField_Frontend::bootstrap'),
                ),
            ),
        ),
        // cart, checkout and customer account views and controllers
        'FCom_Checkout' => array(
            'version' => '0.1.6',
            'root_dir' => 'Checkout',
            'migrate' => 'FCom_Checkout_Migrate',
            'depends' => array('FCom_Catalog'),
            'bootstrap' => array('file'=>'Checkout.php', 'callback'=>'FCom_Checkout::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'CheckoutAdmin.php', 'callback'=>'FCom_Checkout_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CheckoutFrontend.php', 'callback'=>'FCom_Checkout_Frontend::bootstrap'),
                ),
            ),
            'description' => "Base cart and checkout functionality",
        ),
        'FCom_Sales' => array(
            'version' => '0.1.0',
            'root_dir' => 'Sales',
            'migrate' => 'FCom_Sales_Migrate',
            'bootstrap' => array('file'=>'Sales.php', 'callback'=>'FCom_Sales::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'SalesAdmin.php', 'callback'=>'FCom_Sales_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'SalesFrontend.php', 'callback'=>'FCom_Sales_Frontend::bootstrap'),
                ),
            ),
            'description' => "Sales module",
        ),
        'FCom_ShippingPlain' => array(
            'version' => '0.1.0',
            'root_dir' => 'ShippingPlain',
            'depends' => array('FCom_Checkout'),
            'bootstrap' => array('file'=>'ShippingPlain.php', 'callback'=>'FCom_ShippingPlain::bootstrap'),
            'areas' => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'ShippingPlain.php', 'callback'=>'FCom_ShippingPlain::bootstrap'),
                ),
            ),
            'description' => "Plain shipping module for checkout",
        ),
        'FCom_ShippingUps' => array(
            'version' => '0.1.0',
            'root_dir' => 'ShippingUps',
            'depends' => array('FCom_Checkout'),
            'bootstrap' => array('file'=>'Ups.php', 'callback'=>'FCom_ShippingUps_Ups::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'ShippingUpsAdmin.php', 'callback'=>'FCom_ShippingUps_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'ShippingUpsFrontend.php', 'callback'=>'FCom_ShippingUps_Frontend::bootstrap'),
                ),
            ),
            'description' => "Universal post service shipping module for checkout",
        ),
        'FCom_Wishlist' => array(
            'version' => '0.1.0',
            'root_dir' => 'Wishlist',
            'migrate' => 'FCom_Wishlist_Migrate',
            'depends' => array('FCom_Catalog', 'FCom_Customer'),
            'bootstrap' => array('file'=>'Wishlist.php', 'callback'=>'FCom_Wishlist::bootstrap'),
            'areas' => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'WishlistFrontend.php', 'callback'=>'FCom_Wishlist_Frontend::bootstrap'),
                ),
            ),
            'description' => "Wishlist functionality",
        ),
        'FCom_Email' => array(
            'version' => '0.1.0',
            'root_dir' => 'Email',
            'depends' => array('FCom_Core'),
            'description' => "Base email subscription and mailing list management",
            'bootstrap' => array('file'=>'EmailFrontend.php', 'callback'=>'FCom_Email_Frontend::bootstrap'),
            'migrate' => 'FCom_Email_Migrate',
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'EmailAdmin.php', 'callback'=>'FCom_Email_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'EmailFrontend.php', 'callback'=>'FCom_Email_Frontend::bootstrap'),
                ),
            ),
        ),
        // paypal IPN
        'FCom_PayPal' => array(
            'version' => '0.1.0',
            'root_dir' => 'PayPal',
            'depends' => array('FCom_Core'),
            'description' => "PayPal&reg; standard payment method",
            'bootstrap' => array('file'=>'PayPalFrontend.php', 'callback'=>'FCom_PayPal_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'PayPalAdmin.php', 'callback'=>'FCom_PayPal_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'PayPalFrontend.php', 'callback'=>'FCom_PayPal_Frontend::bootstrap'),
                ),
            ),
        ),
        'FCom_Disqus' => array(
            'version' => '0.1.0',
            'root_dir' => 'Disqus',
            'depends' => array('FCom_Core'),
            'translations' => array('ru_RU.UTF-8' => 'ru.json', 'es_ES.UTF-8' => 'es.csv', 'gr_GR.UTF-8' => array('gr.php')),
            'description' => "Disqus comments",
            'bootstrap' => array('file'=>'DisqusFrontend.php', 'callback'=>'FCom_Disqus_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'DisqusAdmin.php', 'callback'=>'FCom_Disqus_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'DisqusFrontend.php', 'callback'=>'FCom_Disqus_Frontend::bootstrap'),
                ),
            ),
        ),
        // test CreditCard module
        'FCom_CreditCard' => array(
            'version' => '0.1.0',
            'root_dir' => 'CreditCard',
            'depends' => array('FCom_Core'),
            'description' => "Credit Card test module",
            'bootstrap' => array('file'=>'CreditCardFrontend.php', 'callback'=>'FCom_CreditCard_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'CreditCardFrontend.php', 'callback'=>'FCom_CreditCard_Frontend::bootstrap'),
                ),
            ),
        ),
        // translations Admin module
        'FCom_Translation' => array(
            'version' => '0.1.0',
            'root_dir' => 'Translation',
            'depends' => array('FCom_Core'),
            'description' => "Translations module",
            'bootstrap' => array('file'=>'Translation.php', 'callback'=>'FCom_Translation::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'TranslationAdmin.php', 'callback'=>'FCom_Translation_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'TranslationFrontend.php', 'callback'=>'FCom_Translation_Frontend::bootstrap'),
                ),
            ),
        ),
        // freshbook simple invoicing
        'FCom_FreshBooks' => array(
            'version' => '0.1.0',
            'root_dir' => 'FreshBooks',
            'depends' => array('FCom_Core'),
            'description' => "FreshBooks&reg; payment method and invoice management API integration",
            'bootstrap' => array('file'=>'FreshBooksFrontend.php', 'callback'=>'FCom_FreshBooks_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'FreshBooksAdmin.php', 'callback'=>'FCom_FreshBooks_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'FreshBooksFrontend.php', 'callback'=>'FCom_FreshBooks_Frontend::bootstrap'),
                ),
            ),
        ),
        // IndexTank integration
        'FCom_IndexTank' => array(
            'version' => '0.1.1',
            'root_dir' => 'IndexTank',
            'depends' => array('FCom_Core'),
            'description' => "IndexTank API integration",
            'migrate' => 'FCom_IndexTank_Migrate',
            'tests' => "FCom_IndexTank_Tests_AllTests",
            'bootstrap' => array('file'=>'IndexTankFrontend.php', 'callback'=>'FCom_IndexTank_Frontend::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'IndexTankAdmin.php', 'callback'=>'FCom_IndexTank_Admin::bootstrap'),
                    'depends' => array('BGanon'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'IndexTankFrontend.php', 'callback'=>'FCom_IndexTank_Frontend::bootstrap'),
                    //'depends' => array('FCom_Frontend'),
                ),
                "FCom_Cron" => array(
                    "bootstrap" => array("file" => "IndexTankCron.php", "callback" => "FCom_IndexTank_Cron::bootstrap")
                )
            ),
        ),
        // PHPUnit Tests integration
        'FCom_Test' => array(
            'version' => '0.1.0',
            'root_dir' => 'Test',
            'depends' => array('FCom_Core'),
            'description' => "PHPUnit tests integration",
            'bootstrap' => array('file'=>'Test.php', 'callback'=>'FCom_Test::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'TestAdmin.php', 'callback'=>'FCom_Test_Admin::bootstrap')
                ),
            ),
        ),
    ),
);