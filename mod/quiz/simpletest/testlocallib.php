<?php
/**
 * Unit tests for (some of) mod/quiz/locallib.php.
 *
 * @author T.J.Hunt@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package quiz
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); /// It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/mod/quiz/locallib.php');

class quiz_locallib_test extends MoodleUnitTestCase {
    function test_quiz_questions_in_quiz() {
        $this->assertEqual(quiz_questions_in_quiz(''), '');
        $this->assertEqual(quiz_questions_in_quiz('0'), '');
        $this->assertEqual(quiz_questions_in_quiz('0,0'), '');
        $this->assertEqual(quiz_questions_in_quiz('0,0,0'), '');
        $this->assertEqual(quiz_questions_in_quiz('1'), '1');
        $this->assertEqual(quiz_questions_in_quiz('1,2'), '1,2');
        $this->assertEqual(quiz_questions_in_quiz('1,0,2'), '1,2');
        $this->assertEqual(quiz_questions_in_quiz('0,1,0,0,2,0'), '1,2');
    }

    function test_quiz_number_of_pages() {
        $this->assertEqual(quiz_number_of_pages('0'), 1);
        $this->assertEqual(quiz_number_of_pages('0,0'), 2);
        $this->assertEqual(quiz_number_of_pages('0,0,0'), 3);
        $this->assertEqual(quiz_number_of_pages('1,0'), 1);
        $this->assertEqual(quiz_number_of_pages('1,2,0'), 1);
        $this->assertEqual(quiz_number_of_pages('1,0,2,0'), 2);
        $this->assertEqual(quiz_number_of_pages('1,2,3,0'), 1);
        $this->assertEqual(quiz_number_of_pages('1,2,3,0'), 1);
        $this->assertEqual(quiz_number_of_pages('0,1,0,0,2,0'), 4);
    }

    function test_quiz_number_of_questions_in_quiz() {
        $this->assertEqual(quiz_number_of_questions_in_quiz('0'), 0);
        $this->assertEqual(quiz_number_of_questions_in_quiz('0,0'), 0);
        $this->assertEqual(quiz_number_of_questions_in_quiz('0,0,0'), 0);
        $this->assertEqual(quiz_number_of_questions_in_quiz('1,0'), 1);
        $this->assertEqual(quiz_number_of_questions_in_quiz('1,2,0'), 2);
        $this->assertEqual(quiz_number_of_questions_in_quiz('1,0,2,0'), 2);
        $this->assertEqual(quiz_number_of_questions_in_quiz('1,2,3,0'), 3);
        $this->assertEqual(quiz_number_of_questions_in_quiz('1,2,3,0'), 3);
        $this->assertEqual(quiz_number_of_questions_in_quiz('0,1,0,0,2,0'), 2);
    }
}
?>