@core @core_course
Feature: Teachers can delete courses.
  In order to delete a course
  As moodle admin
  I should be able to delete the course even when modules are disabled.

  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity | name | intro        | course | idnumber |
      | assign   | A1   | Assignment 1 | C1     | ass1     |

  @javascript
  Scenario: Test deleting a course when course has modules disabled
    Given I am logged in as "admin"
    And I navigate to "Plugins > Activity modules > Manage activities" in site administration
    And I click on "Disable Assignment" "link"
    And I should see "Assignment disabled."
    And I navigate to "Courses > Manage courses and categories" in site administration
    And I select course "Course 1" in the management interface
    And I click on "delete" action for "Course 1" in management course listing
    And I should see "Confirm"
    And I press "Delete"
    And I should see "Cannot delete disabled activity: assign"
    And I should see "C1 has been completely deleted"
    When I navigate to "Courses > Manage courses and categories" in site administration
    Then I should not see "Course 1"

  @javascript
  Scenario: Test deleting a course when all modules are enabled
    Given I am logged in as "admin"
    And I navigate to "Courses > Manage courses and categories" in site administration
    And I select course "Course 1" in the management interface
    And I click on "delete" action for "Course 1" in management course listing
    And I should see "Confirm"
    And I press "Delete"
    And I should see "C1 has been completely deleted"
    When I navigate to "Courses > Manage courses and categories" in site administration
    Then I should not see "Course 1"
