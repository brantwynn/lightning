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
    And I should see a "Media name" field
    And I should see a "Save to my media library" field

  @javascript
  Scenario: Creating a video from a Vimeo URL
    Given I am logged in as a user with the media_creator role
    When I visit "/media/add/video"
    And I enter "https://vimeo.com/14782834" for "Video URL"
    And I wait for AJAX to finish
    Then I should see a video preview
    And I should see a "Media name" field
    And I should see a "Save to my media library" field

  Scenario: Viewing a video as an anonymous user
    Given video from embed code:
    """
    https://www.youtube.com/watch?v=ktCgVopf7D0
    """
    And I am an anonymous user
    When I visit a media entity of type video
    Then I should get a 200 HTTP response
