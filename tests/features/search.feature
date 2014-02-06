Feature: Search
  Check search
  works correctly

  Scenario Outline: Perform search for various parts of product name,
    verify results.
    Given I am on the homepage
    When I fill in "q" with <search>
    And I click ".f-header-search-form .btn"
    Then I should find <find> products as result

    Examples:
      | search | find |
      | "12"   | "20" |
      | "123"  | "1"  |
      | "2"    | "271"|



