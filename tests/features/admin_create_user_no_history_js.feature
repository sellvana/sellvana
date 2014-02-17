@javascript
Feature: Admin user create
  As I am creating new user
  in admin
  History tab must not show

  Scenario: Create super should not have History
    Given I am logged in admin
    When I go to "admin/users"
    And I press "New User"
    Then I should not see "History"

