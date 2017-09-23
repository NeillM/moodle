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
 * Unit tests for (some of) mod/quiz/locallib.php.
 *
 * @package    mod_quiz
 * @category   test
 * @copyright  2008 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');


/**
 * Unit tests for (some of) mod/quiz/locallib.php.
 *
 * @copyright  2008 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_locallib_testcase extends advanced_testcase {

    public function test_quiz_rescale_grade() {
        $quiz = new stdClass();
        $quiz->decimalpoints = 2;
        $quiz->questiondecimalpoints = 3;
        $quiz->grade = 10;
        $quiz->sumgrades = 10;
        $this->assertEquals(quiz_rescale_grade(0.12345678, $quiz, false), 0.12345678);
        $this->assertEquals(quiz_rescale_grade(0.12345678, $quiz, true), format_float(0.12, 2));
        $this->assertEquals(quiz_rescale_grade(0.12345678, $quiz, 'question'),
            format_float(0.123, 3));
        $quiz->sumgrades = 5;
        $this->assertEquals(quiz_rescale_grade(0.12345678, $quiz, false), 0.24691356);
        $this->assertEquals(quiz_rescale_grade(0.12345678, $quiz, true), format_float(0.25, 2));
        $this->assertEquals(quiz_rescale_grade(0.12345678, $quiz, 'question'),
            format_float(0.247, 3));
    }

    public function quiz_attempt_state_data_provider() {
        return [
            [quiz_attempt::IN_PROGRESS, null, null, mod_quiz_display_options::DURING],
            [quiz_attempt::FINISHED, -90, null, mod_quiz_display_options::IMMEDIATELY_AFTER],
            [quiz_attempt::FINISHED, -7200, null, mod_quiz_display_options::LATER_WHILE_OPEN],
            [quiz_attempt::FINISHED, -7200, 3600, mod_quiz_display_options::LATER_WHILE_OPEN],
            [quiz_attempt::FINISHED, -30, 30, mod_quiz_display_options::IMMEDIATELY_AFTER],
            [quiz_attempt::FINISHED, -90, -30, mod_quiz_display_options::AFTER_CLOSE],
            [quiz_attempt::FINISHED, -7200, -3600, mod_quiz_display_options::AFTER_CLOSE],
            [quiz_attempt::FINISHED, -90, -3600, mod_quiz_display_options::AFTER_CLOSE],
            [quiz_attempt::ABANDONED, -10000000, null, mod_quiz_display_options::LATER_WHILE_OPEN],
            [quiz_attempt::ABANDONED, -7200, 3600, mod_quiz_display_options::LATER_WHILE_OPEN],
            [quiz_attempt::ABANDONED, -7200, -3600, mod_quiz_display_options::AFTER_CLOSE],
        ];
    }

    /**
     * @dataProvider quiz_attempt_state_data_provider
     *
     * @param unknown $attemptstate as in the quiz_attempts.state DB column.
     * @param unknown $relativetimefinish time relative to now when the attempt finished, or null for 0.
     * @param unknown $relativetimeclose time relative to now when the quiz closes, or null for 0.
     * @param unknown $expectedstate expected result. One of the mod_quiz_display_options constants/
     */
    public function test_quiz_attempt_state($attemptstate,
            $relativetimefinish, $relativetimeclose, $expectedstate) {

        $attempt = new stdClass();
        $attempt->state = $attemptstate;
        if ($relativetimefinish === null) {
            $attempt->timefinish = 0;
        } else {
            $attempt->timefinish = time() + $relativetimefinish;
        }

        $quiz = new stdClass();
        if ($relativetimeclose === null) {
            $quiz->timeclose = 0;
        } else {
            $quiz->timeclose = time() + $relativetimeclose;
        }

        $this->assertEquals($expectedstate, quiz_attempt_state($quiz, $attempt));
    }

    public function test_quiz_question_tostring() {
        $question = new stdClass();
        $question->qtype = 'multichoice';
        $question->name = 'The question name';
        $question->questiontext = '<p>What sort of <b>inequality</b> is x &lt; y<img alt="?" src="..."></p>';
        $question->questiontextformat = FORMAT_HTML;

        $summary = quiz_question_tostring($question);
        $this->assertEquals('<span class="questionname">The question name</span> ' .
                '<span class="questiontext">What sort of INEQUALITY is x &lt; y[?]' . "\n" . '</span>', $summary);
    }

    /**
     * Test quiz_view
     * @return void
     */
    public function test_quiz_view() {
        global $CFG;

        $CFG->enablecompletion = 1;
        $this->resetAfterTest();

        $this->setAdminUser();
        // Setup test data.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id),
                                                            array('completion' => 2, 'completionview' => 1));
        $context = context_module::instance($quiz->cmid);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        quiz_view($quiz, $course, $cm, $context);

        $events = $sink->get_events();
        // 2 additional events thanks to completion.
        $this->assertCount(3, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_quiz\event\course_module_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $moodleurl = new \moodle_url('/mod/quiz/view.php', array('id' => $cm->id));
        $this->assertEquals($moodleurl, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        // Check completion status.
        $completion = new completion_info($course);
        $completiondata = $completion->get_data($cm);
        $this->assertEquals(1, $completiondata->completionstate);
    }

    /**
     * Tests that adding questions to a quiz results in them being given the correct slot number.
     */
    public function test_quiz_add_quiz_question() {
        global $DB;
        $this->resetAfterTest(true);
        // Get generators.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        // Course and question category for the quiz.
        $course = $this->getDataGenerator()->create_course();
        $category = $questiongenerator->create_question_category();
        // Create the quiz.
        $quizparams = array(
            'course' => $course->id,
            'questionsperpage' => 1, // Questions per page set to 1 so that we can create 3 sections later.
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
        // Add question 1 to the default slot.
        quiz_add_quiz_question($question1->id, $quiz);
        $this->assertEquals(1, $DB->get_field('quiz_slots', 'slot',array('quizid' => $quiz->id, 'questionid' => $question1->id)));
        $question2params = array(
            'name' => 'Question 2',
            'category' => $category->id
        );
        $question2 = $questiongenerator->create_question('truefalse', null, $question2params);
        // Add question 2 to the default slot.
        quiz_add_quiz_question($question2->id, $quiz);
        $this->assertEquals(1, $DB->get_field('quiz_slots', 'slot',array('quizid' => $quiz->id, 'questionid' => $question1->id)));
        $this->assertEquals(2, $DB->get_field('quiz_slots', 'slot',array('quizid' => $quiz->id, 'questionid' => $question2->id)));
        $question3params = array(
            'name' => 'Question 3',
            'category' => $category->id
        );
        $question3 = $questiongenerator->create_question('truefalse', null, $question3params);
        // Add question 3 to page 3.
        quiz_add_quiz_question($question3->id, $quiz, 3);
        $this->assertEquals(1, $DB->get_field('quiz_slots', 'slot',array('quizid' => $quiz->id, 'questionid' => $question1->id)));
        $this->assertEquals(2, $DB->get_field('quiz_slots', 'slot',array('quizid' => $quiz->id, 'questionid' => $question2->id)));
        $this->assertEquals(3, $DB->get_field('quiz_slots', 'slot',array('quizid' => $quiz->id, 'questionid' => $question3->id)));
        // Setup the 3 sections in the quiz, each will contain one question.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
        $quizobj = new quiz($quiz, $cm, $course);
        $structure = \mod_quiz\structure::create_for_quiz($quizobj);
        $sections = $structure->get_sections();
        $firstsection = reset($sections);
        $structure->set_section_heading($firstsection->id, 'Section 1');
        $structure->add_section_heading(2, 'Section 2');
        $structure->add_section_heading(3, 'Section 3');
        // Adding this question to slot 1 would have caused an error before the fix for MDL-57228.
        $question4params = array(
            'name' => 'Question 4',
            'category' => $category->id
        );
        $question4 = $questiongenerator->create_question('truefalse', null, $question4params);
        quiz_add_quiz_question($question4->id, $quiz, 1);
        $this->assertEquals(1, $DB->get_field('quiz_slots', 'slot',array('quizid' => $quiz->id, 'questionid' => $question1->id)));
        $this->assertEquals(3, $DB->get_field('quiz_slots', 'slot',array('quizid' => $quiz->id, 'questionid' => $question2->id)));
        $this->assertEquals(4, $DB->get_field('quiz_slots', 'slot',array('quizid' => $quiz->id, 'questionid' => $question3->id)));
        $this->assertEquals(2, $DB->get_field('quiz_slots', 'slot',array('quizid' => $quiz->id, 'questionid' => $question4->id)));
    }
}
