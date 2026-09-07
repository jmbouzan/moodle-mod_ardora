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
 * WEBService: Legacy externallib.php entry point for mod_ardora.
 * Resolves legacy external function calls and provides backwards compatibility.
 *
 * @package    mod_ardora
 * @category   external
 * @copyright  2026 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// WEBService: Include modern external class definition.
require_once(__DIR__ . '/classes/external.php');

// WEBService: Ensure legacy and namespaced class aliases are registered for external services.
if (!class_exists('mod_ardora_save_job')) {
    class_alias('mod_ardora_external', 'mod_ardora_save_job');
}

if (!class_exists('mod_ardora\external')) {
    class_alias('mod_ardora_external', 'mod_ardora\external');
}
