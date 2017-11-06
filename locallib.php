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
 * @package   assignfeedback_doublemark
 * @copyright 2017 Southampton Solent University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class assign_feedback_doublemark extends assign_feedback_plugin {

    public function get_name() {
        return get_string('doublemark', 'assignfeedback_doublemark');
    }

    public function get_doublemarks($gradeid) {
        global $DB;
        return $DB->get_record('assignfeedback_doublemark', array('grade' => $gradeid));
    }

    public function get_scale() {
        global $DB;
        $scale = $DB->get_record('scale', array('id' => $this->assignment->get_grade_item()->scaleid));
        if ($scale) {
            $nograde = array(-1 => "No grade");
            $scaleoptions = make_menu_from_list($scale->scale);
            $scaleoptions = $nograde + $scaleoptions;
        }
        return $scaleoptions;
    }

    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid) {
		global $USER;
        $scaleoptions = $this->get_scale();

        if ($grade) {
            $doublemarks = $this->get_doublemarks($grade->id);
        }

        if ($doublemarks) {
            if ($doublemarks->first_grade != '-1') {
                $select_first = $mform->addElement('select', 'assignfeedback_doublemark_first_grade', get_string('first_grade', 'assignfeedback_doublemark'), $scaleoptions);
                $select_first->setSelected($doublemarks->first_grade);
				$mform->addElement('hidden', 'grader1_hidden', $doublemarks->first_userid);
            } else {
                $mform->addElement('select', 'assignfeedback_doublemark_first_grade', get_string('first_grade', 'assignfeedback_doublemark'), $scaleoptions);
            }

            if ($doublemarks->second_grade != '-1') {
                $select_second = $mform->addElement('select', 'assignfeedback_doublemark_second_grade', get_string('second_grade', 'assignfeedback_doublemark'), $scaleoptions);
                $select_second->setSelected($doublemarks->second_grade);
				$mform->addElement('hidden', 'grader2_hidden', $doublemarks->second_userid);
            } else {
                $mform->addElement('select', 'assignfeedback_doublemark_second_grade', get_string('second_grade', 'assignfeedback_doublemark'), $scaleoptions);
            }
            $mform->addElement('hidden', 'first_hidden', $doublemarks->first_grade);
            $mform->addElement('hidden', 'second_hidden', $doublemarks->second_grade); 
        } else {
            $mform->addElement('select', 'assignfeedback_doublemark_first_grade', get_string('first_grade', 'assignfeedback_doublemark'), $scaleoptions);
            $mform->addElement('select', 'assignfeedback_doublemark_second_grade', get_string('second_grade', 'assignfeedback_doublemark'), $scaleoptions);
            $mform->addElement('hidden', 'first_hidden', -1);
            $mform->addElement('hidden', 'second_hidden', -1);
        }        
        
		$mform->disabledIf('assignfeedback_doublemark_second_grade', 'first_hidden', 'eq', -1);
		$mform->disabledIf('assignfeedback_doublemark_second_grade', 'grader1_hidden', 'eq', $USER->id);
		$mform->disabledIf('assignfeedback_doublemark_first_grade', 'grader1_hidden', 'neq', $USER->id);

        return true;
    }

    /**
     * Get the double marking grades from the database.
     *
     * @param int $gradeid
     * @return stdClass|false The double marking grades for the given grade if it exists.
     *                        False if it doesn't.
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        if ($grade) {
            $doublemarks = $this->get_doublemarks($grade->id);
        }

        if ($doublemarks) {
            if ($doublemarks->first_grade == $data->assignfeedback_doublemark_first_grade &&
                    $doublemarks->second_grade == $data->assignfeedback_doublemark_second_grade) {
                return false;
            }
        } else {
            return true;
        }
        return true;
    }

    public function save(stdClass $grade, stdClass $data) {
        global $DB, $USER;
        $doublemarks = $this->get_doublemarks($grade->id);
        if ($doublemarks) {
            if ($data->assignfeedback_doublemark_first_grade !== $doublemarks->first_grade) {
                $doublemarks->first_grade = $data->assignfeedback_doublemark_first_grade;
                $doublemarks->first_userid = $USER->id;                
            }

            if (isset($data->assignfeedback_doublemark_second_grade) && $data->assignfeedback_doublemark_second_grade !== $doublemarks->second_grade) {
                $doublemarks->second_grade = $data->assignfeedback_doublemark_second_grade;
                $doublemarks->second_userid = $USER->id;                
            }
			
			return $DB->update_record('assignfeedback_doublemark', $doublemarks);
			
        } else {
            $doublemarks = new stdClass();
            $doublemarks->assignment = $this->assignment->get_instance()->id;
            $doublemarks->grade = $grade->id;
            $doublemarks->first_grade = $data->assignfeedback_doublemark_first_grade;
            $doublemarks->first_userid = $USER->id;
            return $DB->insert_record('assignfeedback_doublemark', $doublemarks) > 0;
        }
    }

    /**
     * Display the comment in the feedback table.
     *
     * @param stdClass $grade
     * @param bool $showviewlink Set to true to show a link to view the full feedback
     * @return string
     */
    public function view_summary(stdClass $grade, & $showviewlink) {
		global $DB;
        $doublemarks = $this->get_doublemarks($grade->id);
        if ($doublemarks) {
            $scaleoptions = $this->get_scale();
            $first_grader = $DB->get_record('user', array('id' => $doublemarks->first_userid));
            $second_grader = $DB->get_record('user', array('id' => $doublemarks->second_userid));
            $grades = '<ol style="margin:0;"><li>' . $scaleoptions[$doublemarks->first_grade] . " - " . $first_grader->firstname . " " . $first_grader->lastname . '</li>' .
                    '<li>' . $scaleoptions[$doublemarks->second_grade] . " - " . $second_grader->firstname . " " . $second_grader->lastname . '</li></ol>';
            return format_text($grades, FORMAT_HTML);
        }
        return '';
    }   
}
