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
 * ardora external functions and service definitions.
 * created from the "Resource module" version created by 2015 Juan Leyva <juan@moodle.com>
 * @package    mod_ardora
 * @category   external
 * @copyright  2023 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

$functions = [

    'mod_ardora_view_ardora' => [
        'classname'     => 'mod_ardora_external',
        'methodname'    => 'view_ardora',
        'description'   => 'Simulate the view.php web interface ardora: trigger events, completion, etc...',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/ardora:view',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'mod_ardora_get_ardoras_by_courses' => [
        'classname'     => 'mod_ardora_external',
        'methodname'    => 'get_ardoras_by_courses',
        'description'   => 'Returns a list of files in a provided list of courses, if no list is provided all files that
                            the user can view will be returned.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/ardora:view',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'mod_ardora_save_job' => [
        'classname'   => 'mod_ardora_save_job',
        'methodname'  => 'save_job',
        'description' => 'Save student job',
        'ajax'        => true,
        'type'        => 'write',
        'capabilities' => '',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = [
    'MyArdoraService' => [
        'functions'        => ['mod_ardora_save_job'],
        'restrictedusers'  => 0,
        'enabled'          => 1,
    ],
];
