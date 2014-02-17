@javascript
Feature: Admin user edit
  As I am editing a user
  in admin
  I should see History tab

  Scenario: Edit user history tab
    Given I am logged in admin
    When I go to "admin/users/form?id=1"
    Then I should see "History"

