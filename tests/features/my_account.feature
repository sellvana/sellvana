Feature: Private pages
  Ensure that logged in user can access
  private pages

  Background:
    Given I am on the homepage
    And I am not logged in front
    When I go to "/login"
    And I fill in the following:
      | login[email]    | test@email.com |
      | login[password] | 123456         |
    And I press "Login"

  Scenario Outline: Try to go to my account page as registered user
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

  Scenario: Verify my account dashboard page
    Given I am on "/customer/myaccount"
    Then I should see "Dashboard"
    And I should see "My Accounts"
    And I should see "My Orders"
    And I should see "Newsletter"
    And I should see "Edit Account"
    And I should see "Address Book"
    And I should see "Wishlist"
