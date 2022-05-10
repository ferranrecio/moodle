@core @core_courseformat
Feature: Validate general course activities editing
    In order to edit the course content
    As a teacher
    I need to be able to manage course activities

    Background:
        Given the following "course" exists:
            | fullname         | Course 1 |
            | shortname        | C1       |
            | category         | 0        |
            | enablecompletion | 1        |
            | numsections      | 4        |
        And the following "activities" exist:
            | activity | name              | intro                       | course | idnumber | section |
            | assign   | Activity sample 1 | Test assignment description | C1     | sample1  | 1       |
            | book     | Activity sample 2 | Test book description       | C1     | sample2  | 2       |
            | choice   | Activity sample 3 | Test choice description     | C1     | sample3  | 3       |
        And I log in as "admin"
        And I am on "Course 1" course homepage with editing mode on

    @javascript
    Scenario: Delete two activities using the delete confirmation modal
        Given I open "Activity sample 3" actions menu
        And I click on "Delete" "link" in the "Activity sample 3" activity
        And I click on "Delete" "button" in the ".modal-dialog" "css_element"
        And I should see "Activity sample 1"
        And I should see "Activity sample 2"
        And I should not see "Activity sample 3"
        When I open "Activity sample 2" actions menu
        And I click on "Delete" "link" in the "Activity sample 2" activity
        And I click on "Delete" "button" in the ".modal-dialog" "css_element"
        Then I should see "Activity sample 1"
        And I should not see "Activity sample 2"
        And I should not see "Activity sample 3"

    @javascript
    Scenario: Delete two activities using the don't ask me again option
        Given I open "Activity sample 3" actions menu
        And I click on "Delete" "link" in the "Activity sample 3" activity
        And I click on "Don't ask me again" "checkbox" in the ".modal-dialog" "css_element"
        And I click on "Delete" "button" in the ".modal-dialog" "css_element"
        And I should see "Activity sample 1"
        And I should see "Activity sample 2"
        And I should not see "Activity sample 3"
        When I open "Activity sample 2" actions menu
        And I click on "Delete" "link" in the "Activity sample 2" activity
        Then I should see "Activity sample 1"
        And I should not see "Activity sample 2"
        And I should not see "Activity sample 3"
