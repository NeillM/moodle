<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Quiz quiz_add_quiz_question() tests.
 *
 * @package   mod_quiz
 * @category  test
 * @copyright 2017 Neill Magill <nmagill@yahoo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');

/**
 * Unit tests for quiz_add_quiz_question function in locallib.php.
 *
 * @copyright  2017 Neill Magill <nmagill@yahoo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_quiz_add_quiz_question_testcase extends advanced_testcase {
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Tests adding a question to the first section of a quiz
     * that has 3 sections with a single question in each.
     */
    public function test_case_1() {
        // Get generators.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        // Course and question category for the quiz.
        $course = $this->getDataGenerator()->create_course();
        $category = $questiongenerator->create_question_category();
        // Create the quiz.
        $quizparams = array(
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 100.0,
            'sumgrades' => 2,
            'preferredbehaviour' => 'immediatefeedback',
        );
        $quiz = $quizgenerator->create_instance($quizparams);
        // Create 3 questions and add them to the quiz.
        $question1params = array(
            'name' => 'Question 1',
            'category' => $category->id
        );
        $question1 = $questiongenerator->create_question('truefalse', null, $question1params);
        quiz_add_quiz_question($question1->id, $quiz, 1);
        $question2params = array(
            'name' => 'Question 2',
            'category' => $category->id
        );
        $question2 = $questiongenerator->create_question('truefalse', null, $question2params);
        quiz_add_quiz_question($question2->id, $quiz, 2);
        $question3params = array(
            'name' => 'Question 3',
            'category' => $category->id
        );
        $question3 = $questiongenerator->create_question('truefalse', null, $question3params);
        quiz_add_quiz_question($question3->id, $quiz, 3);
        // Setup the 3 sections in the quiz, each will contain one question.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
        $quizobj = new quiz($quiz, $cm, $course);
        $structure = \mod_quiz\structure::create_for_quiz($quizobj);
        $sections = $structure->get_sections();
        $firstsection = reset($sections);
        $structure->set_section_heading($firstsection->id, 'Section 1');
        $structure->add_section_heading(2, 'Section 2');
        $structure->add_section_heading(3, 'Section 3');
        // Adding this question would have caused an error before the fix for MDL-57228
        $question4params = array(
            'name' => 'Question 4',
            'category' => $category->id
        );
        $question4 = $questiongenerator->create_question('truefalse', null, $question4params);
        quiz_add_quiz_question($question4->id, $quiz, 1);
    }

    /**
     * Tests adding a question to the first section of a quiz
     * that has 3 sections with a single question the last two sections and 2 in the first section.
     */
    public function test_case_2() {
        // Get generators.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        // Course and question category for the quiz.
        $course = $this->getDataGenerator()->create_course();
        $category = $questiongenerator->create_question_category();
        // Create the quiz.
        $quizparams = array(
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 100.0,
            'sumgrades' => 2,
            'preferredbehaviour' => 'immediatefeedback',
        );
        $quiz = $quizgenerator->create_instance($quizparams);
        // Create 3 questions and add them to the quiz.
        $question1params = array(
            'name' => 'Question 1',
            'category' => $category->id
        );
        $question1 = $questiongenerator->create_question('truefalse', null, $question1params);
        quiz_add_quiz_question($question1->id, $quiz, 1);
        $question2params = array(
            'name' => 'Question 2',
            'category' => $category->id
        );
        $question2 = $questiongenerator->create_question('truefalse', null, $question2params);
        quiz_add_quiz_question($question2->id, $quiz, 2);
        $question3params = array(
            'name' => 'Question 3',
            'category' => $category->id
        );
        $question3 = $questiongenerator->create_question('truefalse', null, $question3params);
        quiz_add_quiz_question($question3->id, $quiz, 3);
        $question4params = array(
            'name' => 'Question 4',
            'category' => $category->id
        );
        $question4 = $questiongenerator->create_question('truefalse', null, $question4params);
        quiz_add_quiz_question($question4->id, $quiz, 4);
        // Setup the 3 sections in the quiz, each will contain one question.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
        $quizobj = new quiz($quiz, $cm, $course);
        $structure = \mod_quiz\structure::create_for_quiz($quizobj);
        $sections = $structure->get_sections();
        $firstsection = reset($sections);
        $structure->set_section_heading($firstsection->id, 'Section 1');
        $structure->add_section_heading(3, 'Section 2');
        $structure->add_section_heading(4, 'Section 3');
        // Adding this question would have caused an error before the fix for MDL-57228
        $question5params = array(
            'name' => 'Question 5',
            'category' => $category->id
        );
        $question5 = $questiongenerator->create_question('truefalse', null, $question5params);
        quiz_add_quiz_question($question5->id, $quiz, 1);
    }

    /**
     * Tests adding a question to the first section of a quiz
     * that has 3 sections with a single question in the first two and two in the third.
     */
    public function test_case_3() {
        // Get generators.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        // Course and question category for the quiz.
        $course = $this->getDataGenerator()->create_course();
        $category = $questiongenerator->create_question_category();
        // Create the quiz.
        $quizparams = array(
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 100.0,
            'sumgrades' => 2,
            'preferredbehaviour' => 'immediatefeedback',
        );
        $quiz = $quizgenerator->create_instance($quizparams);
        // Create 3 questions and add them to the quiz.
        $question1params = array(
            'name' => 'Question 1',
            'category' => $category->id
        );
        $question1 = $questiongenerator->create_question('truefalse', null, $question1params);
        quiz_add_quiz_question($question1->id, $quiz, 1);
        $question2params = array(
            'name' => 'Question 2',
            'category' => $category->id
        );
        $question2 = $questiongenerator->create_question('truefalse', null, $question2params);
        quiz_add_quiz_question($question2->id, $quiz, 2);
        $question3params = array(
            'name' => 'Question 3',
            'category' => $category->id
        );
        $question3 = $questiongenerator->create_question('truefalse', null, $question3params);
        quiz_add_quiz_question($question3->id, $quiz, 3);
        $question4params = array(
            'name' => 'Question 4',
            'category' => $category->id
        );
        $question4 = $questiongenerator->create_question('truefalse', null, $question4params);
        quiz_add_quiz_question($question4->id, $quiz, 4);
        // Setup the 3 sections in the quiz, each will contain one question.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
        $quizobj = new quiz($quiz, $cm, $course);
        $structure = \mod_quiz\structure::create_for_quiz($quizobj);
        $sections = $structure->get_sections();
        $firstsection = reset($sections);
        $structure->set_section_heading($firstsection->id, 'Section 1');
        $structure->add_section_heading(2, 'Section 2');
        $structure->add_section_heading(3, 'Section 3');
        // Adding this question would have caused an error before the fix for MDL-57228
        $question5params = array(
            'name' => 'Question 5',
            'category' => $category->id
        );
        $question5 = $questiongenerator->create_question('truefalse', null, $question5params);
        quiz_add_quiz_question($question5->id, $quiz, 1);
    }
}
