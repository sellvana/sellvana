Feature: Cart
  As a user I should be able to add product to cart
  update its quantity and remove the product from cart

  Scenario: Add, update and remove single product to cart
    Given I am on the homepage
    When I go to first available category
    And I click first product link
    And I press "Add to Cart"
    And I go to "/cart"
    Then I should find correct product name
    When I fill in field ".f-input-qty" with "2"
    And I press "Update Cart"
    Then I should see "Your cart has been updated"
    And the ".f-input-qty" css field should contain "2"
    When I check "Remove"
    And press "Update Cart"
    Then I should see "Your cart is empty"

  Scenario: Add, update and remove multiple products to cart
    Given I am on the homepage
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
    When I fill in first product qty with "2"
    And I fill in second product qty with "5"
    And I press "Update Cart"
    Then I should see "Your cart has been updated"
    And first product qty field should contain "2"
    And second product qty field should contain "5"
    When I check first product "Remove"
    And press "Update Cart"
    Then I should see one product
