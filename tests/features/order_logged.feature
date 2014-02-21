@javascript
Feature: Order process
  To be able to buy a product
  As a logged in user
  I need to be able to place an order

  Scenario: Perform order placement process as logged in user
    Given I am not logged in front
    When I go to "/login"
    When I fill in "email" with "test@email.com"
    And I fill in "password" with "123456"
    And I press "Login"
    And I am on the homepage
    When I go to first available category
    And I click first product link
    When I press "Add to Cart"
    And I go to "/cart"
    Then I should find correct product name
    When I go to first available category
    And I click second product link
    When I press "Add to Cart"
    And I go to "/cart"
    Then I should find correct product name
    When I follow "Proceed to Checkout"
    Then I should be on "/checkout"
    And I should see "Review and Place Order"
    When I press "Order"
    Then I should be on "/checkout/success"
    When I go to "/customer/order"
    Then I should see "Orders History"
    And I should see "ID"
