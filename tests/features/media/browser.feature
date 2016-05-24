@media @api @javascript
Feature: Media browser for CKEditor

  Scenario: Uploading an image from within the media browser
    Given I am logged in as a user with the page_creator role
    When I visit "/node/add/page"
    And I open the media browser
    And I click "Upload Image"
    And I attach the file "puppy.jpg" to "Image"
    And I wait for AJAX to finish
    And I enter "Foobazzz" for "Media name"
    And I press "Place"
    And I switch to the window
    And I wait for AJAX to finish
    And I wait 3 seconds
    And I click the ".ui-dialog .ui-dialog-buttonpane .button--primary" element
    And I wait for AJAX to finish
    Then CKEditor should have an embedded entity
    And I queue the latest media entity for deletion

  Scenario: Embedding a YouTube video from within the media browser
    Given I am logged in as a user with the page_creator role
    When I visit "/node/add/page"
    And I open the media browser
    And I click "Create Embed"
    And I enter "https://www.youtube.com/watch?v=zQ1_IbFFbzA" for "embed_code"
    And I wait for AJAX to finish
    And I enter "The Pill Scene" for "Media name"
    And I press "Place"
    And I switch to the window
    And I wait for AJAX to finish
    And I wait 3 seconds
    And I click the ".ui-dialog .ui-dialog-buttonpane .button--primary" element
    And I wait for AJAX to finish
    Then CKEditor should have an embedded entity
    And I queue the latest media entity for deletion

  Scenario: Embedding a Vimeo video from within the media browser
    Given I am logged in as a user with the page_creator role
    When I visit "/node/add/page"
    And I open the media browser
    And I click "Create Embed"
    And I enter "https://vimeo.com/14782834" for "embed_code"
    And I wait for AJAX to finish
    And I enter "Cache Rules Everything Around Me" for "Media name"
    And I press "Place"
    And I switch to the window
    And I wait for AJAX to finish
    And I wait 3 seconds
    And I click the ".ui-dialog .ui-dialog-buttonpane .button--primary" element
    And I wait for AJAX to finish
    Then CKEditor should have an embedded entity
    And I queue the latest media entity for deletion

  Scenario: Embedding a tweet from within the media browser
    Given I am logged in as a user with the page_creator role
    When I visit "/node/add/page"
    And I open the media browser
    And I click "Create Embed"
    And I enter "https://twitter.com/AprilTrubody/status/707226928730742784" for "embed_code"
    And I wait for AJAX to finish
    And I enter "chx speaks" for "Media name"
    And I press "Place"
    And I switch to the window
    And I wait for AJAX to finish
    And I wait 3 seconds
    And I click the ".ui-dialog .ui-dialog-buttonpane .button--primary" element
    And I wait for AJAX to finish
    Then CKEditor should have an embedded entity
    And I queue the latest media entity for deletion

  Scenario: Embedding an Instagram post from within the media browser
    Given I am logged in as a user with the page_creator role
    When I visit "/node/add/page"
    And I open the media browser
    And I click "Create Embed"
    And I enter "https://www.instagram.com/p/jAH6MNINJG" for "embed_code"
    And I wait for AJAX to finish
    And I enter "Drupal Does LSD" for "Media name"
    And I press "Place"
    And I switch to the window
    And I wait for AJAX to finish
    And I wait 3 seconds
    And I click the ".ui-dialog .ui-dialog-buttonpane .button--primary" element
    And I wait for AJAX to finish
    Then CKEditor should have an embedded entity
    And I queue the latest media entity for deletion
