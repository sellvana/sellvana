@javascript
Feature: Rendering error in Promotions > Details
  As a user in admin,
  I should be able
  to create promotional details.

  Scenario: Open details tab
    Given I am logged in admin
    When I go to "admin/promo/form"
    And I follow "Details"
    Then I should not see "ERROR"


