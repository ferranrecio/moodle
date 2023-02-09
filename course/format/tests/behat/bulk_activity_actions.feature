@core @core_courseformat @show_editor @javascript
Feature: Bulk course activity actions.
  In order to edit the course activities
  As a teacher
  I need to be able to edit activities in bulk.

  Background:
    Given the following "course" exists:
      | fullname    | Course 1 |
      | shortname   | C1       |
      | category    | 0        |
      | numsections | 4        |
    And the following "activities" exist:
      | activity | name              | intro                       | course | idnumber | section |
      | assign   | Activity sample 1 | Test assignment description | C1     | sample1  | 1       |
      | assign   | Activity sample 2 | Test assignment description | C1     | sample2  | 1       |
      | assign   | Activity sample 3 | Test assignment description | C1     | sample3  | 2       |
      | assign   | Activity sample 4 | Test assignment description | C1     | sample4  | 2       |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I click on "Bulk edit" "button"
    And I should see "0 selected" in the "sticky-footer" "region"

  Scenario: Bulk delete activities
    Given I should see "Activity sample 1" in the "Topic 1" "section"
    And I should see "Activity sample 2" in the "Topic 1" "section"
    And I should see "Activity sample 3" in the "Topic 2" "section"
    And I should see "Activity sample 4" in the "Topic 2" "section"
    And I click on "Select activity Activity sample 1" "checkbox"
    And I click on "Select activity Activity sample 3" "checkbox"
    And I should see "2 selected" in the "sticky-footer" "region"
    When I click on "Delete activities" "button" in the "sticky-footer" "region"
    And I click on "Delete" "button" in the "Delete selected activities?" "dialogue"
    Then I should not see "Activity sample 1" in the "Topic 1" "section"
    And I should see "Activity sample 2" in the "Topic 1" "section"
    And I should not see "Activity sample 3" in the "Topic 2" "section"
    And I should see "Activity sample 4" in the "Topic 2" "section"
    And I should see "0 selected" in the "sticky-footer" "region"

  Scenario: Bulk move activities
    Given I should see "Activity sample 1" in the "Topic 1" "section"
    And I should see "Activity sample 2" in the "Topic 1" "section"
    And I should see "Activity sample 3" in the "Topic 2" "section"
    And I should see "Activity sample 4" in the "Topic 2" "section"
    And I click on "Select activity Activity sample 1" "checkbox"
    And I click on "Select activity Activity sample 3" "checkbox"
    And I should see "2 selected" in the "sticky-footer" "region"
    When I click on "Move activities" "button" in the "sticky-footer" "region"
    And I click on "Activity sample 2" "link" in the "Move selected activities" "dialogue"
    Then I should not see "Activity sample 1" in the "Topic 1" "section"
    And I should see "Activity sample 2" in the "Topic 1" "section"
    And I should see "Activity sample 3" in the "Topic 1" "section"
    And I should not see "Activity sample 3" in the "Topic 2" "section"
    And I should see "Activity sample 4" in the "Topic 2" "section"
    And I should see "0 selected" in the "sticky-footer" "region"
