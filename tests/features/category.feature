@javascript
Feature: Category
  Ensure category page shows correctly

  Scenario: Open home page and try invalid login, then verify error is shown
    Given I am on "/"
    And I go to first available category
    Then I should see its name


