#@javascript
Feature: Customer
  As a customer
  I should be able to
  perform various actions

  Scenario: Register as new customer
    Given I am on the homepage
    When I go to "/customer/register"
    Then I should see "Register on"
    When I fill in the following:
      | model[firstname]        | Test           |
      | model[lastname]         | User           |
      | model[password]         | 123456         |
      | model[password_confirm] | 123456         |
    And I fill "model[email]" with random email
    And I press "Register"
    Then I should see "Thank you for your access request. We will be in touch shortly via email"