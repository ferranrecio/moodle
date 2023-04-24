@core @javascript
Feature: Temporal choice dialog behat test
  In order to use choice dialog in quickforms
  As an admin
  I need to be able to test it via behat

  Background:
    Given the following "categories" exist:
    | name  | category | idnumber |
    | Cat 1 | 0        | CAT1     |
    | Cat 2 | 0        | CAT2     |
    And I log in as "admin"
    And I am on fixture page "/lib/form/tests/behat/fixtures/field_sample_choicedialog.php"

  Scenario: Set some value into choice dialog
    When I set the field "Basic choice dialog" to "Text option 2"
    And I click on "Send form" "button"
    Then I should see "example0: option2" in the "submitted_data" "region"

  Scenario: Disable choice dialog via javascript
    When I click on "Check to disable the first choice dialog field." "checkbox"
    Then the "Disable if example" "field" should be disabled

  Scenario: Hide choice dialog via javascript
    Given I should see "Hide if example"
    When I click on "Check to hide the first choice dialog field." "checkbox"
    Then I should not see "Hide if example"

  Scenario: Use a choice dialog to disable and hide other fields
    Given I should not see "Hide if element"
    And the "Disabled if element" "field" should be disabled
    When I set the field "Control choice dialog" to "Show or enable subelements"
    Then I should see "Hide if element"
    And the "Disabled if element" "field" should be enabled
    And I set the field "Control choice dialog" to "Hide or disable subelements"
    And I should not see "Hide if element"
    And the "Disabled if element" "field" should be disabled

  Scenario: Test the fancy javascript choice dialog
    When I click on "Forced text option 1" "button"
    And I click on "Forced text option 2" "radio" in the "Forced choice dialog" "dialogue"
    And I click on "Apply" "button" in the "Forced choice dialog" "dialogue"
    And I click on "Send form" "button"
    Then I should see "example4: option2" in the "submitted_data" "region"

  Scenario: Choice dialog using keyboard
    When I click on "Quick focus button" "button"
    And I press the tab key
    And the focused element is "Forced text option 1" "button"
    # Open dialog and select option 2.
    And I press enter
    And I press the down key
    # Focus apply button.
    And I press the tab key
    And I press the tab key
    And I press enter
    And I wait until "Forced text option 2" "button" exists
    And the focused element is "Forced text option 2" "button"
    And I click on "Send form" "button"
    Then I should see "example4: option2" in the "submitted_data" "region"

  Scenario: Choice dialog using keyboard and apply with enter
    When I click on "Quick focus button" "button"
    And I press the tab key
    And the focused element is "Forced text option 1" "button"
    # Open dialog and select option 2.
    And I press enter
    And I press the down key
    # Apply with enter key.
    And I press enter
    And I wait until "Forced text option 2" "button" exists
    And the focused element is "Forced text option 2" "button"
    And I click on "Send form" "button"
    Then I should see "example4: option2" in the "submitted_data" "region"

  Scenario: Esc key and cancel button should return the focus to the choice dialog field
    When I click on "Forced text option 1" "button"
    Then I press the escape key
    And the focused element is "Forced text option 1" "button"
    And I click on "Forced text option 1" "button"
    And I click on "Cancel" "button" in the "Forced choice dialog" "dialogue"
    And the focused element is "Forced text option 1" "button"
    And I click on "Forced text option 1" "button"
    And I click on "Close" "button" in the "Forced choice dialog" "dialogue"
    And the focused element is "Forced text option 1" "button"

  Scenario: Cancel the modal should not change the choice dialog value
    When I click on "Forced text option 1" "button"
    And I click on "Forced text option 2" "radio" in the "Forced choice dialog" "dialogue"
    And I click on "Cancel" "button" in the "Forced choice dialog" "dialogue"
    And the focused element is "Forced text option 1" "button"
    And I click on "Send form" "button"
    Then I should see "example4: option1" in the "submitted_data" "region"
