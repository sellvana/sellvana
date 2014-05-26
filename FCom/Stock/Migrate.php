<?php

class FCom_Stock_Migrate extends BClass
{
    public function install__0_1_2()
    {
        $tSku = FCom_Stock_Model_Sku::table();
        $tBin = FCom_Stock_Model_Bin::table();

        BDb::ddlTableDef($tBin, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'title' => 'varchar(50)',
                'description' => 'text',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
            ],
            'PRIMARY' => '(id)',
        ]);

        BDb::ddlTableDef($tSku, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'sku' => 'varchar(128) not null',
                'bin_id' => 'int unsigned null',
                'qty_in_stock' => 'int not null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_sku' => 'UNIQUE (sku)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tSku}_bin" => "FOREIGN KEY (bin_id) REFERENCES {$tBin} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ],
        ]);

        $pTable = FCom_Catalog_Model_Product::table();
        $sTable = FCom_Stock_Model_Sku::table();
        BDb::ddlTableDef($sTable, [
                'COLUMNS' => [
                    'net_weight'  => 'decimal(12,2) null default null',
                    'ship_weight' => 'decimal(12,2) null default null',
                ],
            ]
        );

        BDb::ddlTableDef($sTable, [
                'COLUMNS' => [
                    'status'  => 'TINYINT(1) not null default 0',
                ],
            ]
        );
        $productWeights = FCom_Catalog_Model_Product::orm()
            ->select(['local_sku', 'net_weight', 'ship_weight'])
            ->where(['OR' => ["`net_weight` IS NOT NULL", "`ship_weight` IS NOT NULL"]])
            ->find_many();

        if ($productWeights) {
            $prodStocks = FCom_Stock_Model_Sku::orm()->find_many_assoc('sku');
            foreach ($productWeights as $product) {
                /** @var FCom_Catalog_Model_Product $product */
                $k = $product->get('local_sku');
                if (isset($prodStocks[$k])) {
                    /** @var FCom_Stock_Model_Sku $stock */
                    $stock = $prodStocks[$k];
                    $stock->set(
                        [
                            'net_weight'  => $product->get('net_weight'),
                            'ship_weight' => $product->get('ship_weight'),
                        ]
                    )->save();
                } else {
                    FCom_Stock_Model_Sku::create(
                        [
                            'sku' => $k,
                            'qty_in_stock' => 0,
                            'net_weight'  => $product->get('net_weight'),
                            'ship_weight' => $product->get('ship_weight'),
                        ]
                    )->save();
                }
            }
        } // end if products

        try {
            BDb::ddlTableDef($pTable, [
                    'COLUMNS' => [
                        'net_weight'  => 'DROP',
                        'ship_weight' => 'DROP',
                    ]
                ]
            );
        } catch (Exception $e) {
            //TODO: fix checking for existing fields on DROP
        }
    }

    public function upgrade__0_1_0__0_1_1()
    {
        // todo move net_weight and ship_weight from fcom_product to fcom_stock_sku table
        $pTable = FCom_Catalog_Model_Product::table();
        $sTable = FCom_Stock_Model_Sku::table();
        BDb::ddlTableDef($sTable, [
                'COLUMNS' => [
                    'net_weight'  => 'decimal(12,2) null default null',
                    'ship_weight' => 'decimal(12,2) null default null',
                ],
            ]
        );

        $productWeights = FCom_Catalog_Model_Product::orm()
            ->select(['local_sku', 'net_weight', 'ship_weight'])
            ->where(['OR' => ["`net_weight` IS NOT NULL", "`ship_weight` IS NOT NULL"]])
            ->find_many();

        if ($productWeights) {
            $prodStocks = FCom_Stock_Model_Sku::orm()->find_many_assoc('sku');
            foreach ($productWeights as $product) {
                /** @var FCom_Catalog_Model_Product $product */
                $k = $product->get('local_sku');
                if (isset($prodStocks[$k])) {
                    /** @var FCom_Stock_Model_Sku $stock */
                    $stock = $prodStocks[$k];
                    $stock->set(
                          [
                              'net_weight'  => $product->get('net_weight'),
                              'ship_weight' => $product->get('ship_weight'),
                          ]
                    )->save();
                } else {
                    FCom_Stock_Model_Sku::create(
                        [
                            'sku' => $k,
                            'qty_in_stock' => 0,
                            'net_weight'  => $product->get('net_weight'),
                            'ship_weight' => $product->get('ship_weight'),
                        ]
                    )->save();
                }
            }
        } // end if products

        try {
            BDb::ddlTableDef($pTable, [
                    'COLUMNS' => [
                        'net_weight'  => 'DROP',
                        'ship_weight' => 'DROP',
                    ]
                ]
            );
        } catch (Exception $e) {
            //TODO: fix checking for existing fields on DROP
        }
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $sTable = FCom_Stock_Model_Sku::table();
        BDb::ddlTableDef($sTable, [
                'COLUMNS' => [
                    'status'  => 'TINYINT(1) not null default 0',
                ],
            ]
        );
    }
}
