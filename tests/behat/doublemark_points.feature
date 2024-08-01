@assignfeedback @assignfeedback_doublemark
Feature: Testing doublemark_points in assignfeedback_doublemark
  In order to provide feedback to students on their assignments
    As a teacher, and second marker
    I need to create feedback grades as Points against their submissions.

  Background:
    Given I log in as "admin"
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | teacher2 | Teacher | 2 | teacher2@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | teacher2 | C1 | teacher |
      | student1 | C1 | student |
    And the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Test assignment name |
      | idnumber                            |                      |
      | assignsubmission_onlinetext_enabled | 1                    |
      | assignfeedback_doublemark_enabled   | 1                    |
    # This is a little more long-winded so we can have an assignment with no idnumber.
    # This prevents test failure in Solent context where idnumber is indicative of
    # summative assignments.
    And I am logged in as student1
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student1 submission |
    And I press "Save changes"

  @javascript
  Scenario: Two teachers should be able to be first or second markers
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I am on the "Test assignment name" "assign activity editing" page
    And I expand all fieldsets
    And I set the field "grade[modgrade_type]" to "Point"
    And I press "Save and display"
    And I am on the "Test assignment name" "assignfeedback_doublemark > View all submissions" page
    Then I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "First grade" to "100"
    And I press "Save changes"
    Then the "Second grade" "select" should be disabled
    Given I log in as "teacher2"
    And I am on the "Test assignment name" "assignfeedback_doublemark > View all submissions" page
    Then I click on "Grade" "link" in the "Student 1" "table_row"
    Then the "First grade" "select" should be disabled
    And I set the field "Second grade" to "65"
    And I press "Save changes"
    And I am on the "Test assignment name" "assignfeedback_doublemark > View all submissions" page
    Then "Student 1" row "Double Marking" column of "generaltable" table should contain "100 / 100 - Teacher 1"
    And "Student 1" row "Double Marking" column of "generaltable" table should contain "65 / 100 - Teacher 2"
    Given I log in as "teacher1"
    And I am on the "Test assignment name" "assignfeedback_doublemark > View all submissions" page
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Agreed grade" to "83"
    And I press "Save changes"
    When I am on the "Test assignment name" "assignfeedback_doublemark > View all submissions" page
    # For some reason behat can't recognise "83.00 / 100.00" in this context cf: MDL-82664.
    Then "Student 1" row "Final grade" column of "generaltable" table should contain "83.00"
