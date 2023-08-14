@core @core_completion
Feature: Allow admins to edit the default activity completion rules at site level.
  In order to set the activity completion defaults for new activities
  As an admin
  I need to be able to edit the completion rules for a group of activities at site level.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And I log in as "admin"

  Scenario: Default values don't affect existing activities when no site or course defaults are defined
    Given the following "activity" exists:
      | activity   | assign              |
      | course     | C1                  |
      | name       | Test assignment one |
      | completion | 1                   |
    And I am on the "Test assignment one" Activity page
    When I navigate to "Settings" in current page administration
    Then the field "Completion tracking" matches value "1"

  Scenario: Default activity completion rules with no site or course default completion
    When I am adding an assign to section 0 of the "Course 1" course
    Then the field "Completion tracking" matches value "0"

  Scenario: Default values don't affect existing activities when site default completion are defined
    Given the following "activity" exists:
      | activity   | assign              |
      | course     | C1                  |
      | name       | Test assignment one |
      | completion | 0                   |
    And the following "core_completion > Course default" exist:
      | course               | module | completion | completionview | completionusegrade | completionsubmit |
      | Acceptance test site | assign | 2          | 0              | 1                  | 1                |
    And I am on the "Test assignment one" Activity page
    When I navigate to "Settings" in current page administration
    Then the field "Completion tracking" matches value "0"

  Scenario: Default activity completion rules with site default completion but with no course default completion
    Given the following "core_completion > Course default" exist:
      | course               | module | completion | completionview | completionusegrade | completionsubmit |
      | Acceptance test site | assign | 2          | 0              | 1                  | 1                |
    When I am adding an assign to section 0 of the "Course 1" course
    Then the field "Completion tracking" matches value "2"
    And the field "completionview" matches value "0"
    And the field "completionusegrade" matches value "1"
    And the field "completionsubmit" matches value "1"

  Scenario: Default values don't affect existing activities when site and course defaults completion are defined
    Given the following "activity" exists:
      | activity   | assign              |
      | course     | C1                  |
      | name       | Test assignment one |
      | completion | 0                   |
    And the following "core_completion > Course defaults" exist:
      | course               | module | completion | completionview | completionusegrade | completionsubmit |
      | Acceptance test site | assign | 2          | 0              | 1                  | 1                |
      | C1                   | assign | 2          | 1              | 0                  | 1                |
    And I am on the "Test assignment one" Activity page
    When I navigate to "Settings" in current page administration
    Then the field "Completion tracking" matches value "0"

  Scenario: Default activity completion rules with site default completion and course default completion
    Given the following "core_completion > Course defaults" exist:
      | course               | module | completion | completionview | completionusegrade | completionsubmit |
      | Acceptance test site | assign | 2          | 0              | 1                  | 1                |
      | C1                   | assign | 2          | 1              | 0                  | 1                |
    When I am adding an assign to section 0 of the "Course 1" course
    Then the field "Completion tracking" matches value "2"
    And the field "completionview" matches value "1"
    And the field "completionusegrade" matches value "0"
    And the field "completionsubmit" matches value "1"
