@mod @mod_data @javascript
Feature: Users can use predefined presets
  In order to use presets
  As a user
  I need to select an existing preset

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name                | intro           | course | idnumber |
      | data     | Mountain landscapes | introduction... | C1     | data1    |
    And the following "mod_data > fields" exist:
      | database | type | name              | description              |
      | data1    | text | Test field name   | Test field description   |

  Scenario: If Teacher use another preset then the previous fields are removed
    Given I am on the "Mountain landscapes" "data activity" page logged in as teacher1
    And I follow "Fields"
    And I should see "Test field name"
    And I follow "Presets"
    And I click on "fullname" "radio" in the "Image gallery" "table_row"
    And I click on "Use this preset" "button"
    And I should see "Apply preset Image gallery"
    When I click on "Apply preset" "button"
    Then I should see "The preset has been successfully applied."
    And I follow "Fields"
    And I should see "image"
    And I should see "title"
    And I should not see "Test field name"

  Scenario: Using a preset could create new fields
    Given the following "mod_data > fields" exist:
      | database | type | name    |
      | data1    | text | title   |
    And I am on the "Mountain landscapes" "data activity" page logged in as teacher1
    And I follow "Fields"
    And I should see "title"
    And I should not see "Description"
    And I should not see "image"
    And I follow "Presets"
    And I click on "fullname" "radio" in the "Image gallery" "table_row"
    And I click on "Use this preset" "button"
    And I should see "Apply preset Image gallery"
    And I click on "Map fields" "button"
    And I should see "Fields mappings"
    And I click on "Continue" "button"
    And I should see "The preset has been successfully applied"
    And I click on "Continue" "button"
    When I follow "Fields"
    Then I should see "title"
    And I should see "description" in the "description" "table_row"
    And I should see "image" in the "image" "table_row"

  Scenario: Using a preset could create map fields
    Given the following "mod_data > fields" exist:
      | database | type | name            |
      | data1    | text | oldtitle        |
    And I am on the "Mountain landscapes" "data activity" page logged in as teacher1
    And I follow "Fields"
    And I should see "oldtitle"
    And I should not see "Description"
    And I should not see "image"
    And I follow "Presets"
    And I click on "fullname" "radio" in the "Image gallery" "table_row"
    And I click on "Use this preset" "button"
