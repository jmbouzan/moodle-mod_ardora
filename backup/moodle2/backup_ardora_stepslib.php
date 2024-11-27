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
 * Defines the backup steps for the Ardora activity module.
 *
 * @package   mod_ardora
 * @copyright 2024 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Backup structure step for the Ardora activity module.
  *
  * @package   mod_ardora
  */
class backup_ardora_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure for the Ardora activity.
     *
     * @return backup_nested_element The backup structure for the Ardora activity.
     */
    protected function define_structure() {

        // Define each element separated.
        $ardora = new backup_nested_element('ardora', ['id'], [
            'course', 'name', 'ardora_id', 'intro', 'introformat', 'tobemigrated', 'legacyfiles',
            'legacyfileslast', 'display', 'displayoptions', 'filterfiles', 'revision', 'timemodified',
        ]);

        $jobs = new backup_nested_element('jobs', ['id'], [
            'courseid', 'userid', 'datajob', 'father', 'type', 'paq_name', 'ardora_id',
            'activity', 'hstart', 'hend', 'state', 'attempts', 'points',
        ]);

        $ardoraold = new backup_nested_element('ardora_old', ['id'], [
            'course', 'name', 'ardora_id', 'type', 'reference', 'intro', 'introformat', 'alltext', 'popup',
            'options', 'timemodified', 'oldid', 'cmid', 'newmodule', 'newid', 'migrated',
        ]);

        // Build the tree.
        $ardora->add_child($jobs);
        $ardora->add_child($ardoraold);

        // Define sources.
        $ardora->set_source_table('ardora', ['id' => backup::VAR_ACTIVITYID]);
        $jobs->set_source_table('ardora_jobs', ['courseid' => backup::VAR_COURSEID]);
        $ardoraold->set_source_table('ardora_old', ['course' => backup::VAR_COURSEID]);

        // Annotate files for the backup.
        $ardora->annotate_files('mod_ardora', 'intro', null);
        $ardora->annotate_files('mod_ardora', 'content', null);

        return $this->prepare_activity_structure($ardora);
    }
}
