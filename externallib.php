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
 * External Web Service Template
 *
 * @package    mod_ardora
 * @copyright  2024 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . "/externallib.php");
require_login();

/**
 * External API class for Ardora local plugin.
 *
 * This class defines the external functions and services for the Ardora local plugin
 * that can be accessed via the Moodle web services API.
 *
 * @package    mod_ardora
 * @category   external
 * @copyright  2024 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ardora_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function hello_world_parameters() {
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
     * Returns welcome message
     * @return string welcome message
     */
    public static function hello_world($welcomemessage = 'Hello world, ') {
        global $USER;

        // Parameter validation.
        // REQUIRED.
        $params = self::validate_parameters(self::hello_world_parameters(),
        ['welcomemessage' => $welcomemessage];

        // Context validation.
        // OPTIONAL but in most web service it should present.
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        // Capability checking.
        // OPTIONAL but in most web service it should present.
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        return $params['welcomemessage'] . $USER->firstname;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function hello_world_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }
}
