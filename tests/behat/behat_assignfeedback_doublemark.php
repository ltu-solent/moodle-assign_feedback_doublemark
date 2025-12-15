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
 * Behat steps for Double marks
 *
 * @package   assignfeedback_doublemark
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

/**
 * Behat Doublemark feedback steps
 */
class behat_assignfeedback_doublemark extends behat_base {
    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype             | name meaning                             | description                                  |
     * | View all submissions | Assignment name                          | The assignment submission page               |
     *
     * @param string $type identifies which type of page this is, e.g. 'View all submissions'.
     * @param string $identifier identifies the particular page, e.g. 'Assignment name'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch (strtolower($type)) {
            case 'view all submissions':
                $cm = $this->get_assignment_cm_by_name($identifier);
                return new moodle_url('/mod/assign/view.php', ['id' => $cm->id, 'action' => 'grading']);
                break;
        }
    }

    /**
     * Get assignment by its name
     *
     * @param string $name
     * @return stdClass The assignment coursemodule instance
     */
    protected function get_assignment_cm_by_name(string $name): stdClass {
        global $DB;
        $assign = $DB->get_record('assign', ['name' => $name], '*', MUST_EXIST);
        return get_coursemodule_from_instance('assign', $assign->id, $assign->course);
    }
}
