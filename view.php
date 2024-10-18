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
 * ardora module version information
 * created from the "Resource module" version created by 2009 Petr Skoda  {@link http://skodak.org}
 * @package    mod_ardora
 * @copyright  2024 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/ardora/lib.php');
require_once($CFG->dirroot.'/mod/ardora/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT); // Course Module ID.
$r        = optional_param('r', 0, PARAM_INT);  // Ardora instance ID.
$redirect = optional_param('redirect', 0, PARAM_BOOL);
$forceview = optional_param('forceview', 0, PARAM_BOOL);

if ($r) {
    if (!$ardora = $DB->get_record('ardora', ['id' => $r])) {
        ardora_redirect_if_migrated($r, 0);
        throw new moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('ardora', $ardora->id, $ardora->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('ardora', $id)) {
        ardora_redirect_if_migrated(0, $id);
        throw new moodle_exception('invalidcoursemodule');
    }
    $ardora = $DB->get_record('ardora', ['id' => $cm->instance], '*', MUST_EXIST);
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);




$_SESSION['courseid'] = $course->id;






require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/ardora:view', $context);

// Completion and trigger events.
ardora_view($ardora, $course, $cm, $context);

$PAGE->set_url('/mod/ardora/view.php', ['id' => $cm->id]);

if ($ardora->tobemigrated) {
    ardora_print_tobemigrated($ardora, $cm, $course);
    die;
}

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_ardora', 'content', 0, 'sortorder DESC, id ASC', false);
if (count($files) < 1) {
    ardora_print_filenotfound($ardora, $cm, $course);
    die;
} else {
    $file = reset($files);
    unset($files);
}
$ardora->mainfile = $file->get_filename();
$displaytype = ardora_get_final_display_type($ardora);
if ($displaytype == RESOURCELIB_DISPLAY_OPEN || $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD) {
    $redirect = true;
}

// Don't redirect teachers, otherwise they can not access course or module settings.
if ($redirect && !course_get_format($course)->has_view_page() &&
        (has_capability('moodle/course:manageactivities', $context) ||
        has_capability('moodle/course:update', context_course::instance($course->id)))) {
    $redirect = false;
}

if ($redirect && !$forceview) {
    // Coming from course page or url index page.
    // This redirect trick solves caching problems when tracking views ;-).
    $path = '/'.$context->id.'/mod_ardora/content/'.$ardora->revision.$file->get_filepath().$file->get_filename();
    $fullurl = moodle_url::make_file_url('/pluginfile.php', $path, $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD);
    redirect($fullurl);
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        ardora_display_embed($ardora, $cm, $course, $file);
        break;
    case RESOURCELIB_DISPLAY_FRAME:
        ardora_display_frame($ardora, $cm, $course, $file);
        break;
    default:
        ardora_print_workaround($ardora, $cm, $course, $file);
        break;
}
