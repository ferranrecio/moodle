@core @core_courseformat @show_editor @javascript
Feature: Bulk course section actions.
  In order to edit the course section
  As a teacher
  I need to be able to edit sections in bulk.

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

  Scenario: Bulk delete sections with content ask for confirmation
    Given I should see "Topic 1" in the "region-main" "region"
    And I should see "Topic 2" in the "region-main" "region"
    And I should see "Topic 3" in the "region-main" "region"
    And I should see "Topic 4" in the "region-main" "region"
    And I should see "Activity sample 1" in the "Topic 1" "section"
    And I should see "Activity sample 2" in the "Topic 1" "section"
    And I should see "Activity sample 3" in the "Topic 2" "section"
    And I should see "Activity sample 4" in the "Topic 2" "section"
    And I click on "Select topic Topic 1" "checkbox"
    And I click on "Select topic Topic 2" "checkbox"
    And I should see "2 selected" in the "sticky-footer" "region"
    When I click on "Delete topics" "button" in the "sticky-footer" "region"
    And I click on "Delete" "button" in the "Delete selected topics?" "dialogue"
    Then I should see "Topic 1" in the "region-main" "region"
    And I should see "Topic 2" in the "region-main" "region"
    And I should not see "Topic 3" in the "region-main" "region"
    And I should not see "Topic 4" in the "region-main" "region"
    And I should not see "Activity sample 1" in the "Topic 1" "section"
    And I should not see "Activity sample 2" in the "Topic 1" "section"
    And I should not see "Activity sample 3" in the "Topic 2" "section"
    And I should not see "Activity sample 4" in the "Topic 2" "section"
    And I should see "0 selected" in the "sticky-footer" "region"

  Scenario: Bulk delete sections without content dos not ask for confirmation
    Given I should see "Topic 1" in the "region-main" "region"
    And I should see "Topic 2" in the "region-main" "region"
    And I should see "Topic 3" in the "region-main" "region"
    And I should see "Topic 4" in the "region-main" "region"
    And I should see "Activity sample 1" in the "Topic 1" "section"
    And I should see "Activity sample 2" in the "Topic 1" "section"
    And I should see "Activity sample 3" in the "Topic 2" "section"
    And I should see "Activity sample 4" in the "Topic 2" "section"
    And I click on "Select topic Topic 3" "checkbox"
    And I click on "Select topic Topic 4" "checkbox"
    And I should see "2 selected" in the "sticky-footer" "region"
    When I click on "Delete topics" "button" in the "sticky-footer" "region"
    Then I should see "Topic 1" in the "region-main" "region"
    And I should see "Topic 2" in the "region-main" "region"
    And I should not see "Topic 3" in the "region-main" "region"
    And I should not see "Topic 4" in the "region-main" "region"
    And I should see "Activity sample 1" in the "Topic 1" "section"
    And I should see "Activity sample 2" in the "Topic 1" "section"
    And I should see "Activity sample 3" in the "Topic 2" "section"
    And I should see "Activity sample 4" in the "Topic 2" "section"
    And I should see "0 selected" in the "sticky-footer" "region"

  Scenario: Bulk delete both section with content and empty section ask for confirmation
    Given I should see "Topic 1" in the "region-main" "region"
    And I should see "Topic 2" in the "region-main" "region"
    And I should see "Topic 3" in the "region-main" "region"
    And I should see "Topic 4" in the "region-main" "region"
    And I should see "Activity sample 1" in the "Topic 1" "section"
    And I should see "Activity sample 2" in the "Topic 1" "section"
    And I should see "Activity sample 3" in the "Topic 2" "section"
    And I should see "Activity sample 4" in the "Topic 2" "section"
    And I click on "Select topic Topic 2" "checkbox"
    And I click on "Select topic Topic 3" "checkbox"
    And I should see "2 selected" in the "sticky-footer" "region"
    When I click on "Delete topics" "button" in the "sticky-footer" "region"
    And I click on "Delete" "button" in the "Delete selected topics?" "dialogue"
    Then I should see "Topic 1" in the "region-main" "region"
    And I should see "Topic 2" in the "region-main" "region"
    And I should not see "Topic 3" in the "region-main" "region"
    And I should not see "Topic 4" in the "region-main" "region"
    And I should see "Activity sample 1" in the "Topic 1" "section"
    And I should see "Activity sample 1" in the "Topic 1" "section"
    And I should see "Activity sample 2" in the "Topic 1" "section"
    And I should not see "Activity sample 3" in the "Topic 2" "section"
    And I should not see "Activity sample 4" in the "Topic 2" "section"
    And I should see "0 selected" in the "sticky-footer" "region"
