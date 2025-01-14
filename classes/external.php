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
 * ardora external API
 * created from the "Resource module" version created by
 * @package    mod_ardora
 * @category   external
 * @copyright  2025 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

/**
 * External API class for Ardora module.
 *
 * This class defines the external functions and services for the Ardora module
 * that can be accessed via the Moodle web services API.
 *
 * @package    mod_ardora
 * @category   external
 */
class mod_ardora_external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function mod_ardora_save_job_parameters() {
        return new external_function_parameters(
            [
                'welcomemessage' => new external_value(
                    PARAM_TEXT,
                    'The welcome message. By default it is "Hello world,"',
                    VALUE_DEFAULT,
                    'Hello world, '
                ),
            ]
        );
    }

    /**
     * Saves a job and returns a welcome message.
     *
     * This web service function validates parameters, context, and capabilities before
     * returning a personalized welcome message.
     *
     * @param string $welcomemessage The base message to include in the welcome message.
     *                                Defaults to 'Hello world, '.
     * @return string The personalized welcome message including the user's first name.
     * @throws moodle_exception If the user does not have the required capability.
     */
    public static function mod_ardora_save_job($welcomemessage = 'Hello world, ') {
        global $USER;
        // Parameter validation.
        // REQUIRED.
        $params = self::validate_parameters(self::mod_ardora_save_job_parameters(), [
            'welcomemessage' => $welcomemessage,
        ]);
        // Context validation.
        // OPTIONAL but in most web services it should be present.
        $context = context_user::instance($USER->id);
        self::validate_context($context);
        // Capability checking.
        // OPTIONAL but in most web services it should be present.
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }
        return $params['welcomemessage'] . $USER->firstname;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function mod_ardora_save_job_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }

    /*========================================================*/

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function view_ardora_parameters() {
        return new external_function_parameters(
            [
                'ardoraid' => new external_value(PARAM_INT, 'ardora instance id'),
            ]
        );
    }

    /**
     * Simulate the ardora/view.php web interface page: trigger events, completion, etc...
     *
     * @param int $ardoraid the ardora instance id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function view_ardora($ardoraid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/ardora/lib.php");

        $params = self::validate_parameters(self::view_ardora_parameters(),
                                    [
                                        'ardoraid' => $ardoraid,
                                    ]);
        $warnings = [];

        // Request and permission validation.
        $ardora = $DB->get_record('ardora', ['id' => $params['ardoraid']], '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($ardora, 'ardora');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/ardora:view', $context);

        // Call the ardora/lib API.
        ardora_view($ardora, $course, $cm, $context);

        $result = [];
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function view_ardora_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Describes the parameters for get_ardoras_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_ardoras_by_courses_parameters() {
        return new external_function_parameters (
            [
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'), 'Array of course ids', VALUE_DEFAULT, []
                ),
            ]
        );
    }

    /**
     * Returns a list of files in a provided list of courses.
     * If no list is provided all files that the user can view will be returned.
     *
     * @param array $courseids course ids
     * @return array of warnings and files
     * @since Moodle 3.3
     */
    public static function get_ardoras_by_courses($courseids = []) {

        $warnings = [];
        $returnedardoras = [];

        $params = [
            'courseids' => $courseids,
        ];
        $params = self::validate_parameters(self::get_ardoras_by_courses_parameters(), $params);

        $mycourses = [];
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);

            // Get the ardoras in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $ardoras = get_all_instances_in_courses("ardora", $courses);
            foreach ($ardoras as $ardora) {
                $context = context_module::instance($ardora->coursemodule);
                // Entry to return.
                $ardora->name = external_format_string($ardora->name, $context->id);
                $options = ['noclean' => true];
                list($ardora->intro, $ardora->introformat) =
                    external_format_text($ardora->intro, $ardora->introformat, $context->id, 'mod_ardora', 'intro', null,
                        $options);
                $ardora->introfiles = external_util::get_area_files($context->id, 'mod_ardora', 'intro', false, false);
                $ardora->contentfiles = external_util::get_area_files($context->id, 'mod_ardora', 'content');

                $returnedardoras[] = $ardora;
            }
        }

        $result = [
            'ardoras' => $returnedardoras,
            'warnings' => $warnings,
        ];
        return $result;
    }

    /**
     * Describes the get_ardoras_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_ardoras_by_courses_returns() {
        return new external_single_structure(
            [
                'ardoras' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'Module id'),
                            'coursemodule' => new external_value(PARAM_INT, 'Course module id'),
                            'course' => new external_value(PARAM_INT, 'Course id'),
                            'name' => new external_value(PARAM_RAW, 'Page name'),
                            'intro' => new external_value(PARAM_RAW, 'Summary'),
                            'introformat' => new external_format_value('intro', 'Summary format'),
                            'introfiles' => new external_files('Files in the introduction text'),
                            'contentfiles' => new external_files('Files in the content'),
                            'tobemigrated' => new external_value(PARAM_INT, 'Whether this ardora was migrated'),
                            'legacyfiles' => new external_value(PARAM_INT, 'Legacy files flag'),
                            'legacyfileslast' => new external_value(PARAM_INT, 'Legacy files last control flag'),
                            'display' => new external_value(PARAM_INT, 'How to display the ardora'),
                            'displayoptions' => new external_value(PARAM_RAW, 'Display options (width, height)'),
                            'filterfiles' => new external_value(PARAM_INT, 'If filters should be applied to the ardora content'),
                            'revision' => new external_value(PARAM_INT, 'Incremented when after each file changes, to avoid cache'),
                            'timemodified' => new external_value(PARAM_INT, 'Last time the ardora was modified'),
                            'section' => new external_value(PARAM_INT, 'Course section id'),
                            'visible' => new external_value(PARAM_INT, 'Module visibility'),
                            'groupmode' => new external_value(PARAM_INT, 'Group mode'),
                            'groupingid' => new external_value(PARAM_INT, 'Grouping id'),
                        ]
                    )
                ),
                'warnings' => new external_warnings(),
            ]
        );
    }
}
