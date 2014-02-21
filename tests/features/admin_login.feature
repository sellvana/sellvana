Feature: Verify user can login in admin
  As a user,
  I should be able to login the backend
  with correct credentials

  Scenario: Should see login form if not logged in
    Given go to "/admin"
    And I am not logged in admin
    Then I should see "User Name or Email"
    And I should see "Password"

  Scenario Outline: Non complete form should not log me in. no javascript
    Given go to "/admin"
    And I am not logged in admin
    When I fill in the following:
      | login[username] | <login> |
      | login[password] | <pass>  |
    When I press "Sign in"
    Then I should see "User Name or Email"
    And I should see "Password"
    And I should see "Username and password cannot be blank."
  Examples:
    | login | pass     |
    |       |          |
    | admin |          |
    |       | admin123 |

  Scenario Outline: Should not be able to login with wrong credentials
  One test with wrong username and pass,
  one each with correct either pass or user name
  but not both
    Given go to "/admin"
    And I am not logged in admin
    When I fill in the following:
      | login[username] | <login> |
      | login[password] | <pass>  |
    When I press "Sign in"
    Then I should see "Invalid user name or password."
  Examples:
    | login  | pass     |
    | random | phrase   |
    | admin  | phrase   |
    | random | admin123 |

  Scenario: Should be able to login with correct credentials
    Given go to "/admin"
    And I am not logged in admin
    When I fill in the following:
      | login[username] | admin    |
      | login[password] | admin123 |
    When I press "Sign in"
    Then I should see "Dashboard"
    When I restart browser
    And I go to "/admin"
    Then I should see "User Name or Email"
    And I should see "Password"

  @javascript
  Scenario Outline: Non complete form should not log me in with javascript
    Given go to "/admin"
    And I am not logged in admin
    When I fill in the following:
      | login[username] | <login> |
      | login[password] | <pass>  |
    When I press "Sign in"
    Then I should see "User Name or Email"
    And I should see "Password"
    And I should see "This field is required."
  Examples:
    | login | pass     |
    |       |          |
    | admin |          |
    |       | admin123 |

  @javascript
  Scenario: Should be able to login with correct credentials
    Given go to "/admin"
    And I am not logged in admin
    When I fill in the following:
      | login[username] | admin    |
      | login[password] | admin123 |
    When I press "Sign in"
    Then I should see "Dashboard"


  @javascript
  Scenario: Should be able to login with correct credentials
  If remember me is not checked, reopening the browser
  should lead to login
    Given go to "/admin"
    And I am not logged in admin
    When I fill in the following:
      | login[username] | admin    |
      | login[password] | admin123 |
    When I press "Sign in"
    Then I should see "Dashboard"
    When I restart browser
    And I go to "/admin"
    Then I should see "User Name or Email"
    And I should see "Password"

  @javascript
  Scenario: Should be able to login with correct credentials
  If remember me is checked, reopening the browser
  should lead to dashboard
    Given go to "/admin"
    And I am not logged in admin
    When I fill in the following:
      | login[username] | admin    |
      | login[password] | admin123 |
    And I check "remember_me"
    When I press "Sign in"
    Then I should see "Dashboard"
    When I restart browser
    And I go to "/admin"
    Then I should see "Dashboard"

