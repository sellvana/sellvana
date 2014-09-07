<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
            echo "\tLogged in\n";
        } else {
            echo "\tHave to login\n";
            $userName = $page->findField( 'login[username]' );
            $login    = $page->findField( 'login[password]' );

            $userName->setValue( 'admin' );
            $login->setValue( 'admin123' );
            $page->findButton( 'Sign in' )->press();
        }
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
     * @Given /^I am not logged in admin$/
     */
    public function iAmNotLoggedInAdmin()
    {
        $this->visit( "/admin/logout" );
    }

    /**
     * Make sure not to be logged in as customer on frontend
     *
     * @Given /^I am not logged in front$/
     */
    public function iAmNotLoggedInFront()
    {
        $this->visit( "/logout" );
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
        $page  = $this->getPage();
        $field = $page->find( 'css', $element );
        if ( !$field ) {
            $field = $page->find( 'xpath', $element );
        }
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
            $link = $page->find( 'css', $path );
        }

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
        $path    = "//section[contains(@class,'f-prod-listing-filter')]/form/dl/dd[1]/ul/li[2]/a";
        $page    = $this->getPage();
        $catLink = $page->find( 'xpath', $path );

        if ( !$catLink ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, $path );
        }

        $catLink->click();
    }

    /**
     * @Then /^I should find "([^"]*)" products as result$/
     */
    public function iShouldFindProductsAsResult( $count )
    {
        $resultPath = "fieldset.f-prod-listing-toolbar span.hidden-xs";
        $result     = $this->getPage()->find( 'css', $resultPath );
        if ( !$result ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, $resultPath );
        }

        $text = preg_replace( '/\D/', '', $result->getText() );
