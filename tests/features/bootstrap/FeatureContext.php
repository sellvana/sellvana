<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{
    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->baseUrl = $this->getMinkParameter('base_url');
    }

    protected $baseUrl;

    /**
     * @Given /^I am logged in admin$/
     */
    public function iAmLoggedInAdmin()
    {
        $session = $this->getSession();
        $this->baseUrl = $this->getMinkParameter('base_url');
//        $session->visit($this->baseUrl);
        $session->visit($this->baseUrl . "/admin");
        $page    = $session->getPage();
        $content = $page->getContent();
        $this->assertUrlRegExp("/admin/");

//        echo $this->baseUrl;
        if (strpos($content, "Forgot your password?") === false) {
            echo "Logged in\n";
            return true;
        } else {
            echo "Have to login\n";
        }

        $userName = $page->findField('login[username]');
        $login    = $page->findField('login[password]');

        $userName->setValue('admin');
        $login->setValue('admin123');
        $page->findButton('Sign in')->press();
    }

    /**
     * @Then /^"(?P<element>[^"]*)" should be disabled$/
     */
    public function shouldBeDisabled($element)
    {
        echo $element;
        $page = $this->getSession()->getPage();
        $field = $page->findField($element);
        if(!$field){
            throw new \Behat\Mink\Exception\ElementNotFoundException($element . " not found.");
        }
        $attribute = $field->getAttribute('disabled');
        if(!$attribute){
            throw new \Exception("Not disabled.");
        }
    }

}