#    Let's map a field that is not mapped by default
    And I should see "Apply preset Image gallery"
    When I click on "Map fields" "button"
    And I should see "Create a new field" in the "oldtitle" "table_row"
    And I set the field "id_title" to "Map to oldtitle"
    And I click on "Continue" "button"
    And I should see "The preset has been successfully applied"
    And I click on "Continue" "button"
    And I follow "Fields"
    Then I should not see "oldtitle"
    And I should see "title"
    And I should see "description" in the "description" "table_row"
    And I should see "image" in the "image" "table_row"

  Scenario: Teacher can use a preset from presets page on a database with entries
    And the following "mod_data > entries" exist:
      | database | Test field name |
      | data1    | Student entry 1 |
    Given I am on the "Mountain landscapes" "data activity" page logged in as teacher1
    And I follow "Presets"
    And I click on "fullname" "radio" in the "Image gallery" "table_row"
    And the "Use this preset" "button" should be enabled
    When I click on "Use this preset" "button"
    And I should see "Apply preset Image gallery"
    And I click on "Map fields" "button"
    Then I should see "Fields mappings"
    And I should see "image"
    And I should see "Create a new field" in the "image" "table_row"
    And I click on "Continue" "button"
    And I should see "The preset has been successfully applied"
    And I follow "Fields"
    And I should see "image"

  Scenario: Using same preset twice doesn't show mapping dialogue
    Given the following "mod_data > fields" exist:
      | database | type | name    |
      | data1    | text | title   |
    And I am on the "Mountain landscapes" "data activity" page logged in as teacher1
    And I follow "Presets"
    And I click on "fullname" "radio" in the "Image gallery" "table_row"
    When I click on "Use this preset" "button"
    And I should see "Apply preset Image gallery"
    And I click on "Apply preset" "button"
    And I should see "The preset has been successfully applied"
    And I follow "Presets"
    And I click on "fullname" "radio" in the "Image gallery" "table_row"
    And I click on "Use this preset" "button"
    Then I should not see "Apply preset Image gallery"
    And I should see "The preset has been successfully applied"

  Scenario: If Teacher use another from preview page preset then the previous fields are removed
    Given I am on the "Mountain landscapes" "data activity" page logged in as teacher1
    And I follow "Fields"
    And I should see "Test field name"
    And I follow "Presets"
    And I click on "Image gallery" "link"
    And I click on "Use this preset" "button"
    And I click on "Map fields" "button"
    And I should see "Fields mappings"
    When I click on "Continue" "button"
    Then I should see "The preset has been successfully applied."
    And I follow "Fields"
    And I should see "image"
    And I should see "title"
    And I should not see "Test field name"

  Scenario: Using a preset from preview page could create new fields
    Given the following "mod_data > fields" exist:
      | database | type | name    |
      | data1    | text | title   |
    And I am on the "Mountain landscapes" "data activity" page logged in as teacher1
    And I follow "Fields"
    And I should see "title"
    And I should not see "Description"
    And I should not see "image"
    And I follow "Presets"
    And I click on "Image gallery" "link"
    And I click on "Use this preset" "button"
    And I should see "Apply preset Image gallery"
    When I click on "Apply preset" "button"
    And I should see "The preset has been successfully applied"
    And I follow "Fields"
    Then I should see "title"
    And I should see "description" in the "description" "table_row"
    And I should see "image" in the "image" "table_row"

  Scenario: Using a preset from preview page could create map fields
    Given the following "mod_data > fields" exist:
      | database | type | name            |
      | data1    | text | oldtitle        |
    And I am on the "Mountain landscapes" "data activity" page logged in as teacher1
    And I follow "Fields"
    And I should see "oldtitle"
    And I should not see "Description"
    And I should not see "image"
    And I follow "Presets"
    And I click on "Image gallery" "link"
    And I click on "Use this preset" "button"
#    Let's map a field that is not mapped by default
    And I should see "Apply preset Image gallery"
    When I click on "Map fields" "button"
    And I should see "Create a new field" in the "oldtitle" "table_row"
    And I set the field "id_title" to "Map to oldtitle"
    And I click on "Continue" "button"
    And I should see "The preset has been successfully applied"
    And I click on "Continue" "button"
    And I follow "Fields"
    Then I should not see "oldtitle"
    And I should see "title"
    And I should see "description" in the "description" "table_row"
    And I should see "image" in the "image" "table_row"

  Scenario: Teacher can use a preset from presets preview page on a database with entries
    And the following "mod_data > entries" exist:
      | database | Test field name |
      | data1    | Student entry 1 |
    Given I am on the "Mountain landscapes" "data activity" page logged in as teacher1
    And I follow "Presets"
    And I click on "Image gallery" "link"
    And the "Use this preset" "button" should be enabled
    When I click on "Use this preset" "button"
    And I should see "Apply preset Image gallery"
    And I click on "Map fields" "button"
    Then I should see "Fields mappings"
    And I should see "image"
    And I should see "Create a new field" in the "image" "table_row"
    And I click on "Continue" "button"
    And I should see "The preset has been successfully applied"
    And I follow "Fields"
    And I should see "image"

  Scenario: Using same preset twice from preview page doesn't show mapping dialogue
    Given the following "mod_data > fields" exist:
      | database | type | name    |
      | data1    | text | title   |
    And I am on the "Mountain landscapes" "data activity" page logged in as teacher1
    And I follow "Presets"
    And I click on "Image gallery" "link"
    When I click on "Use this preset" "button"
    And I should see "Apply preset Image gallery"
    And I click on "Map fields" "button"
    And I should see "Fields mappings"
    And I click on "Continue" "button"
    And I should see "The preset has been successfully applied"
    And I click on "Continue" "button"
    And I follow "Presets"
    And I click on "Image gallery" "link"
    And I click on "Use this preset" "button"
    Then I should not see "Fields mappings"
    And I should see "The preset has been successfully applied"
