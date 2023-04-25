@core @javascript
Feature: Test modal radio buttons module
  In order to make user select an option
  As a user
  I need to interact with the radio modal

  Background:
    # Get to the fixture page.
    Given I log in as "admin"
    And I am on fixture page "/lib/tests/behat/fixtures/modal_radio_testpage.php"

  Scenario: The radio modal shows all the available options.
    When I click on "Show modal" "button"
    # Check all string are loaded, literals and get_string ones.
    Then I should see "Add text" in the "Test modal" "dialogue"
    And I should see "Second option" in the "Test modal" "dialogue"
    And I should see "Third option" in the "Test modal" "dialogue"
    # Check enabled and disabled options.
    And the "Add text" "radio" should be enabled
    And the "Second option" "radio" should be enabled
    And the "Third option" "radio" should be disabled
    # Check options description.
    And I should see "Define courses and categories and assign people to them, edit pending courses" in the "Test modal" "dialogue"
    And I should see "Second option description" in the "Test modal" "dialogue"
    And I should see "Third option description" in the "Test modal" "dialogue"
    # Icons are only decorative but we check it anyway.
    And ".modal-body [data-mdl-optionnumber='1'] .icon-box" "css_element" should not exist
    And ".modal-body [data-mdl-optionnumber='2'] .icon-box" "css_element" should exist
    And ".modal-body [data-mdl-optionnumber='3'] .icon-box" "css_element" should exist
    # Check default selected value.
    And I click on "Save changes" "button" in the "Test modal" "dialogue"
    And I should see "Saved value: first" in the "eventmonitor" "region"
    And I should see "Close value: first" in the "eventmonitor" "region"

  Scenario: The user can select an option and trigger a save event
    When I click on "Show modal" "button"
    And I click on "Second option" "radio" in the "Test modal" "dialogue"
    And I click on "Save changes" "button" in the "Test modal" "dialogue"
    Then I should see "On change value: second" in the "eventmonitor" "region"
    And I should see "Saved value: second" in the "eventmonitor" "region"
    And I should see "Close value: second" in the "eventmonitor" "region"

  Scenario: The user can select an option and trigger a cancel event by clicking on cancel button
    When I click on "Show modal" "button"
    And I click on "Second option" "radio" in the "Test modal" "dialogue"
    And I click on "Cancel" "button" in the "Test modal" "dialogue"
    Then I should see "On change value: second" in the "eventmonitor" "region"
    And I should see "Cancel value: second" in the "eventmonitor" "region"
    And I should see "Close value: second" in the "eventmonitor" "region"

  Scenario: The user can select an option and trigger a cancel event by clicking on close button
    When I click on "Show modal" "button"
    And I click on "Second option" "radio" in the "Test modal" "dialogue"
    And I click on "Close" "button" in the "Test modal" "dialogue"
    Then I should see "On change value: second" in the "eventmonitor" "region"
    And I should see "Close value: second" in the "eventmonitor" "region"

  Scenario: The user can submit the radio button with the keyboard
    When I click on "Show modal" "button"
    # Focus modal content.
    And I press the tab key
    And I press the tab key
    # Move option and submit.
    And I press the down key
    And I press enter
    Then I should see "On change value: second" in the "eventmonitor" "region"
    And I should see "Saved value: second" in the "eventmonitor" "region"
    And I should see "Close value: second" in the "eventmonitor" "region"
    And the focused element is "Show modal" "button"

  Scenario: The user cancel the radio button with the keyboard
    When I click on "Show modal" "button"
    # Focus modal content.
    And I press the tab key
    And I press the tab key
    # Move option and cancel.
    And I press the down key
    And I press the tab key
    And I press enter
    Then I should see "On change value: second" in the "eventmonitor" "region"
    And I should see "Cancel value: second" in the "eventmonitor" "region"
    And I should see "Close value: second" in the "eventmonitor" "region"
    And the focused element is "Show modal" "button"
