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
 * Double mark locallib
 * @package   assignfeedback_doublemark
 * @copyright 2017 Southampton Solent University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Double mark feedback class
 */
class assign_feedback_doublemark extends assign_feedback_plugin {

    /**
     * Get the name of the doublemark feedback plugin.
     *
     * @return string
     */
    public function get_name() {
        return get_string('doublemark', 'assignfeedback_doublemark');
    }

    /**
     * Get the doublemark record, if it exists.
     *
     * @param int $gradeid
     * @return stdClass|false False if it doesn't exist.
     */
    public function get_doublemarks($gradeid) {
        global $DB;
        return $DB->get_record('assignfeedback_doublemark', array('grade' => $gradeid));
    }

    /**
     * Get the scale menu used by the assignment
     *
     * @return array
     */
    public function get_scale() {
        global $DB;
        $scale = $DB->get_record('scale', array('id' => $this->assignment->get_grade_item()->scaleid));
        if ($scale) {
            $nograde = array(-1 => "No grade");
            $scaleoptions = make_menu_from_list($scale->scale);
            $scaleoptions = $nograde + $scaleoptions;
        }
        // What happens if there's no scale?
        return $scaleoptions;
    }

    /**
     * Get form elements for the grading page
     *
     * @param stdClass|null $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @param int $userid
     * @return bool true if elements were added to the form
     */
    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid) {
        global $USER;
        $scaleoptions = $this->get_scale();
        if ($scaleoptions) {
            if ($grade) {
                $doublemarks = $this->get_doublemarks($grade->id);
            }
            if ($this->assignment->get_grade_item()->locked == 0) {
                // If any grades have been saved, add them to the form.
                if ($doublemarks) {
                    // First marker has already graded. Set grade and hidden userid field for first marker.
                    if ($doublemarks->first_grade != '-1') {
                        $selectfirst = $mform->addElement(
                            'select',
                            'assignfeedback_doublemark_first_grade',
                            get_string('first_grade', 'assignfeedback_doublemark'),
                            $scaleoptions);
                        $selectfirst->setSelected($doublemarks->first_grade);
                        $mform->addElement('hidden', 'grader1_hidden', $doublemarks->first_userid);
                    } else {
                        // Display grade options for First marker.
                        $mform->addElement(
                            'select',
                            'assignfeedback_doublemark_first_grade',
                            get_string('first_grade', 'assignfeedback_doublemark'),
                            $scaleoptions);
                    }
                    // Second marker has already graded. Set grade and hidden userid field for the second marker.
                    if ($doublemarks->second_grade != '-1') {
                        $selectsecond = $mform->addElement(
                            'select',
                            'assignfeedback_doublemark_second_grade',
                            get_string('second_grade', 'assignfeedback_doublemark'),
                            $scaleoptions);
                        $selectsecond->setSelected($doublemarks->second_grade);
                        $mform->addElement('hidden', 'grader2_hidden', $doublemarks->second_userid);
                    } else {
                        // Display grade options for Second marker.
                        $mform->addElement('select',
                            'assignfeedback_doublemark_second_grade',
                            get_string('second_grade', 'assignfeedback_doublemark'),
                            $scaleoptions);
                    }
                    $mform->addElement('hidden', 'first_hidden', $doublemarks->first_grade);
                    $mform->addElement('hidden', 'second_hidden', $doublemarks->second_grade);
                } else {
                    // No grades have been saved yet.
                    $mform->addElement('select',
                        'assignfeedback_doublemark_first_grade',
                        get_string('first_grade', 'assignfeedback_doublemark'),
                        $scaleoptions);
                    $mform->addElement('select',
                        'assignfeedback_doublemark_second_grade',
                        get_string('second_grade', 'assignfeedback_doublemark'),
                        $scaleoptions);
                    $mform->addElement('hidden', 'first_hidden', -1);
                    $mform->addElement('hidden', 'second_hidden', -1);
                }
            } else {
                // Grades have been locked for this assignment, so display the grades as text rather than a form.
                $mform->addElement('static',
                    'description',
                    get_string('first_grade', 'assignfeedback_doublemark'),
                    $scaleoptions[$doublemarks->first_grade]);
                $mform->addElement('static',
                    'description',
                    get_string('second_grade', 'assignfeedback_doublemark'),
                    $scaleoptions[$doublemarks->second_grade]);
            }
            // Re-arrange form elements so double marking comes first.
            // Get the header.
            $elements0 = array_splice($mform->_elements, 0, 1);
            // Get the double marks elements.
            $elements1 = array_splice($mform->_elements, 3);
            // Reconstruct elements.
            $mform->_elements = array_merge($elements0, $elements1, $mform->_elements);

            foreach ($mform->_elements as $key => $value) {
                if (array_key_exists($value->_attributes['name'], $mform->_elementIndex)) {
                    $mform->_elementIndex[$value->_attributes['name']] = $key;
                }
            }

            // Disable the grade selector for the opposite marker to prevent them entering both grades.
            $mform->addElement('html', '<script type="text/javascript">
            document.getElementById("id_assignfeedback_doublemark_first_grade").onchange = function () {
                    document.getElementById("id_assignfeedback_doublemark_second_grade").disabled = true;
            };
            document.getElementById("id_assignfeedback_doublemark_second_grade").onchange = function () {
                    document.getElementById("id_assignfeedback_doublemark_first_grade").disabled = true;
            };
            document.getElementById("id_grade_label").innerHTML = "' . get_string("agreedgrade", "assignfeedback_doublemark") . '";
            </script>');
            // Might be "false" if the double marks record doesn't exist. In which case, there's no need to disable.
            if ($doublemarks) {
                if ($doublemarks->first_grade != '-1') {
                    $mform->disabledIf('assignfeedback_doublemark_first_grade', 'grader1_hidden', 'neq', $USER->id);
                    $mform->disabledIf('assignfeedback_doublemark_second_grade', 'grader1_hidden', 'eq', $USER->id);
                }
                if ($doublemarks->second_grade != '-1') {
                    $mform->disabledIf('assignfeedback_doublemark_second_grade', 'grader2_hidden', 'neq', $USER->id);
                    $mform->disabledIf('assignfeedback_doublemark_first_grade', 'grader2_hidden', 'eq', $USER->id);
                }
            }
        } else {
            $mform->addElement('html', get_string('not_available', 'assignfeedback_doublemark'));
            // Re-arrange form elements so double marking comes first
            // Get the header.
            $elements0 = array_splice($mform->_elements, 0, 1);
            // Get the double marks element.
            $elements1 = array_splice($mform->_elements, -1, 1);
            // Reconstruct elements.
            $mform->_elements = array_merge($elements0, $elements1, $mform->_elements);

            foreach ($mform->_elements as $key => $value) {
                if (array_key_exists($value->_attributes['name'], $mform->_elementIndex)) {
                    $mform->_elementIndex[$value->_attributes['name']] = $key;
                }
            }
        }

        return true;
    }

    /**
     * Get the double marking grades from the database.
     *
     * @param stdClass $grade
     * @param stdClass $data Data from the form submission
     * @return boolean True if the feedback has been modified, else False.
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

    /**
     * Saving the grades into database.
     *
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $grade, stdClass $data) {
        global $DB, $USER;
        $doublemarks = $this->get_doublemarks($grade->id);
        if ($doublemarks) {
            // Does this assume the first marker always completes first?
            $first = $data->assignfeedback_doublemark_first_grade;

            if (isset($data->assignfeedback_doublemark_first_grade)
                    && $data->assignfeedback_doublemark_first_grade !== $doublemarks->first_grade) {
                $doublemarks->first_grade = $first;
                $doublemarks->first_userid = ($data->assignfeedback_doublemark_first_grade == -1 ? 0 : $USER->id);
            } else if (isset($data->assignfeedback_doublemark_second_grade)
                    && $data->assignfeedback_doublemark_second_grade !== $doublemarks->second_grade) {
                $doublemarks->second_grade =
                (
                    $data->assignfeedback_doublemark_second_grade != null ||
                    $data->assignfeedback_doublemark_second_grade != -1
                )
                ? $data->assignfeedback_doublemark_second_grade
                : -1;
                $doublemarks->second_userid = ($data->assignfeedback_doublemark_second_grade == -1 ? 0 : $USER->id);
            }

            return $DB->update_record('assignfeedback_doublemark', $doublemarks);
        } else {
            $doublemarks = new stdClass();
            $doublemarks->assignment = $this->assignment->get_instance()->id;
            $doublemarks->grade = $grade->id;
            if ($data->assignfeedback_doublemark_first_grade) {
                $doublemarks->first_grade = $data->assignfeedback_doublemark_first_grade;
                $doublemarks->first_userid = $USER->id;
            } else if ($data->assignfeedback_doublemark_second_grade) {
                $doublemarks->second_grade = $data->assignfeedback_doublemark_second_grade;
                $doublemarks->second_userid = $USER->id;
            }
            return $DB->insert_record('assignfeedback_doublemark', $doublemarks) > 0;
        }
    }

    /**
     * Display the grades in the feedback table.
     *
     * @param stdClass $grade
     * @param bool $showviewlink Set to true to show a link to view the full feedback
     * @return string
     */
    public function view_summary(stdClass $grade, & $showviewlink) {
        global $DB;
        $scaleoptions = $this->get_scale();
        if (!$scaleoptions) {
            return '';
        }

        $doublemarks = $this->get_doublemarks($grade->id);
        if ($doublemarks) {
            $firstgrader = $DB->get_record('user', array('id' => $doublemarks->first_userid));
            $secondgrader = $DB->get_record('user', array('id' => $doublemarks->second_userid));
            $grades = '';
            // Both grades have been set.
            if ($doublemarks->first_grade != -1 && $doublemarks->second_grade != -1) {
                $grades .= '<ol style="margin:0;">
                    <li>' . $scaleoptions[$doublemarks->first_grade] .
                        " - " . fullname($firstgrader) . '</li>';
                $grades .= '<li>' . $scaleoptions[$doublemarks->second_grade] . " - " . fullname($secondgrader) . '</li></ol>';
            } else if ($doublemarks->first_grade != -1 && $doublemarks->second_grade == -1) {
                // Only first grade has been set.
                $grades .= '<ol style="margin:0;">
                    <li>' . $scaleoptions[$doublemarks->first_grade] . " - " . fullname($firstgrader) . '</li></ol>';
            } else if ($doublemarks->first_grade == -1 && $doublemarks->second_grade != -1) {
                // Only the second grade has been set. Start list count at 2.
                $grades .= '<ol start="2" style="margin:0;">
                    <li>' . $scaleoptions[$doublemarks->second_grade] . " - " . fullname($secondgrader) . '</li></ol>';
            }
            return format_text($grades, FORMAT_HTML);
        }

        return '';
    }

}
