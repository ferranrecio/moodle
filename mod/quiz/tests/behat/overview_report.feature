@mod @mod_quiz
Feature: Testing overview_report in mod_quiz
  In order to list all quiz in a course
  As a user
  I need to be able to see the quiz overview

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Username  | 1        |
      | student2 | Username  | 2        |
      | student3 | Username  | 3        |
      | teacher1 | Teacher   | T        |
    And the following "courses" exist:
      | fullname | shortname | groupmode |
      | Course 1 | C1        | 1         |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name    | course | idnumber | timeclose    |
      | quiz     | Quiz 1  | C1     | quiz1    | ##tomorrow## |
      | quiz     | Quiz 2  | C1     | quiz2    | 0            |
      | qbank    | Qbank 1 | C1     | qbank1   |              |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | qbank1    | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   |
      | Test questions   | truefalse | TF1  | First question |

    And quiz "Quiz 1" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    | 2.00    |
    And quiz "Quiz 2" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    | 2.00    |
    And user "student1" has attempted "Quiz 1" with responses:
      | slot | response |
      | 1    | True     |
    And user "student2" has attempted "Quiz 1" with responses:
      | slot | response |
      | 1    | True     |
    And user "student2" has attempted "Quiz 2" with responses:
      | slot | response |
      | 1    | True     |

  @javascript
  Scenario: Teacher can see the quiz relevant information in the quiz overview
    When I am on the "Course 1" "course > activities > quiz" page logged in as "teacher1"
    Then the following should exist in the "Table listing all Quiz activities" table:
      | Name   | Students who attempted | Total attempts | Due date |
      | Quiz 1 | 2 of 3                 | 2              | Tomorrow |
      | Quiz 2 | 1 of 3                 | 1              | -        |
    And I click on "2" "button" in the "Quiz 1" "table_row"
    And I should see "Allowed attempts per student: Unlimited attempts"
    And I should see "Average attempts per student: 1"
    And I press the escape key
    And "0" "button" should not exist in the "Quiz 2" "table_row"
    And I click on "1" "button" in the "Quiz 2" "table_row"
    And I should see "Allowed attempts per student: Unlimited attempts"
    And I should see "Average attempts per student: 0"
    And I press the escape key
    And I click on "View" "link" in the "Quiz 1" "table_row"
    And I should see "Results" in the "page-header" "region"

  Scenario: Students can see the quiz relevant information in the quiz overview
    When I am on the "Course 1" "course > activities > quiz" page logged in as "student1"
    Then I should not see "Actions" in the "quiz_overview_collapsible" "region"
    And I should not see "Students who attempted" in the "quiz_overview_collapsible" "region"
    And I should not see "Total attempts" in the "quiz_overview_collapsible" "region"
    And the following should exist in the "Table listing all Quiz activities" table:
      | Name   | Due date | Grade |
      | Quiz 1 | Tomorrow | 100   |
      | Quiz 2 | -        | -     |

  Scenario: The quiz index redirect to the activities overview
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Activities" block
    And I click on "Quiz" "link" in the "Activities" "block"
    Then I should see "An overview of all activities in the course, with dates and other information."
    And I should see "Name" in the "quiz_overview_collapsible" "region"
    And I should see "Students who attempted" in the "quiz_overview_collapsible" "region"
    And I should see "Total attempts" in the "quiz_overview_collapsible" "region"
    And I should see "Actions" in the "quiz_overview_collapsible" "region"

  Scenario: The quiz overview report should generate log events
    Given I am on the "Course 1" "course > activities > quiz" page logged in as "teacher1"
    When I am on the "Course 1" "course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Logs" "link"
    And I click on "Get these logs" "button"
    Then I should see "Course activities overview page viewed"
    And I should see "viewed the instance list for the module 'quiz'"