//        echo "\t$text\n";
        if ( $text != $count ) {
            throw new ExpectationException( "{$text} do not match {$count}", $this->getSession() );
        }
    }

    /**
     * Click first product link on category page
     *
     * @Given /^I click first product link$/
     */
    public function iClickFirstProductLink()
    {
        $productLinkPath = 'div.f-prod-listing div.row div.col-md-4:first-child a.f-prod-name';
        $page            = $this->getPage();
        $productLink     = $page->find( 'css', $productLinkPath );
        if ( !$productLink ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, $productLinkPath );
        }

        $this->productName = $productLink->getText();
        echo "\t{$this->productName}\n";
        $productLink->click();
    }

    /**
     * Click second product link on category page
     *
     * @Given /^I click second product link$/
     */
    public function iClickSecondProductLink()
    {
        $productLinkPath = 'div.f-prod-listing div.row div.col-md-4 a.f-prod-name';
        $page            = $this->getPage();
        $productLink     = $page->findAll( 'css', $productLinkPath );
        if ( !$productLink ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, $productLinkPath );
        }
        if(!isset($productLink[1])){
            echo "\tSecond product link not found, skipping.\n";
            return;
        }
        $this->productName = $productLink[ 1 ]->getText();
        echo "\t{$this->productName}\n";
        $productLink[ 1 ]->click();
    }

    protected $productName;

    /**
     * Assert product name matches stored value
     *
     * @Then /^I should find correct product name$/
     */
    public function iShouldFindCorrectProductName()
    {
        $this->assertPageContainsText( $this->productName );
    }

    /**
     * Click first product image on category page
     *
     * @Given /^I click first product image$/
     */
    public function iClickFirstProductImage()
    {
        $productImageLinkPath = 'div.f-prod-listing div.row div.col-md-4:first-child a.f-prod-img';
        $page                 = $this->getPage();
        $productLink          = $page->find( 'css', $productImageLinkPath );
        if ( !$productLink ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, $productImageLinkPath );
        }
        $img = $productLink->find( 'css', 'img' );
        if ( $img ) {
            // alt text is same as product name
            $this->productName = $img->getAttribute( 'alt' );
            echo "\t{$this->productName}\n";
        }
        $productLink->click();
    }

    /**
     * Click quick view button for first product on category page
     *
     * @Given /^I click first product quick view button$/
     */
    public function iClickFirstProductQuickViewButton()
    {
        $productQVLinkPath = 'div.f-prod-listing div.row div.col-md-4:first-child a.f-prod-quickview-btn';
        $productLinkPath   = 'div.f-prod-listing div.row div.col-md-4:first-child a.f-prod-name';
        $page              = $this->getPage();
        $qvLink            = $page->find( 'css', $productQVLinkPath );
        $prLink            = $page->find( 'css', $productLinkPath );
        if ( !$qvLink ) {
            throw new ElementNotFoundException( $this->getSession(), null, null, $productQVLinkPath );
        }
        $this->productName = $prLink->getText();
        echo "\t{$this->productName}\n";

        $qvLink->click();
    }

    /**
     * Assert quick view for product is displayed
     *
     * @Then /^I should see product quick view$/
     */
    public function iShouldSeeProductQuickView()
    {
        $content = $this->getPage()->find( 'css', 'h4.f-prod-name' )->getText();
        echo "\t{$content}\n";
        if ( $content != $this->productName ) {
            throw new ExpectationException( "{$content} does not match {$this->productName}", $this->getSession() );
        }
    }

    /**
     * Fill input field with random email address
     *
     * @Given /^I fill "([^"]*)" with random email$/
     */
    public function iFillWithRandomEmail( $fieldName )
    {
        $email = md5( microtime( true ) ) . "_test@email.com";
        $this->fillField( $fieldName, $email );
    }

    /**
     * Fill in field with random css selector
     *
     * @When /^I fill in field "([^"]*)" with "([^"]*)"$/
     */
    public function iFillInFieldWith( $selector, $value )
    {
        $value = $this->fixStepArgument( $value );
        $field = $this->getFieldsCss( $selector );
        if ( $field ) {
            $field->setValue( $value );
        }
    }

    /**
     * Check a field value by css selector
     *
     * @Given /^the "([^"]*)" css field should contain "([^"]*)"$/
     */
    public function theCssFieldShouldContain( $selector, $value )
    {
        $value = $this->fixStepArgument( $value );
        $field = $this->getFieldsCss( $selector );
        if ( $field ) {
            $this->assertNodeValueMatchesValue( $value, $field );
        }
    }

    protected $qty_input = '.f-input-qty';

    /**
     * Update qty field for first product in cart
     *
     * @When /^I fill in first product qty with "([^"]*)"$/
     */
    public function iFillInFirstProductQtyWith( $value )
    {
        $value    = $this->fixStepArgument( $value );
        $selector = $this->qty_input;
        $field    = $this->getFieldsCss( $selector );
        if ( $field ) {
            $field->setValue( $value );
        } else {
            throw new ElementNotFoundException( $this->getSession(), 'input', 'css', $selector );
        }
    }

    /**
     * Update qty field for second product in cart
     *
     * @Given /^I fill in second product qty with "([^"]*)"$/
     */
    public function iFillInSecondProductQtyWith( $value )
    {
        $value    = $this->fixStepArgument( $value );
        $selector = $this->qty_input;
        $fields   = $this->getFieldsCss( $selector, false );
        if ( !empty( $fields ) && is_array( $fields ) ) {
            if ( isset( $fields[ 1 ] ) ) {
                /* @var $fieldInput \Behat\Mink\Element\NodeElement */
                $fieldInput = $fields[ 1 ];
                $fieldInput->setValue( $value );
            } else {
                throw new ElementNotFoundException( $this->getSession(), 'second qty input', 'css', $selector );
            }
        } else {
            throw new ElementNotFoundException( $this->getSession(), 'input', 'css', $selector );
        }
    }

    /**
     * Assert qty for first product in cart
     *
     * @Given /^first product qty field should contain "([^"]*)"$/
     */
    public function firstProductQtyFieldShouldContain( $value )
    {
        $value    = $this->fixStepArgument( $value );
        $selector = $this->qty_input;
        $field    = $this->getFieldsCss( $selector );
        if ( $field ) {
            $this->assertNodeValueMatchesValue( $value, $field );
        } else {
            throw new ElementNotFoundException( $this->getSession(), 'input', 'css', $selector );
        }
    }

    /**
     * Assert qty for second product in cart
     *
     * @Given /^second product qty field should contain "([^"]*)"$/
     */
    public function secondProductQtyFieldShouldContain( $value )
    {
        $value    = $this->fixStepArgument( $value );
        $selector = $this->qty_input;
        $fields   = $this->getFieldsCss( $selector, false );
        if ( !empty( $fields ) && is_array( $fields ) ) {
            /* @var $fieldInput \Behat\Mink\Element\NodeElement */
            $fieldInput = $fields[ 1 ];
            $this->assertNodeValueMatchesValue( $value, $fieldInput );
        } else {
            throw new ElementNotFoundException( $this->getSession(), 'input', 'css', $selector );
        }
    }

    /**
     * Check remove checkbox related to first product in cart
     *
     * @When /^I check first product "([^"]*)"$/
     */
    public function iCheckFirstProduct( $checkBox )
    {
        $checkBox = $this->fixStepArgument( $checkBox );
        $this->checkOption( $checkBox );
    }

    /**
     * Assert only one product left in cart
     *
     * @Then /^I should see one product$/
     */
    public function iShouldSeeOneProduct()
    {
        $selector = $this->qty_input;
        $fields   = $this->getFieldsCss( $selector, false );

        if ( count( $fields ) != 1 ) {
            throw new ExpectationException( sprintf(
                "Expected to find exactly one product, found %d",
                count( $fields )
            ), $this->getSession() );
        }
    }

    /**
     * @param string $selector
     * @param bool   $single
     * @return array|\Behat\Mink\Element\NodeElement|null
     */
    protected function getFieldsCss( $selector, $single = true )
    {
        $selector = $this->fixStepArgument( $selector );
        $fields   = $this->getPage()->findAll( 'css', $selector );
        if ( $fields && $single ) {
            return current( $fields );
        }
        return $fields;
    }

    /**
     * @When /^I restart browser$/
     */
    public function iRestartBrowser()
    {
        $this->getSession()->restart();
    }

    /**
     * Shortcut to get page object
     *
     * @return \Behat\Mink\Element\DocumentElement
     */
    public function getPage()
    {
        return $this->getSession()->getPage();

    }

    /**
     * @param                                $value
     * @param Behat\Mink\Element\NodeElement $field
     * @throws Behat\Mink\Exception\ExpectationException
     */
    public function assertNodeValueMatchesValue( $value, $field )
    {
        $actual = $field->getValue( $value );
        $regex  = '/^' . preg_quote( $value, '/' ) . '/ui';

        if ( !preg_match( $regex, $actual ) ) {
            $message = sprintf( 'The field "%s" value is "%s", but "%s" expected.', $field->getTagName(), $actual, $value );
            throw new ExpectationException( $message, $this->getSession() );
        }
    }
}
