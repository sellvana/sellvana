@javascript
Feature: As a merchant,
  I want to know the ID number of a root category
  to enter it in the Navigation root id categories
  field when selecting categories menu by root id top menu option.

  Scenario: Open root category
    Given I am logged in admin
    When I go to "admin/catalog/categories"
    And I follow "ROOT"
    Then I should see "ID: 1"
