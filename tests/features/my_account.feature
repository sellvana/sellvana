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

  Scenario Outline: Verify I can follow all my account links
    Given I am on "/customer/myaccount"
    When I follow "<title>"
    Then I should be on "<url>"
  Examples:
    | title                    | url                              |
    | Edit Account Information | /customer/myaccount/edit         |
    | Edit Account             | /customer/myaccount/edit         |
    | Change Password          | /customer/myaccount/editpassword |
    | Address Book             | /customer/address                |
    | Order history            | /customer/order                  |
    | My Orders                | /customer/order                  |
    | Wishlist                 | /wishlist                        |

  Scenario: Verify edit account page
    Given I am on "/customer/myaccount/edit"
    Then I should see "Edit Account"
    And I should see a "#edit-firstname" element
    And I should see an "#edit-email" element
    And I should see a "#edit-lastname" element

  @javascript
  Scenario: Verify edit account page
    Given I am on "/customer/myaccount/edit"
    Then the "edit-firstname" field should contain "Test"
    And the "edit-lastname" field should contain "User"
    And the "edit-email" field should contain "test@email.com"
    When I fill in the following:
      | First Name | Test2           |
      | Last Name  | User2           |
      | Email      | test2@email.com |
    And press "Save"
    And go to "/customer/myaccount/edit"
    Then the "edit-firstname" field should contain "Test2"
    And the "edit-lastname" field should contain "User2"
    And the "edit-email" field should contain "test2@email.com"
    When I fill in the following:
      | First Name | Test           |
      | Last Name  | User           |
      | Email      | test@email.com |
    And press "Save"
    And go to "/customer/myaccount/edit"
    Then the "edit-firstname" field should contain "Test"
    And the "edit-lastname" field should contain "User"
    And the "edit-email" field should contain "test@email.com"

  @javascript
  Scenario: Verify cannot submit wrong account data
    Given I am on "/customer/myaccount/edit"
    When I fill in the following:
      | First Name | Test2          |
      | Last Name  | User2          |
      | Email      | test2email.com |
    And press "Save"
    Then I should see "Please enter a valid email address."

  Scenario: Verify orders page
    Given I am on "/wishlist"
    Then I should see "Wishlist"

  Scenario: Verify address page
    Given I am on "/customer/address"
    Then I should see "Address Book"
    And I should see "Add new address"

  Scenario: Verify orders page
    Given I am on "/customer/order"
    Then I should see "Orders History"


  Scenario: Verify change password page
    Given I am on "/customer/myaccount/editpassword"
    Then I should see "Change Password"
    And I should see a "#model-current-password" element
    And I should see a "#model-password" element
    And I should see a "#edit-password_confirm" element