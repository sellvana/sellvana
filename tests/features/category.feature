@javascript
Feature: Category
  Ensure category page shows correctly

  Scenario: Go to first category in navigation and make sure
    its name is displayed as page title
    Given I am on "/"
    And I go to first available category
    Then I should see its name


