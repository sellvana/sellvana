@javascript
Feature: Admin super user create
  As I am creating new admin super user
  in admin Role drop-down
  must be disabled

  Scenario: Create admin super user
    Given I am logged in admin
    When I go to "admin/users"
    And I press "New User"
    And select "Yes" from "Super Admin"
    Then "model-role-id" should be disabled

