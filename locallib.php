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
 * Strings for component 'doublemark', language 'en'
 *
 * @package   assignfeedback_doublemark
 * @copyright 2017 Southampton Solent University {@link mailto: ltu@solent.ac.uk}
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
        $scaleoptions = $this->get_scale();

        if ($grade) {
            $doublemarks = $this->get_doublemarks($grade->id);
        }

        if ($doublemarks) {
            if ($doublemarks->first_grade != '-1') {
                $select_first = $mform->addElement('select', 'assignfeedback_doublemark_first_grade', get_string('first_grade', 'assignfeedback_doublemark'), $scaleoptions);
                $select_first->setSelected($doublemarks->first_grade);
            } else {
                $mform->addElement('select', 'assignfeedback_doublemark_first_grade', get_string('first_grade', 'assignfeedback_doublemark'), $scaleoptions);
            }

            if ($doublemarks->second_grade != '-1') {
                $select_second = $mform->addElement('select', 'assignfeedback_doublemark_second_grade', get_string('second_grade', 'assignfeedback_doublemark'), $scaleoptions);
                $select_second->setSelected($doublemarks->second_grade);
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
//            $mform->disabledIf('assignfeedback_doublemark_first_grade', 'first_hidden', 'neq', -1);
//            $mform->disabledIf('assignfeedback_doublemark_second_grade', 'second_hidden', 'neq', -1);


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
            $grades = "1:  " . $scaleoptions[$doublemarks->first_grade] . " - " . $first_grader->firstname . " " . $first_grader->lastname . "<br>" .
                    "2: " . $scaleoptions[$doublemarks->second_grade] . " - " . $second_grader->firstname . " " . $second_grader->lastname;
            return format_text($grades, FORMAT_HTML);
        }
        return '';
    }

    // /**
    // * Display the comment in the feedback table.
    // *
    // * @param stdClass $grade
    // * @return string
    // */
    // public function view(stdClass $grade) {
    // $doublemarks = $this->get_doublemarks($grade->id);
    // if ($doublemarks) {
    // return format_text($doublemarks->first_grade,
    // $doublemarks->second_grade,
    // array('context' => $this->assignment->get_context()));
    // }
    // return '';
    // }
    // public function can_upgrade($type, $version) {
    // if (($type == 'upload' || $type == 'uploadsingle') && $version >= 2011112900) {
    // return true;
    // }
    // return false;
    // }
    // public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
    // // first upgrade settings (nothing to do)
    // return true;
    // }
    // public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmission, stdClass $grade, & $log) {
    // global $DB;
    // // now copy the area files
    // $this->assignment->copy_area_files_for_upgrade($oldcontext->id,
    // 'mod_assignment',
    // 'response',
    // $oldsubmission->id,
    // // New file area
    // $this->assignment->get_context()->id,
    // 'assignfeedback_file',
    // ASSIGNFEEDBACK_FILE_FILEAREA,
    // $grade->id);
    // // now count them!
    // $filefeedback = new stdClass();
    // $filefeedback->numfiles = $this->count_files($grade->id, ASSIGNFEEDBACK_FILE_FILEAREA);
    // $filefeedback->grade = $grade->id;
    // $filefeedback->assignment = $this->assignment->get_instance()->id;
    // if (!$DB->insert_record('assignfeedback_file', $filefeedback) > 0) {
    // $log .= get_string('couldnotconvertgrade', 'mod_assign', $grade->userid);
    // return false;
    // }
    // return true;
    // }
    // public function is_empty(stdClass $submission) {
    // return $this->count_files($submission->id, ASSIGNSUBMISSION_FILE_FILEAREA) == 0;
    // }
    // public function get_file_areas() {
    // return array(ASSIGNFEEDBACK_FILE_FILEAREA=>$this->get_name());
    // }
    // public function format_for_gradebook(stdClass $grade) {
    // return FORMAT_MOODLE;
    // }
    // public function text_for_gradebook(stdClass $grade) {
    // return '';
    // }
    // /**
    // * Run cron for this plugin
    // */
    // public static function cron() {
    // }
    // /**
    // * Return a list of the grading actions supported by this plugin.
    // *
    // * A grading action is a page that is not specific to a user but to the whole assignment.
    // * @return array - An array of action and description strings.
    // *                 The action will be passed to grading_action.
    // */
    // public function get_grading_actions() {
    // return array();
    // }
    // /**
    // * Show a grading action form
    // *
    // * @param string $gradingaction The action chosen from the grading actions menu
    // * @return string The page containing the form
    // */
    // public function grading_action($gradingaction) {
    // return '';
    // }
    // /**
    // * Return a list of the batch grading operations supported by this plugin.
    // *
    // * @return array - An array of action and description strings.
    // *                 The action will be passed to grading_batch_operation.
    // */
    // public function get_grading_batch_operations() {
    // return array();
    // }
    // /**
    // * Show a batch operations form
    // *
    // * @param string $action The action chosen from the batch operations menu
    // * @param array $users The list of selected userids
    // * @return string The page containing the form
    // */
    // public function grading_batch_operation($action, $users) {
    // return '';
    // }
}
