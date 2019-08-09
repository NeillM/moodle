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
 * Tests for comments when the context is frozen.
 *
 * @package    core_comment
 * @copyright  2019 University of Nottingham
 * @author     Neill Magill <neill.magill@nottingham.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for comments when the context is frozen.
 *
 * @package    core_comment
 * @copyright  2019 University of Nottingham
 * @author     Neill Magill <neill.magill@nottingham.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_context_freeze_testcase extends advanced_testcase {
    /**
     * Test that deleting a comment fails.
     *
     * @param comment $comment
     * @param int $commentid The id of a the comment.
     * @throws comment_exception
     */
    protected function delete_commnet_fails(comment $comment, $commentid) {
        try {
            $comment->delete($commentid);
            self::fail('User deleted comment');
        } catch (comment_exception $e) {
            if ($e->errorcode !== 'nopermissiontocomment') {
                // Wrong type of error.
                throw $e;
            }
            // The correct excption was thrown.
        }
    }

    /**
     * Test that comments cannot be deleted in frozen contexts.
     */
    public function test_delete() {
        global $CFG;
        require_once($CFG->dirroot . '/comment/lib.php');

        $this->resetAfterTest();
        set_config('contextlocking', 1);

        $course = self::getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $student = self::getDataGenerator()->create_and_enrol($course, 'student');

        $args = new stdClass;
        $args->context = $context;
        $args->course = $course;
        $args->area = 'page_comments';
        $args->itemid = 0;
        $args->component = 'block_comments';
        $args->linktext = get_string('showcomments');
        $args->notoggle = true;
        $args->autostart = true;
        $args->displaycancel = false;

        // Create a comment by the student.
        self::setUser($student);
        $comment = new comment($args);
        $newcomment = $comment->add('New comment');

        // Freeze the context.
        self::setAdminUser();
        $context->set_locked(true);

        // Check that the admin user cannot delete the comment.
        $admincomment = new comment($args);
        self::assertFalse($admincomment->can_delete($newcomment->id));
        self::assertFalse($admincomment->can_post());
        $this->delete_commnet_fails($admincomment, $newcomment->id);

        // Check that a student cannot delete their own comment.
        self::setUser($student);
        $studentcomment = new comment($args);
        self::assertFalse($studentcomment->can_delete($newcomment->id));
        self::assertFalse($studentcomment->can_post());
        $this->delete_commnet_fails($studentcomment, $newcomment->id);
    }
}
