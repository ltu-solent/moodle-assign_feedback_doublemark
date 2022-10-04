@mod @mod_assign @assignfeedback @assignfeedback_doublemark
Feature: In an assignment, teachers can provide doublemarks on student submissions
  In order to provide feedback to students on their assignments
  As a teacher, and second marker
  I need to create feedback grades against their submissions.

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
    And I navigate to "Grades > Scales" in site administration
    And I press "Add a new scale"
    And I set the following fields to these values:
      | Name  | Grademark |
      | Scale | N,S,F3,F2,F1,D3,D2,D1,C3,C2,C1,B3,B2,B1,A4,A3,A2,A1 |
    And I press "Save changes"
    And the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Test assignment name |
      | assignsubmission_onlinetext_enabled | 1                    |
      | assignfeedback_doublemark_enabled   | 1                    |

  @javascript
  Scenario: Two teachers should be able to be first or second markers
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I am on the "Test assignment name" "assign activity editing" page
    And I expand all fieldsets
    And I set the field "grade[modgrade_type]" to "Scale"
    And I set the field "grade[modgrade_scale]" to "Grademark"
    And I press "Save and display"
    And the following "mod_assign > submissions" exist:
      | assign                | user      | onlinetext                   |
      | Test assignment name  | student1  | I'm the student1 submission  |
    And I am on the "Test assignment name" Activity page
    And I follow "View all submissions"
    Then I click on "Grade" "link" in the "Student 1" "table_row"
    And I wait until the page is ready
    And I set the field "First grade" to "A1"
    And I press "Save changes"
    Then the "Second grade" "select" should be disabled
    Given I log in as "teacher2"
    And I am on the "Test assignment name" Activity page
    And I follow "View all submissions"
    Then I click on "Grade" "link" in the "Student 1" "table_row"
    Then the "First grade" "select" should be disabled
    And I set the field "Second grade" to "B2"
    And I press "Save changes"
    And I am on the "Test assignment name" Activity page
    And I follow "View all submissions"
    Then "Student 1" row "Double Marking" column of "generaltable" table should contain "A1 - Teacher 1"
    And "Student 1" row "Double Marking" column of "generaltable" table should contain "B2 - Teacher 2"
    Given I log in as "teacher1"
    And I am on the "Test assignment name" Activity page
    And I follow "View all submissions"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grade" to "A3"
    And I press "Save changes"
    And I am on the "Test assignment name" Activity page
    And I follow "View all submissions"
    Then "Student 1" row "Final grade" column of "generaltable" table should contain "A3"
