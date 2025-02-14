@mod @mod_assign
Feature: Testing overview integration in mod_assign
  In order to summarize the assignments
  As a user
  I need to be able to see the assignment overview

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
      | activity | name           | course | idnumber | duedate              | assignsubmission_onlinetext_enabled | assignsubmission_file_enabled | submissiondrafts |
      | assign   | Date assign    | C1     | assign1  | ##1 Jan 2040 08:00## | 1                                   | 0                             | 0                |
      | assign   | No submissions | C1     | assign2  | ##1 Jan 2040 08:00## | 1                                   | 0                             | 0                |
      | assign   | Pending grades | C1     | assign3  |                      | 1                                   | 0                             | 0                |
    And the following "mod_assign > submissions" exist:
      | assign         | user     | onlinetext                          |
      | Date assign    | student1 | This is a submission for assignment |
      | Pending grades | student1 | This is a submission for assignment |
      | Pending grades | student2 | This is a submission for assignment |
    And the following "grade grades" exist:
      | gradeitem      | user     | grade |
      | Pending grades | student1 | 50    |

  Scenario: The assign overview report should generate log events
    Given I am on the "Course 1" "course > activities > assign" page logged in as "teacher1"
    When I am on the "Course 1" "course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Logs" "link"
    And I click on "Get these logs" "button"
    Then I should see "Course activities overview page viewed"
    And I should see "viewed the instance list for the module 'assign'"

  @javascript
  Scenario: Teachers can see relevant columns in the assign overview
    # The teacher needs to grade an assignment manually to change the real status.
    Given I am on the "assign1" "Activity" page logged in as "teacher1"
    And I click on "Grade" "link_or_button" in the "region-main" "region"
    And I set the field "Grade" to "50"
    And I click on "Save changes" "button"
    When I am on the "Course 1" "course > activities > assign" page logged in as "teacher1"
    # Check columns.
    Then I should see "Name" in the "assign_overview_collapsible" "region"
    And I should see "Due date" in the "assign_overview_collapsible" "region"
    And I should see "Submissions" in the "assign_overview_collapsible" "region"
    And I should see "Actions" in the "assign_overview_collapsible" "region"
    # Check Due dates.
    And I should see "1 January 2040" in the "Date assign" "table_row"
    And I should see "1 January 2040" in the "No submissions" "table_row"
    And I should see "-" in the "Pending grades" "table_row"
    # Check Submissions.
    And I should see "1 of 8" in the "Date assign" "table_row"
    And I should see "0 of 8" in the "No submissions" "table_row"
    And I should see "2 of 8" in the "Pending grades" "table_row"
    # Check main actions.
    And I should see "Grade" in the "Date assign" "table_row"
    And I should see "Grade" in the "No submissions" "table_row"
    And I should see "Grade" in the "Pending grades" "table_row"
    And I should see "(2)" in the "Pending grades" "table_row"

  Scenario: Students can see relevant columns in the assign overview
    When I am on the "Course 1" "course > activities > assign" page logged in as "student1"
    # Check columns.
    Then I should see "Name" in the "assign_overview_collapsible" "region"
    And I should see "Due date" in the "assign_overview_collapsible" "region"
    And I should see "Submission status" in the "assign_overview_collapsible" "region"
    And I should see "Grade" in the "assign_overview_collapsible" "region"
    # Check Due dates.
    And I should see "1 January 2040" in the "Date assign" "table_row"
    And I should see "1 January 2040" in the "No submissions" "table_row"
    And I should see "-" in the "Pending grades" "table_row"
    # Check Submission status.
    And I should see "Submitted for grading" in the "Date assign" "table_row"
    And I should see "No submission" in the "No submissions" "table_row"
    And I should see "-" in the "Submitted for grading" "table_row"
    # Check Grade.
    And I should see "-" in the "Date assign" "table_row"
    And I should see "-" in the "No submissions" "table_row"
    And I should see "50.00" in the "Pending grades" "table_row"
