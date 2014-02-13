Feature: Facets
  Check faceted filtering
  works correctly

  Scenario: Go to first category in navigation
    click some of the filtering options
    ensure correct results are shown
    Given I am on "/"
    When I go to first available category
    And I click first filter
    Then I should find correct product count

  Scenario: Go to first category in navigation
    and then to subcategory and
    click some of the filtering options
    ensure correct results are shown
    Given I am on "/"
    When I go to first available category
    And I go to first available sub-category
    And I click first filter
    Then I should find correct product count


