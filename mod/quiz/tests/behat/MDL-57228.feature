@mod @mod_quiz @wip
Feature: Edit quiz page - add question to sections
  In order to build a quiz laid out in sections the way I want
  As a teacher
  I need to be able to add questions to a section.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | T1        | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity   | name   | intro              | course | idnumber |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    |
    And the following "questions" exist:
      | questioncategory | qtype       | name | questiontext    |
      | Test questions   | truefalse   | TF1  | This is question 01 |
      | Test questions   | truefalse   | TF2  | This is question 02 |
      | Test questions   | truefalse   | TF3  | This is question 03 |
    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
      | TF2      | 2    |
      | TF3      | 3    |
    And quiz "Quiz 1" contains the following sections:
      | heading   | firstslot | shuffle |
      | Section 1 | 1         | 1       |
      | Section 2 | 2         | 1       |
      | Section 3 | 3         | 1       |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I navigate to "Edit quiz" in current page administration
    And I should see "Editing quiz: Quiz 1"

  @javascript
  Scenario: Quiz with 3 sections, one question in each, we add question to the first
    Given I open the "Page 1" add to quiz menu
    And I choose "a new question" in the open action menu
    And I set the field "item_qtype_essay" to "1"
    And I press "submitbutton"
    And I should see "Adding an Essay question"
    When I set the field "Question name" to "This is question 04"
    And I set the field "Question text" to "Please write 200 words about Essay"
    And I press "id_submitbutton"
    Then I should see "Editing quiz: Quiz 1"
    And I should see "This is question 01" on quiz page "1"
    And I should see "This is question 04" on quiz page "1"
    And I should see "This is question 02" on quiz page "2"
    And I should see "This is question 03" on quiz page "3"

  @javascript
  Scenario: Quiz with 3 sections, one question in each, we add question to the second
    Given I open the "Page 2" add to quiz menu
    And I choose "a new question" in the open action menu
    And I set the field "item_qtype_essay" to "1"
    And I press "submitbutton"
    And I should see "Adding an Essay question"
    When I set the field "Question name" to "This is question 04"
    And I set the field "Question text" to "Please write 200 words about Essay"
    And I press "id_submitbutton"
    Then I should see "Editing quiz: Quiz 1"
    And I should see "This is question 01" on quiz page "1"
    And I should see "This is question 02" on quiz page "2"
    And I should see "This is question 04" on quiz page "2"
    And I should see "This is question 03" on quiz page "3"

  @javascript
  Scenario: Quiz with 3 sections, one question in each, we add question to the third
    Given I open the "Page 3" add to quiz menu
    And I choose "a new question" in the open action menu
    And I set the field "item_qtype_essay" to "1"
    And I press "submitbutton"
    And I should see "Adding an Essay question"
    When I set the field "Question name" to "This is question 04"
    And I set the field "Question text" to "Please write 200 words about Essay"
    And I press "id_submitbutton"
    Then I should see "Editing quiz: Quiz 1"
    And I should see "This is question 01" on quiz page "1"
    And I should see "This is question 02" on quiz page "2"
    And I should see "This is question 03" on quiz page "3"
    And I should see "This is question 04" on quiz page "3"
