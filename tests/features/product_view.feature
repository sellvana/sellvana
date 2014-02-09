@javascript
Feature: Product view
  Provided I am user at frontend
  I must be able to navigate to product page
  via clicking product image
  or product link
  and be able to see quick view

  Scenario: Open product page by clicking on direct product link
    Given I am on the homepage
    When I go to first available category
    And I click first product link
    Then I should find correct product name

  Scenario: Open product page by clicking on product image
    Given I am on the homepage
    When I go to first available category
    And I click first product image
    Then I should find correct product name

  Scenario: Use product quick view
    Given I am on the homepage
    When I go to first available category
    And I click first product quick view button
    Then I should see product quick view


