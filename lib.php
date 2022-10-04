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
 * Lib file for doublemark
 * @package   assignfeedback_doublemark
 * @copyright 2017 Southampton Solent University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Provides double marking fields for grading.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param array $args
 * @return bool
 */
function assignfeedback_doublemark_pluginfile($course,
                                        $cm,
                                        context $context,
                                        $args) {
    global $USER, $DB;
    // I think we can just return false at the top, or not bother with this function.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);
    $itemid = (int)array_shift($args);
    $userid = $record->userid;

    if (!$assign = $DB->get_record('assign', array('id' => $cm->instance))) {
        return false;
    }

    if ($assign->id != $record->assignment) {
        return false;
    }

    // Check is users feedback or has grading permission.
    if ($USER->id != $userid && !has_capability('mod/assign:grade', $context)) {
        return false;
    }
}
