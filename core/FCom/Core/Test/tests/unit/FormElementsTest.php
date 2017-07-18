<?php

class EmbedTest extends \Codeception\TestCase\Test
{
    /**
     * @var \FCom\Core\UnitTester
     */
    protected $tester;

    /**
     * @var FCom_Core_View_FormElements $form
     */
    protected $form;

    protected function _before()
    {
        $this->form = FCom_Core_View_FormElements::i();
    }

    protected function _after()
    {
    }

    public function testGetOptionsWithEmptyItem()
    {
        $options = [
            'add_empty' => true,
            'options' => [
                'key' => 'value'
            ]
        ];

        $options = $this->form->getOptions($options);
        $this->tester->assertTrue(array_key_exists('', $options), 'Can not jnject empty option.');
        $this->tester->assertNotContains('add_empty', $options, 'Do not exlude add_empty key.');
    }

    public function testGetInputId()
    {
        $p = [
            'id' => 'input-test-id',
            'field' => '',
            'settings_module' => '',
            'id_prefix' => ''
        ];
        $r = $this->form->getInputId($p);

        $this->tester->assertEquals('input-test-id', $r, 'Input id is not correct');

        $p['id'] = '';
        $r = $this->form->getInputId($p);
        $this->tester->assertNotEquals('input-test-id', $r, 'Input id is not correct');

        $p['settings_module'] = (('Test'));
        $p['field'] = 'product';
        $r = $this->form->getInputId($p);
        $this->tester->assertRegExp('/^[A-z]+\-(Test)\-[A-z]+/', $r, 'Input id is not correct');

        $p['settings_module'] = '';
        $p['id_prefix'] = 'fcom';
        $r = $this->form->getInputId($p);
        $this->tester->assertRegExp('/^(fcom)\-[A-z]+/', $r, 'Input id is not correct');

        $p['id_prefix'] = '';
        $r = $this->form->getInputId($p);
        $this->tester->assertRegExp('/^(model)\-[A-z]+/', $r, 'Input id is not correct');

    }

    public function testGetInputName()
    {
        $p = [
            'name' => 'input-test-name',
            'field' => '',
            'settings_module' => '',
            'name_prefix' => ''
        ];
        $r = $this->form->getInputName($p);
        $this->tester->assertEquals('input-test-name', $r, 'Input name is not correct');

        $p['name'] = '';
        $r = $this->form->getInputName($p);
        $this->tester->assertNotEquals('input-test-name', $r, 'Input name is not correct');

        $p['settings_module'] = (('Test'));
        $p['field'] = 'product';
        $r = $this->form->getInputName($p);
        $this->tester->assertRegExp('/^[A-z\[]+(Test)\]\[[A-z]+\]/', $r, 'Input name is not correct');

        $p['settings_module'] = '';
        $p['name_prefix'] = 'fcom';
        $r = $this->form->getInputName($p);
        $this->tester->assertRegExp('/^(fcom)\[[A-z]+\]/', $r, 'Input name is not correct');

        $p['name_prefix'] = '';
        $r = $this->form->getInputName($p);
        $this->tester->assertRegExp('/^(model)\[[A-z]+\]/', $r, 'Input name is not correct');

        $p['multiple'] = true;
        $p['name_prefix'] = 'test';
        $r = $this->form->getInputName($p);
        $this->tester->assertRegExp('/^(test)\[[A-z]+\]\[\]/', $r, 'Input name is not correct');
    }

    public function testGetInputValue()
    {

        $p = [
            'value' => 'input-test-value',
            'field' => '',
            'model' => '',
            'settings_module' => '',
            'get_prefix' => ''
        ];
        $r = $this->form->getInputValue($p);
        $this->tester->assertEquals('input-test-value', $r, 'Input value is not correct');

        unset($p['value']);
        $p['field'] = 'base';
        $p['settings_module'] = 'catalog';
        $p['model'] = Sellvana_Catalog_Model_Product::class;
        $r = $this->form->getInputValue($p);
        $this->tester->assertNotRegExp('/[A-z\[\]]+\[('.$p['settings_module'].')\]\['.$p['field'].'\]/', $r, 'Input value is not correct');

        $this->tester->haveInDatabase('fcom_product', [
            'id' => '1',
            'product_name' => (('Product 1')),
            'product_sku' => 'test-1',
            'url_key' => 'product-1'
        ]);

        $p['settings_module'] = '';
        $p['field'] = 'product_name';
        $p['model'] = Sellvana_Catalog_Model_Product::i()->load(1);
        $r = $this->form->getInputValue($p);
        $this->tester->assertEquals('Product 1', $r, 'Input value is not correct');

        $p['settings_module'] = '';
        $p['model'] = Sellvana_Catalog_Model_Product::class;
        $r = $this->form->getInputName($p);
        $this->tester->assertNotEmpty($r, 'Input value is not correct');
        $this->tester->assertRegExp('/[A-z\[]+\[('.$p['field'].')\]$/', $r, 'Input value is not correct');
    }
}