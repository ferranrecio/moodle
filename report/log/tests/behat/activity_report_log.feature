@report @report_log
Feature: In a activity page, navigate through the More / Logs menu, test for report log page
  In order to navigate through report page
  As an admin
  Go to the activity page, click on More / Logs menu, and check for the report log page

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "activities" exist:
      | activity | name        | course | section |
      | page     | Test page 1 | C1     | 1       |
      | page     | Test page 2 | C1     | 1       |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | admin    | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Report selectors should be targeted toward course module
    Given I am on the "Test page 1" Activity page logged in as "admin"
    When I navigate to "Logs" in current page administration
    Then "menuid" "select" should not exist
    And "modid" "select" should not exist
    And I should see "All participants" in the "user" "select"
    And I should see "All days" in the "date" "select"
    And I should see "All sources" in the "origin" "select"
    And I should see "All events" in the "edulevel" "select"
    And I should see "Test page 1" in the "#page-header" "css_element"

  Scenario: Report submission stays in the same course module page
    Given I am on the "Test page 1" Activity page logged in as "admin"
    When I navigate to "Logs" in current page administration
    And I click on "Get these logs" "button"
    Then I should see "Test page 1" in the "#page-header" "css_element"
