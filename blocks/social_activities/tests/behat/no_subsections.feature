@block @block_social_activities
Feature: Testing no subsections can be added to the block
  In order to use social activities block
  As a teacher
  I need to prevent subsections inside the block

  Background:
    Given the following "course" exists:
      | fullname    | Course 1 |
      | shortname   | C1       |
      | format      | social   |
      | numsections | 0        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: subsections cannot be added to the social activities block
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    Then I should not see "Subsection" in the "Social activities" "block"
    And I should see "Add an activity or resource" in the "Social activities" "block"
    And I click on "Add an activity or resource" "button" in the "Social activities" "block"
    And I should not see "Subsection" in the "Add an activity or resource" "dialogue"
