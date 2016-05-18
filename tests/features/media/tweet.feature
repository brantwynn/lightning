@lightning @media @api
Feature: Twitter media assets
  A media asset representing a tweet.

  @javascript
  Scenario: Creating a tweet
    Given I am logged in as a user with the media_creator role
    When I visit "/media/add/tweet"
    And I enter "https://twitter.com/chx/status/493538461761028096" for "Tweet"
    And I wait for AJAX to finish
    Then I should see a preview

  Scenario: Viewing a tweet as an anonymous user
    Given tweet media from embed code:
    """
    https://twitter.com/webchick/status/672110599497617408
    """
    And I am an anonymous user
    When I visit a media entity of type tweet
    Then I should get a 200 HTTP response
