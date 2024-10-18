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
 * Defines the restore steps for the Ardora activity module.
 *
 * @package   mod_ardora
 * @copyright  2024 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore structure step for the Ardora activity module.
 *
 * This class defines the structure required for restoring the Ardora activity data
 * from a backup file into the Moodle course.
 *
 * It extends the `restore_activity_structure_step` class, and defines the structure
 * of the data elements to be restored, including their sources, ids, and how they are
 * processed during the restore procedure.
 *
 * @package   mod_ardora
 * @copyright  2024 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ardora_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the structure for restoring the Ardora activity.
     *
     * @return array The restored structure elements.
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('ardora', '/activity/ardora');
        $paths[] = new restore_path_element('jobs', '/activity/ardora/jobs');
        $paths[] = new restore_path_element('ardora_old', '/activity/ardora/ardora_old');
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Processes the restored Ardora activity data.
     *
     * @param stdClass $data The restored activity data.
     */
    protected function process_ardora($data) {
        global $DB;

        $data = (object) $data;

        // Ensure the correct course ID is applied.
        $data->course = $this->get_courseid();

        // Insert the activity record.
        $newitemid = $DB->insert_record('ardora', $data);
        $data->contextid = $this->get_mappingid('context', $this->task->get_contextid());
        // Apply the instance mapping (old ID to new ID).
        $this->apply_activity_instance($newitemid);
    }
    /**
     * Processes the restored Ardora jobs data.
     *
     * @param stdClass $data The restored jobs data.
     */
    protected function process_jobs($data) {
        global $DB;

        $data = (object) $data;

        // Ensure the correct course ID is applied.
        $data->courseid = $this->get_courseid();
        $data->attemps = isset($data->attemps) ? $data->attemps : 0;

        // Insert the jobs record.
        $DB->insert_record('ardora_jobs', $data);
    }

    /**
     * Processes the restored Ardora old data.
     *
     * @param stdClass $data The restored old data.
     */
    protected function process_ardora_old($data) {
        global $DB;

        $data = (object) $data;

        // Ensure the correct course ID is applied.
        $data->course = $this->get_courseid();

        // Insert the old data record.
        $DB->insert_record('ardora_old', $data);
    }

    /**
     * Executes actions after the structure restoration.
     */
    protected function after_execute() {
        // Add related files after the instance has been restored.
        $this->add_related_files('mod_ardora', 'intro', null);
        $this->add_related_files('mod_ardora', 'content', null);
    }
}
