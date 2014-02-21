@javascript
Feature: Add the word username in login field
  As a user,
  I should be able to login the backend
  with my username or email.

  Scenario: Username in login field
    Given go to "/admin"
    And I am not logged in
    Then I should see "User Name"
    And I should see "Password"

