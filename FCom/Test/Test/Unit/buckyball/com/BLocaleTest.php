<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BLocale_Test extends PHPUnit_Framework_TestCase
{
    public function testSetLanguage()
    {
        $lang = 'de_DE';
        BLocale::i()->setCurrentLanguage($lang);
        $this->assertEquals($lang, BLocale::i()->getCurrentLanguage());
    }

    public function testTransliterateAlgorithm()
    {
        $str = "aBc dEf 123_[]$%";
        $this->assertEquals("abc-def-123", BLocale::i()->transliterate($str));

        $str = "aBc dEf 123_[]$%";
        $this->assertEquals("abc_def_123", BLocale::i()->transliterate($str, '_'));

        $str = "aBc dEf 123_[]$% ";
        $this->assertEquals("abcrdefr123", BLocale::i()->transliterate($str, 'R'));
    }
}
