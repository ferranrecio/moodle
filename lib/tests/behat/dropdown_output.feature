@core @javascript
Feature: Test dropdown output module
    In order to show extra information to the user
    As a user
    I need to interact with the dropdown output modules

    Background:
        # Get to the fixture page.
        Given I log in as "admin"
        And I am on fixture page "/lib/tests/behat/fixtures/dropdown_output_testpage.php"
        And I should not see "Dialog content"

    Scenario: User can open a modal dropdown dialog
        When I click on "Open dialog" "button" in the "regularscenario" "region"
        Then I should see "Dialog content" in the "regularscenario" "region"

    Scenario: Dropdown dialog can have rich content inside
        When I click on "Open dialog" "button" in the "richcontent" "region"
        Then I should see "Some rich content" in the "richcontent" "region"
        And "Link 1" "link" should exist in the "richcontent" "css_element"

    # Scenario: HTML attributtes can be overriden in the dropdown dialog
    #     When I click on "Open dialog" "button" in the "classes" "region"
    #     Then I should see "Some rich content" in the "classes" "region"
    #     And "Link 1" "link" should exist in the "richcontent" "css_element"
