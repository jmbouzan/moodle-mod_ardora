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
 * @copyright  2026 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ardora\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

/**
 * Implementation of the privacy provider for mod_ardora.
 *
 * @copyright  2026 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

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
            'ardora_id' => 'privacy:metadata:ardora_jobs:ardora_id',
            'datajob' => 'privacy:metadata:ardora_jobs:datajob',
            'activity' => 'privacy:metadata:ardora_jobs:activity',
            'hstart' => 'privacy:metadata:ardora_jobs:hstart',
            'hend' => 'privacy:metadata:ardora_jobs:hend',
            'state' => 'privacy:metadata:ardora_jobs:state',
            'attemps' => 'privacy:metadata:ardora_jobs:attemps',
            'points' => 'privacy:metadata:ardora_jobs:points',
        ], 'privacy:metadata:ardora_jobs');

        $collection->add_database_table('ardora_server_pages', [
            'userid' => 'privacy:metadata:ardora_server_pages:userid',
            'courseid' => 'privacy:metadata:ardora_server_pages:courseid',
            'ardora_id' => 'privacy:metadata:ardora_server_pages:ardora_id',
            'type' => 'privacy:metadata:ardora_server_pages:type',
            'dt' => 'privacy:metadata:ardora_server_pages:dt',
        ], 'privacy:metadata:ardora_server_pages');

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

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'ardora',
            'userid' => $userid,
        ];

        $sqljobs = "
            SELECT ctx.id
              FROM {context} ctx
              JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
              JOIN {modules} m ON m.id = cm.module AND m.name = :modname
              JOIN {ardora} a ON a.id = cm.instance
              JOIN {ardora_jobs} aj ON aj.ardora_id = a.ardora_id AND aj.courseid = a.course
             WHERE aj.userid = :userid
        ";
        $contextlist->add_from_sql($sqljobs, $params);

        $sqlpages = "
            SELECT ctx.id
              FROM {context} ctx
              JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
              JOIN {modules} m ON m.id = cm.module AND m.name = :modname
              JOIN {ardora} a ON a.id = cm.instance
              JOIN {ardora_server_pages} asp ON asp.ardora_id = a.ardora_id AND asp.courseid = a.course
             WHERE asp.userid = :userid
        ";
        $contextlist->add_from_sql($sqlpages, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'cmid' => $context->instanceid,
            'modname' => 'ardora',
        ];

        $sqljobs = "
            SELECT aj.userid
              FROM {course_modules} cm
              JOIN {modules} m ON m.id = cm.module AND m.name = :modname
              JOIN {ardora} a ON a.id = cm.instance
              JOIN {ardora_jobs} aj ON aj.ardora_id = a.ardora_id AND aj.courseid = a.course
             WHERE cm.id = :cmid
        ";
        $userlist->add_from_sql('userid', $sqljobs, $params);

        $sqlpages = "
            SELECT asp.userid
              FROM {course_modules} cm
              JOIN {modules} m ON m.id = cm.module AND m.name = :modname
              JOIN {ardora} a ON a.id = cm.instance
              JOIN {ardora_server_pages} asp ON asp.ardora_id = a.ardora_id AND asp.courseid = a.course
             WHERE cm.id = :cmid
        ";
        $userlist->add_from_sql('userid', $sqlpages, $params);
    }

    /**
     * Export all user data for the contexts provided.
     *
     * @param approved_contextlist $contextlist The list of approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('ardora', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $ardora = $DB->get_record('ardora', ['id' => $cm->instance]);
            if (!$ardora) {
                continue;
            }

            $jobs = $DB->get_records('ardora_jobs', [
                'userid' => $userid,
                'courseid' => $ardora->course,
                'ardora_id' => $ardora->ardora_id,
            ]);
            if (!empty($jobs)) {
                writer::with_context($context)->export_data(
                    ['ardora_jobs'],
                    (object)['jobs' => array_values($jobs)]
                );
            }

            $pages = $DB->get_records('ardora_server_pages', [
                'userid' => $userid,
                'courseid' => $ardora->course,
                'ardora_id' => $ardora->ardora_id,
            ]);
            if (!empty($pages)) {
                writer::with_context($context)->export_data(
                    ['ardora_server_pages'],
                    (object)['server_pages' => array_values($pages)]
                );
            }
        }
    }

    /**
     * Deletes all user data for the specified context.
     *
     * @param \context $context The context for which to delete user data.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('ardora', $context->instanceid);
        if (!$cm) {
            return;
        }

        $ardora = $DB->get_record('ardora', ['id' => $cm->instance]);
        if (!$ardora) {
            return;
        }

        $DB->delete_records('ardora_jobs', [
            'courseid' => $ardora->course,
            'ardora_id' => $ardora->ardora_id,
        ]);

        $DB->delete_records('ardora_server_pages', [
            'courseid' => $ardora->course,
            'ardora_id' => $ardora->ardora_id,
        ]);
    }

    /**
     * Delete all user data which matches the specified user and context.
     *
     * @param approved_contextlist $contextlist The list of approved contexts to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('ardora', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $ardora = $DB->get_record('ardora', ['id' => $cm->instance]);
            if (!$ardora) {
                continue;
            }

            $DB->delete_records('ardora_jobs', [
                'userid' => $userid,
                'courseid' => $ardora->course,
                'ardora_id' => $ardora->ardora_id,
            ]);

            $DB->delete_records('ardora_server_pages', [
                'userid' => $userid,
                'courseid' => $ardora->course,
                'ardora_id' => $ardora->ardora_id,
            ]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        $cm = get_coursemodule_from_id('ardora', $context->instanceid);
        if (!$cm) {
            return;
        }

        $ardora = $DB->get_record('ardora', ['id' => $cm->instance]);
        if (!$ardora) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge($userparams, [
            'courseid' => $ardora->course,
            'ardora_id' => $ardora->ardora_id,
        ]);

        $select = "courseid = :courseid AND ardora_id = :ardora_id AND userid $usersql";
        $DB->delete_records_select('ardora_jobs', $select, $params);
        $DB->delete_records_select('ardora_server_pages', $select, $params);
    }
}
