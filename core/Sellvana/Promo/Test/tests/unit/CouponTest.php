<?php

class CouponTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Sellvana\Wishlist\UnitTester
     */
    protected $tester;

    /**
     * @var Sellvana_Promo_Model_PromoCoupon
     */
    protected $model;

    protected function _before()
    {
        $this->model = Sellvana_Promo_Model_PromoCoupon::i(true);
    }

    /**
     * @covers Sellvana_Promo_Model_PromoCoupon::generateCouponCode
     */
    public function testGeneratePattern()
    {
        /*
         * pattern {U8}
         */
        $pattern = '{U8}';
        $code = $this->model->generateCouponCode($pattern);
        $this->assertRegExp('/[A-Z]{8}/', $code);

        /*
         * pattern {D4}
         */
        $pattern = '{D4}';
        $code = $this->model->generateCouponCode($pattern);
        $this->assertRegExp('/[0-9]{4}/', $code);

        /*
         * pattern {UD5}
         */
        $pattern = '{UD5}';
        $code = $this->model->generateCouponCode($pattern);
        $this->assertRegExp('/[A-Z0-9]{5}/', $code);

        /*
         * pattern CODE-{U4}-{UD6}
         */
        $pattern = 'CODE-{U4}-{UD6}';
        $code = $this->model->generateCouponCode($pattern);
        $this->assertRegExp('/CODE-[A-Z]{4}-[A-Z0-9]{6}/', $code);
    }

    /**
     * @covers Sellvana_Promo_Model_PromoCoupon::generateCouponCode
     */
    public function testGeneratePatternLowerCase()
    {
        $pattern = '{u8}';
        $code = $this->model->generateCouponCode($pattern);
        $this->assertRegExp('/[A-Z]{8}/', $code);

        $pattern = '{d4}';
        $code = $this->model->generateCouponCode($pattern);
        $this->assertRegExp('/[0-9]{4}/', $code);

        $pattern = '{du5}';
        $code = $this->model->generateCouponCode($pattern);
        $this->assertRegExp('/[A-Z0-9]{5}/', $code);

        $pattern = 'CODE-{u4}-{ud6}';
        $code = $this->model->generateCouponCode($pattern);
        $this->assertRegExp('/CODE-[A-Z]{4}-[A-Z0-9]{6}/', $code);
    }

    /**
     * @covers Sellvana_Promo_Model_PromoCoupon::generateCoupons
     * @expectedException InvalidArgumentException
     */
    public function testInvalidPromoIdShouldThrowException()
    {
        $params = ['promo_id' => -1];
        $this->model->generateCoupons($params);
    }

    /**
     * @covers Sellvana_Promo_Model_PromoCoupon::generateCoupons
     */
    public function testGenerateCouponsGenerateFromPattern()
    {
        $params = [
            'promo_id' => 1,
            'pattern' => 'AB-UL-{u8}-{l8}-{d8}-{ul8}-{ud8}-{ld8}-uld',
            'uses_per_customer' => 3,
            'uses_total' => 9,
            'count' => 10
        ];
        $result = $this->model->generateCoupons($params);
        $this->assertArrayHasKey('codes', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('generated', $result);

        $this->assertNotEmpty($result['codes']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(10, $result['generated']);
        $this->assertCount(10, $result['codes']);
        $regex = '/AB-UL-[A-Z]{8}-[a-z]{8}-[0-9]{8}-[a-zA-Z]{8}-[A-Z0-9]{8}-[a-z0-9]{8}-uld/';
        foreach ($result['codes'] as $code) {
            $this->assertRegExp($regex, $code);
        }
    }

    /**
     * @covers Sellvana_Promo_Model_PromoCoupon::generateCoupons
     */
    public function testGenerateCouponsGenerateFromLength()
    {
        $l = 8;
        $params = [
            'promo_id' => 1,
            'uses_per_customer' => 3,
            'uses_total' => 9,
            'count' => 10,
            'length' => $l
        ];
        $result = $this->model->generateCoupons($params);
        $this->assertArrayHasKey('codes', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('generated', $result);

        $this->assertNotEmpty($result['codes']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(10, $result['generated']);
        $this->assertCount(10, $result['codes']);
        $regex = '/[A-Za-z0-9]{' . $l . '}/';
        foreach ($result['codes'] as $code) {
            $this->assertRegExp($regex, $code);
        }
    }

}
