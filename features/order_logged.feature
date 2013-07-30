@javascript
Feature: Order process
  To be able to buy a product
  As a logged in user
  I need to be able to place an order

  Scenario: Perform order placement process as logged in user
    Given I am on "/index.php/test/test-product"
    When I press "Add to Cart"
    Then I should be on "/index.php/cart"
    When I go to "/index.php/checkout/login"
    And should see "Login"
    When I fill in "login[email]" with "petar.dev@gmail.com"
    And I fill in "login[password]" with "123456"
    And I press "Login"
    Then I should be on "/index.php/checkout"
    And I should see "Review and Place Order"
    Then I should be on "/index.php/checkout"
    And I should see "Review and Place Order"
    When I press "Place your order"
    Then I should be on "/index.php/checkout/success"
