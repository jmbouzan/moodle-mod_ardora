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
 * Ardora Server Pages library.
 * Functions to handle data for Server Pages (V.2).
 *
 * UPDATE V.2 PLUGIN Páginas en servidor
 *
 * @package    mod_ardora
 * @copyright  2026 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Saves or updates a Server Page entry in Moodle database.
 *
 * @param int $courseid The course ID.
 * @param string $ardoraid The Ardora instance ID.
 * @param int $userid The user ID.
 * @param string $type The content type (e.g. 'comentarios').
 * @param int $father The parent ID (for nested comments).
 * @param array $fields Array of fields (field01 to field15).
 * @param int|null $id Optional entry ID for updates.
 * @return int The ID of the saved/updated record.
 */
function mod_ardora_save_server_page_data($courseid, $ardoraid, $userid, $type, $father = 0,
        $fields = [], $id = null, $folder = '') {
    global $DB;

    $record = new stdClass();
    if ($id) {
        $record->id = $id;
    }
    $record->courseid = $courseid;
    $record->ardora_id = $ardoraid;
    $record->userid = $userid;
    $record->type = $type;
    $record->father = $father;
    // DEPURACIÓN STICKY.
    $record->folder = $folder;
    $record->dt = time();

    for ($i = 1; $i <= 30; $i++) {
        $fieldname = sprintf('field%02d', $i);
        if (isset($fields[$fieldname])) {
            $record->$fieldname = $fields[$fieldname];
        }
    }

    if ($id) {
        $DB->update_record('ardora_server_pages', $record);
        return $id;
    } else {
        return $DB->insert_record('ardora_server_pages', $record);
    }
}

/**
 * Retrieves Server Page data for a specific activity.
 *
 * @param int $courseid The course ID.
 * @param string $ardoraid The Ardora instance ID.
 * @param string|null $type Optional content type filter.
 * @return array Array of records.
 */
function mod_ardora_get_server_page_data($courseid, $ardoraid, $type = null, $folder = null) {
    global $DB;

    $params = ['courseid' => $courseid, 'ardora_id' => $ardoraid];
    if ($type) {
        $params['type'] = $type;
    }
    // DEPURACIÓN STICKY.
    if ($folder) {
        $params['folder'] = $folder;
    }

    $records = $DB->get_records('ardora_server_pages', $params, 'dt ASC');
    return $records;
}

/**
 * Deletes a Server Page entry and its children if applicable.
 *
 * @param int $id The entry ID.
 * @param int $userid The user ID performing the action.
 * @param bool $isadmin Whether the user has administrative/teacher privileges.
 * @return bool True on success.
 * @throws moodle_exception If permission is denied.
 */
function mod_ardora_delete_server_page_data($id, $userid, $isadmin = false) {
    global $DB;

    $record = $DB->get_record('ardora_server_pages', ['id' => $id], '*', MUST_EXIST);

    // Check ownership or admin rights.
    if ($record->userid != $userid && !$isadmin) {
        throw new moodle_exception('nopermission', 'mod_ardora');
    }

    // Delete children first.
    $DB->delete_records('ardora_server_pages', ['father' => $id]);

    // Delete the record itself.
    return $DB->delete_records('ardora_server_pages', ['id' => $id]);
}
