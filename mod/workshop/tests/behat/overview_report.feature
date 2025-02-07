@mod @mod_workshop
Feature: Testing overview integration in mod_workshop
In order to summarize the workshops
  As a user
  I need to be able to see the workshop overview

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Username  | 1        |
      | student2 | Username  | 2        |
      | student3 | Username  | 3        |
      | student4 | Username  | 4        |
      | student5 | Username  | 5        |
      | student6 | Username  | 6        |
      | student7 | Username  | 7        |
      | student8 | Username  | 8        |
      | teacher1  | Teacher  | T        |
    And the following "courses" exist:
      | fullname | shortname | groupmode |
      | Course 1 | C1        | 1         |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | student5 | C1     | student        |
      | student6 | C1     | student        |
      | student7 | C1     | student        |
      | student8 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name       | course | idnumber  | submissiontypetext | submissiontypefile | grade | gradinggrade | gradedecimals | overallfeedbackmethod |
      | workshop | Activity 1 | C1     | workshop1 | 2                  | 1                  | 100   | 5            | 1             | 2                     |
      | workshop | Activity 2 | C1     | workshop1 | 2                  | 1                  | 100   | 5            | 1             | 2                     |
    # And the following "grade grades" exist:
    #   | gradeitem               | user     | grade |
    #   | Activity 1 (submission) | student1 | 20    |
    #   | Activity 2 (assessment) | student1 | 10    |

  Scenario: The workshop overview report should generate log events
    Given I am on the "Course 1" "course > activities > workshop" page logged in as "teacher1"
    When I navigate to "Reports" in current page administration
    And I click on "Logs" "link"
    And I click on "Get these logs" "button"
    Then I should see "Course activities overview page viewed"
    And I should see "viewed the instance list for the module 'workshop'"

  @javascript
  Scenario: Students can see relevant columns in the workshop overview
    # Workshop is not compatible with grade generators (hopefully someday).
    Given I am on the "Course 1" "grades > Grader report > View" page logged in as "teacher1"
    And I turn editing mode on
    And I change window size to "large"
    And I click on "Activity 1 (submission)" "core_grades > grade_actions" in the "Username 1" "table_row"
    And I choose "Edit grade" in the open action menu
    And I set the following fields to these values:
      | Overridden  | 1                      |
      | Final grade | 10                     |
    And I press "Save changes"
    And I click on "Activity 2 (assessment)" "core_grades > grade_actions" in the "Username 1" "table_row"
    And I choose "Edit grade" in the open action menu
    And I set the following fields to these values:
      | Overridden  | 1                      |
      | Final grade | 20                     |
    And I press "Save changes"
    And I change window size to "medium"
    When I am on the "Course 1" "course > activities > workshop" page logged in as "student1"
    # Check columns.
    Then I should see "Name" in the "workshop_overview_collapsible" "region"
    And I should see "Assessment grade" in the "workshop_overview_collapsible" "region"
    And I should see "Submission grade" in the "workshop_overview_collapsible" "region"
    # Check Grades.
    And I should see "10.00" in the "Activity 1" "table_row"
    And I should see "-" in the "Activity 1" "table_row"
    And I should see "-" in the "Activity 2" "table_row"
    # The worksup assessment grade is normalized to 5.00.
    And I should see "5.00" in the "Activity 2" "table_row"
