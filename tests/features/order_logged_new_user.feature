@javascript
Feature: Order process for new customer that has no address
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
    Then I should be on "/checkout/address"
    And I should see "Shipping Address"
    When I fill in "firstname" with "Test"
    And I fill in "lastname" with "User"
    And I fill in "email" with "test@email.com"
    And I fill in "street1" with "Gulf dr. 123"
    And I fill in "city" with "Panama City"
    And I select "United States" from "country"
    And I fill in "postcode" with "12345"
    And I check "Billing address is same as shipping"
    And I press "Save address"
    Then I should see "This field is required."
    And I select "FL" from "region"
    And I press "Save address"
    Then I should be on "/checkout"
    And I should see "Review and Place Order"
    When I press "Order"
    Then I should be on "/checkout/success"
    When I go to "/customer/order"
    Then I should be on "/customer/order"
    And I should see "Orders History"
    And I should see "ID"
