@lightning @media @api
Feature: Video media assets
  A media asset representing an externally hosted video.

  @javascript
  Scenario: Creating a video from a YouTube URL
    Given I am logged in as a user with the media_creator role
    When I visit "/media/add/video"
    And I enter "https://www.youtube.com/watch?v=zQ1_IbFFbzA" for "Video URL"
    And I wait for AJAX to finish
    Then I should see a video preview

  @javascript
  Scenario: Creating a video from a Vimeo URL
    Given I am logged in as a user with the media_creator role
    When I visit "/media/add/video"
    And I enter "https://vimeo.com/14782834" for "Video URL"
    And I wait for AJAX to finish
    Then I should see a video preview

  Scenario: Viewing a video as an anonymous user
    Given video from embed code:
    """
    https://www.youtube.com/watch?v=ktCgVopf7D0
    """
    And I am an anonymous user
    When I visit a media entity of type video
    Then I should get a 200 HTTP response

  @javascript
  Scenario: Creating a video in CKEditor from an embed code
    Given I am logged in as a user with the page_creator,media_creator roles
    When I go to "/node/add/page"
    And I open the CKEditor media widget
    And I click "Create Embed"
    And I enter the embed code "https://www.youtube.com/watch?v=DyFYUKBEZAg"
    And I submit the media widget
    Then CKEditor should match "/data-entity-id=.?[0-9]+.?/"
