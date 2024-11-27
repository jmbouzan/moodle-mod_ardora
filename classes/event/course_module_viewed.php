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
 * The mod_ardora course module viewed event.
 *
 * @package    mod_ardora
 * @copyright  2023 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ardora\event;

/**
 * The mod_ardora course module viewed event class.
 * created from the "Resource module" version created by 2014 Rajesh Taneja <rajesh@moodle.com>
 * @package    mod_ardora
 * @since      Moodle 2.7
 * @copyright  2023 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'ardora';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }
    /**
     * Returns the mapping of the object ID for the restore process.
     *
     * This function provides the database table and the restore process name.
     * for the 'ardora' activity module during the backup and restore process.
     *
     * @return array The mapping array containing the database table and restore name.
     */
    public static function get_objectid_mapping() {
        return ['db' => 'ardora', 'restore' => 'ardora'];
    }
}
