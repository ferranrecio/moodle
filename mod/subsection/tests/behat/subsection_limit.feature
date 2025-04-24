@mod @mod_subsection
Feature: Teacher can only add subsection when certain conditions are met
  In order to limit subsections
  As an teacher
  I need to create subsections only when possible

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | numsections | initsections |
      | Course 1 | C1        | 0        | 5           | 1            |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
