@javascript
Feature: Home page
  Ensure homepage shows correctly
  for different users

  Scenario: Open home page as non logged user
    Given I am on "/"
    Then I should see "Log in"

  Scenario: Open home page and try invalid login, then verify error is shown
    Given I am on "/"
    Then I should see "Log In"
    When I follow "Log In"
    And I fill in "login[email]" with "petar.dev@gmail.com"
    And I fill in "login[password]" with "123456"
    And I press "Login"
    And I go to "/"
    Then I should see "My Account"

