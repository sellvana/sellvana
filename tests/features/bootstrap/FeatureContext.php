<?php

use Behat\Behat\Exception\PendingException;

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
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
    public function __construct( array $parameters )
    {
        $this->baseUrl = $this->getMinkParameter( 'base_url' );
    }

    protected $baseUrl;

    /**
     * @Given /^I am logged in admin$/
     */
    public function iAmLoggedInAdmin()
    {
        $session       = $this->getSession();
        $this->baseUrl = $this->getMinkParameter( 'base_url' );
//        $session->visit($this->baseUrl);
        $session->visit( $this->baseUrl . "/admin" );
        $page    = $session->getPage();
        $content = $page->getContent();

//        echo $this->baseUrl;
        if ( strpos( $content, "Forgot your password?" ) === false ) {
            echo "Logged in\n";
            return true;
        } else {
            echo "Have to login\n";
        }

        $userName = $page->findField( 'login[username]' );
        $login    = $page->findField( 'login[password]' );

        $userName->setValue( 'admin' );
        $login->setValue( 'admin123' );
        $page->findButton( 'Sign in' )->press();
    }

    /**
     * @Given /^I am not logged in$/
     */
    public function iAmNotLoggedIn()
    {
        $session       = $this->getSession();
        $this->baseUrl = $this->getMinkParameter( 'base_url' );
//        $session->visit($this->baseUrl);
        $session->visit( $this->baseUrl . "/admin" );
        $page    = $session->getPage();
        $content = $page->getContent();
        if ( strpos( $content, "Forgot your password?" ) === false ) {
            echo "Logged in\n";

            $session->visit( $this->baseUrl . "/admin/logout" );
        }
    }

    /**
     * @Then /^"(?P<element>[^"]*)" should be disabled$/
     */
    public function shouldBeDisabled( $element )
    {
        echo $element;
        $page  = $this->getPage();
        $field = $page->findField( $element );
        if ( !$field ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, $element );
        }
        $attribute = $field->getAttribute( 'disabled' );
        if ( !$attribute ) {
            throw new \Exception( "Not disabled." );
        }
    }

    /**
     * @Given /^I click "(?P<element>[^"]*)"$/
     */
    public function iClick( $element )
    {

        echo $element;
        $page  = $this->getPage();
        $field = $page->find( 'xpath', $element );
        if ( !$field ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, $element );
        }
        $field->click();
    }

    /**
     * @Given /^I go to first available category$/
     */
    public function iGoToFirstAvailableCategory()
    {
        $cat = '//nav[contains(@class,"f-catalog-navbar")]//li[contains(@class,"dropdown")][1]/a';

        $page  = $this->getPage();
        $field = $page->find( 'xpath', $cat );
        if ( !$field ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, $cat );
        }
        $this->firstCat = $field->getText();
        $field->click();
    }

    protected $firstCat;

    /**
     * @Then /^I should see its name$/
     */
    public function iShouldSeeItsName()
    {
        if ( !isset( $this->firstCat ) ) {
            throw new ExpectationException( "First cat name not found", $this->getSession() );
        }
        $page = $this->getPage();
        if ( strpos( $page->find( 'css', 'div.f-page-title' ), $this->firstCat ) !== null ) {
            echo "\t{$this->firstCat} found\n";
        } else {
            throw new ExpectationException( "{$this->firstCat} text not found", $this->getSession() );
        }
    }

    /**
     * @When /^I click first filter$/
     */
    public function iClickFirstFilter()
    {
        $path = "//section[contains(@class,'f-prod-listing-filter')]/form/dl/dd[2]/ul/li[1]/a";
        $page = $this->getPage();
        $link = $page->find( 'xpath', $path );

        if ( !$link ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, $path );
        }

        $count = $link->find( 'css', '.count' );

        if ( !$count ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, '.count' );
        }
        $this->filterCount = trim( $count->getText(), '()' );
        $link->click();
        return $this->filterCount;
    }

    protected $filterCount;

    /**
     * @Then /^I should find correct product count$/
     */
    public function iShouldFindCorrectProductCount()
    {
        if ( !$this->filterCount ) {
            throw new ExpectationException( "{$this->filterCount} text not found", $this->getSession() );
        }
        echo "\tLooking for {$this->filterCount} products\n";

        $page     = $this->getPage();
        $products = $page->findAll( 'css', '.f-prod-img' );
        if ( !$products ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, '.f-prod-img' );
        }
        $cnt = count( $products );
        if ( $cnt != $this->filterCount ) {
            throw new ExpectationException( "{$this->filterCount} do not match {$cnt}", $this->getSession() );
        } else {
            echo "\t{$cnt} products found\n";
        }
    }

    /**
     * @Given /^I go to first available sub-category$/
     */
    public function iGoToFirstAvailableSubCategory()
    {
        $path = "//section[contains(@class,'f-prod-listing-filter')]/form/dl/dd[1]/ul/li[2]/a";
        $page = $this->getPage();
        $catLink = $page->find( 'xpath', $path );

        if ( !$catLink ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, $path );
        }

        $catLink->click();
    }

    /**
     * @return \Behat\Mink\Element\DocumentElement
     */
    public function getPage()
    {
        return $this->getSession()->getPage();

    }
}
