Feature: Promotions form UI changes
  As a user in admin,
  When I configure promotion
  I should see specific controls.

  Scenario: Customer group
    Given I am logged in admin
    When I go to "admin/promo/form"
    Then I should see "CUSTOMER GROUPS"

  Scenario: Site dropdown
    Given I am logged in admin
    When I go to "admin/promo/form"
    Then I should see "Sites"

  Scenario: Categories in From dropdown list
    Given I am logged in admin
    When I go to "admin/promo/form"
    Then I should see "From"
    And the "select[name='model[buy_group]']" element should contain "Categories"

