<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BLocale_Test extends PHPUnit_Framework_TestCase
{
    public function testSetLanguage()
    {
        $lang = 'de_DE';
        $this->BLocale->setCurrentLanguage($lang);
        $this->assertEquals($lang, $this->BLocale->getCurrentLanguage());
    }

    public function testTransliterateAlgorithm()
    {
        $str = "aBc dEf 123_[]$%";
        $this->assertEquals("abc-def-123", $this->BLocale->transliterate($str));

        $str = "aBc dEf 123_[]$%";
        $this->assertEquals("abc_def_123", $this->BLocale->transliterate($str, '_'));

        $str = "aBc dEf 123_[]$% ";
        $this->assertEquals("abcrdefr123", $this->BLocale->transliterate($str, 'R'));
    }
}
