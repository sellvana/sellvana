Feature: Search
  Check search
  works correctly

  Scenario: Perform search for various parts of product name,
    verify results.
    Given I am on the homepage
    When I fill in "q" with "12"
    And I click ".f-header-search-form .btn"
    Then I should find "20" products as result



