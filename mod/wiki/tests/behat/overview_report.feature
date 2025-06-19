@mod @mod_wiki
Feature: Testing overview integration in mod_wiki
In order to summarize the wikis
  As a user
  I need to be able to see the wiki overview

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | 1        |
      | student2 | Student   | 2        |
      | student3 | Student   | 3        |
      | teacher1  | Teacher  | T        |
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
      | activity | course | name          | idnumber | wikimode      | firstpagetitle  | groupmode |
      | wiki     | C1     | Separate wiki | wiki1    | collaborative | Separate page 1 | 1         |
      | wiki     | C1     | Visible wiki  | wiki2    | collaborative | Visible page 1  | 2         |

  Scenario: The wiki overview report should generate log events
    Given I am on the "Course 1" "course > activities > wiki" page logged in as "teacher1"
    When I am on the "Course 1" "course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Logs" "link"
    And I click on "Get these logs" "button"
    Then I should see "Course activities overview page viewed"
    And I should see "viewed the instance list for the module 'wiki'"

  @javascript
  Scenario: Students can see relevant columns in the wiki overview
    Given I am on the "Course 1" "course > activities > wiki" page logged in as "student1"
    # Check columns.
    Then I should see "Name" in the "wiki_overview_collapsible" "region"
    And I should see "Total entries" in the "wiki_overview_collapsible" "region"
    And I should see "My entries" in the "wiki_overview_collapsible" "region"

  Scenario: Teachers can see relevant columns in the wiki overview
    Given I am on the "Course 1" "course > activities > wiki" page logged in as "teacher1"
    # Check columns.
    Then I should see "Name" in the "wiki_overview_collapsible" "region"
    And I should see "Wiki mode" in the "wiki_overview_collapsible" "region"
    And I should see "Total entries" in the "wiki_overview_collapsible" "region"

  @javascript
  Scenario: The wiki index redirect to the activities overview
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Activities" block
    And I click on "Wikis" "link" in the "Activities" "block"
    Then I should see "An overview of all activities in the course"
    And I should see "Name" in the "wiki_overview_collapsible" "region"
    And I should see "Wiki mode" in the "wiki_overview_collapsible" "region"
    And I should see "Total entries" in the "wiki_overview_collapsible" "region"
