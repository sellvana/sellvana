Feature: Private pages
  Ensure that non logged user cannot access
  private pages

  Scenario Outline: Try to go to my account page as guest
    and should be presented with login form
    Given I am on the homepage
    When I go to <private_page>
    Then I should see "Login"
  Examples:
    | private_page                       |
    | "/customer/myaccount"              |
    | "/customer/myaccount/edit"         |
    | "/customer/myaccount/editpassword" |
    | "/customer/address"                |
    | "/customer/order"                  |
    | "/wishlist"                        |