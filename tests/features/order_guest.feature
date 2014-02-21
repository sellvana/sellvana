@javascript
Feature: Order process
  To be able to buy a product
  As a website user
  I need to be able to place an order

  Scenario: Perform order placement process as guest
    Given I am on the homepage
    And I am not logged in front
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
    When I go to "/checkout/login"
    And should see "Login"
    And should see "Email"
    And should see "Password"
    And should see "No Account? Checkout as a guest"
    When I follow "Checkout as a guest"
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
    And I should see "Review and Place Order"
    When I press "Order"
    Then I should be on "/checkout/success"
