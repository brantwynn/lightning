@media @api
Feature: Media browser for CKEditor

  @javascript
  Scenario: Opening the media browser
    Given I am logged in as a user with the page_creator role
    When I visit "/node/add/page"
    And I open the media browser
    Then I should see a "nav.tabs.entity-tabs" element
