Feature: Order process
  To be able to buy a product
  As a website user
  I need to be able to place an order

  Scenario: Opening main page
    Given I am on "/"
    Then I should see "Hello there"

  Scenario: Opening test cat page
    Given I am on "/"
    Then I should see "Hello there"
    Then I should see "Test Cat"
    When I follow "Test Cat"
    Then I should see "Test Product"

  Scenario: Open product page
    Given I am on "/index.php/test"
    Then I should see "Test Product"
    When I follow "Test Product"
    Then I should see "Overview"
    Then I should be on "/index.php/test/test-product"

  Scenario: Add product to cart from its page
    Given I am on "/index.php/test/test-product"
    When I press "Add to Cart"
    Then I should be on "/index.php/cart"