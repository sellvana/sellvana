<?php

class BLocale_Test extends PHPUnit_Framework_TestCase
{
    public function testSetLanguage()
    {
        $lang = 'de_DE';
        BLocale::setCurrentLanguage($lang);
        $this->assertEquals($lang, BLocale::getCurrentLanguage());
    }
}