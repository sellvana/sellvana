@javascript
Feature: Order process
  To be able to buy a product
  As a website user
  I need to be able to place an order

  Scenario: Perform order placement process as guest
    Given I am on "/index.php/test/test-product"
    When I press "Add to Cart"
    And I go to "/index.php/cart"
    And I go to "/index.php/checkout/login"
    And should see "Login"
    And should see "Email"
    And should see "Password"
    And should see "No Account? Checkout as a guest"
    When I follow "Checkout as a guest"
    Then I should be on "/index.php/checkout/address"
    And I should see "Shipping Address"
    When I fill in "firstname" with "Test"
    And I fill in "lastname" with "User"
    And I fill in "email" with "petar.dev@gmail.com"
    And I fill in "street1" with "Gulf dr. 123"
    And I fill in "city" with "Panama City"
    And I select "US" from "country"
    And I select "FL" from "region"
    And I fill in "postcode" with "12345"
    And I check "Billing address is same as shipping"
    And I press "Save address"
    Then I should be on "/index.php/checkout"
    And I should see "Review and Place Order"
    Then I should be on "/index.php/checkout"
    And I should see "Review and Place Order"
    When I press "Place your order"
    Then I should be on "/index.php/checkout/success"
