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
 * Privacy Subsystem implementation for mod_ardora.
 * created from the "Resource module" version created by 2018 Zig Tan <zig@moodle.com>
 * @package    mod_ardora
 * @copyright  2025 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ardora\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\core_user_data_provider;

/**
 * Implementation of the privacy provider for mod_ardora.
 *
 * @copyright  2024 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements core_user_data_provider {

    /**
     * Returns metadata about this plugin.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection The updated collection of metadata items.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('ardora_jobs', [
            'userid' => 'privacy:metadata:ardora_jobs:userid',
            'courseid' => 'privacy:metadata:ardora_jobs:courseid',
            'datajob' => 'privacy:metadata:ardora_jobs:datajob',
        ], 'privacy:metadata:ardora_jobs');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The list of contexts for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "
            SELECT ctx.id
            FROM {context} ctx
            JOIN {course_modules} cm ON cm.id = ctx.instanceid
            JOIN {modules} m ON m.id = cm.module
            JOIN {ardora} a ON a.id = cm.instance
            WHERE m.name = 'ardora' AND a.userid = :userid
        ";
        $params = ['userid' => $userid];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the contexts provided.
     *
     * @param approved_contextlist $contextlist The list of approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            // Aquí debes implementar la lógica de exportación de datos.
            // Por ejemplo, se puede consultar la base de datos y luego escribir la información con writer::with_context().
            $jobs = get_user_ardora_jobs_for_context($userid, $context);
            foreach ($jobs as $job) {
                writer::with_context($context)
                    ->export_data(['ardora_job'], (object)$job);
            }
        }
    }

    /**
     * Deletes all user data for the specified context.
     *
     * This function removes all user-related records from the `ardora_jobs` table
     * for the given course context.
     *
     * @param \context $context The context for which to delete user data. Typically, a course context.
     * @return void This function does not return a value.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // Implementa la lógica para eliminar todos los datos de usuario en el contexto proporcionado.
        global $DB;
        $DB->delete_records('ardora_jobs', ['courseid' => $context->instanceid]);
    }

    /**
     * Delete all user data which matches the specified user and context.
     *
     * @param approved_contextlist $contextlist The list of approved contexts to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            // Implementa la lógica para eliminar los datos del usuario.
            $DB->delete_records('ardora_jobs', ['userid' => $userid, 'courseid' => $context->instanceid]);
        }
    }
}
