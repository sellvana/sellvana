@javascript
Feature: Customer
  As a customer
  I should be able to
  perform various actions

  Scenario: Register as new customer
    Given I am on the homepage
    And I am not logged in front
    When I go to "/customer/register"
    Then I should see "Register on"
    When I fill in the following:
      | model[firstname]        | Test   |
      | model[lastname]         | User   |
      | model[password]         | 123456 |
      | model[password_confirm] | 123456 |
    And I fill "model[email]" with random email
    And I press "Register"
    Then I should see "Thank you for your access request. We will be in touch shortly via email"

  Scenario: Not able to register new user with registered email
    Given I am on the homepage
    And I am not logged in front
    When I go to "/customer/register"
    Then I should see "Register on"
    When I fill in the following:
      | model[firstname]        | Test   |
      | model[lastname]         | User   |
      | model[password]         | 123456 |
      | model[password_confirm] | 123456 |
    And I fill in "model[email]" with "test@email.com"
    And I press "Register"
    Then I should see "An account with this email address already exists"

  Scenario: Test form will not be submitted when there are empty required fields
    Given I am on the homepage
    And I am not logged in front
    When I go to "/customer/register"
    When I fill in the following:
      | model[firstname]        |  |
      | model[lastname]         |  |
      | model[password]         |  |
      | model[password_confirm] |  |
    And I press "Register"
    Then I should see "This field is required."
    When I fill in the following:
      | model[firstname]        | Test |
      | model[lastname]         |      |
      | model[password]         |      |
      | model[password_confirm] |      |
    And I press "Register"
    Then I should see "This field is required."
    When I fill in the following:
      | model[firstname]        |      |
      | model[lastname]         | User |
      | model[password]         |      |
      | model[password_confirm] |      |
    And I press "Register"
    Then I should see "This field is required."
    When I fill in the following:
      | model[firstname]        |        |
      | model[lastname]         |        |
      | model[password]         | 123456 |
      | model[password_confirm] |        |
    And I press "Register"
    Then I should see "This field is required."
    When I fill in the following:
      | model[firstname]        |        |
      | model[lastname]         |        |
      | model[password]         |        |
      | model[password_confirm] | 123456 |
    And I press "Register"
    Then I should see "This field is required."

  Scenario: Test form will not be submitted when pass field does not match
    Given I am on the homepage
    And I am not logged in front
    When I go to "/customer/register"
    When I fill in the following:
      | model[firstname]        | Test   |
      | model[lastname]         | User   |
      | model[password]         | 123456 |
      | model[password_confirm] | 123457 |
    And I fill "model[email]" with random email
    Then I should see "Please enter the same value again."
    When I press "Register"
    Then I should see "Please enter the same value again."

  Scenario Outline: Get invalid email address format message
    Given I am on the homepage
    And I am not logged in front
    When I go to "/customer/register"
    And I fill in "Email" with <email>
    And I fill in "Password" with " "
    Then I should see "Please enter a valid email address."
  Examples:
    | email        |
    | "some@email" |
    | "nonsense"   |
    | "123456"     |

  Scenario: Login as a customer
    Given I am on the homepage
    And I am not logged in front
    When I go to "/login"
    And I fill in the following:
      | login[email]    |  |
      | login[password] |  |
    And I press "Login"
    Then I should see "This field is required."
    And I fill in the following:
      | login[email]    | test@email.com |
      | login[password] |                |
    And I press "Login"
    Then I should see "This field is required."
    And I fill in the following:
      | login[email]    |        |
      | login[password] | 123456 |
    And I press "Login"
    Then I should see "This field is required."
    And I fill in the following:
      | login[email]    | test@email.com |
      | login[password] | 123456         |
    And I press "Login"
    Then I should see "My Account"

  Scenario Outline: Not able to login with invalid credentials
    Given I am on the homepage
    And I am not logged in front
    When I go to "/login"
    And I fill in "Email" with <email>
    And I fill in "Password" with <password>
    And I press "Login"
    Then I should see "Invalid email or password."
  Examples:
    | email            | password |
    | "some@email.com" | "134124" |
    | "some@email.com" | "123456" |
    | "test@email.com" | "34345"  |

  Scenario Outline: Get invalid email address format message
    Given I am on the homepage
    And I am not logged in front
    When I go to "/login"
    And I fill in "Email" with <email>
    And I fill in "Password" with " "
    Then I should see "Please enter a valid email address."
  Examples:
    | email        |
    | "some@email" |
    | "nonsense"   |
    | "123456"     |

  Scenario: Recover forgotten password
    Given I am on the homepage
    And I am not logged in front
    When I go to "/customer/password/recover"
    When I fill in "Email" with ""
    And I press "Send Reset Instructions"
    Then I should see "This field is required."
    When I fill in "Email" with "test@email.com"
    And I press "Send Reset Instructions"
    Then I should see "If the email address was correct, you should receive an email shortly with password recovery instructions"

  Scenario Outline: Try to go to my account page as registered user
    Given I am on the homepage
    And I am not logged in front
    When I go to "/login"
    And I fill in the following:
      | login[email]    | test@email.com |
      | login[password] | 123456         |
    And I press "Login"
    When I go to <private_page>
    Then I should see "My Account"
    And I should not see "Login"
  Examples:
    | private_page                       |
    | "/customer/myaccount"              |
    | "/customer/myaccount/edit"         |
    | "/customer/myaccount/editpassword" |
    | "/customer/address"                |
    | "/customer/order"                  |
    | "/wishlist"                        |

  Scenario: Being able to log out
    Given I am on the homepage
    And I go to "/logout"
    Then I should see "Log in"