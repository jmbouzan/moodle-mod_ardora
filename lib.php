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
 * created from the "Resource module" version created by 2009 Petr Skoda  {@link http://skodak.org}
 * @package    mod_ardora
 * @copyright  2023 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns the features supported by the Ardora module.
 *
 * This function checks which features are supported by the Ardora module and returns
 * the corresponding values.
 *
 * @param string $feature The feature constant being checked.
 * @return mixed The feature value (true, null, or a specific constant).
 */
function ardora_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}
/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function ardora_reset_userdata($data) {
    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.
    return [];
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function ardora_get_view_actions() {
    return ['view', 'view all'];
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function ardora_get_post_actions() {
    return ['update', 'add'];
}

/**
 * Delete ardora instance.
 * @param int $id
 * @return bool true
 */
function ardora_delete_instance($id) {
    // IMPORTANT: to make it work, the Moodle recycle garbage can must be deactivated at.
    // Site administration - extensions - recycle garbage can.
    global $DB;
    // Check if the instance exists.
    if (!$ardora = $DB->get_record('ardora', ['id' => $id])) {
        return false;
    }

    // Get ardora_id.
    $ardoraid = $ardora->ardora_id;

    $transaction = $DB->start_delegated_transaction();

    try {

        // Deletes related records from the ardora_jobs table.
        $DB->delete_records('ardora_jobs', ['ardora_id' => $ardoraid]);

        // Deletes the record from the ardora table.
        $DB->delete_records('ardora', ['id' => $id]);

        // Confirm transaction.
        $transaction->allow_commit();

    } catch (Exception $e) {
        $transaction->rollback($e);
        return false;
    }

    return true;
}

/**
 * Add ardora instance.
 * @param object $data
 * @param object $mform
 * @return int new ardora instance id
 */
function ardora_add_instance($data, $mform) {
    global $CFG, $DB, $USER;

    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->dirroot/mod/ardora/locallib.php");

    $cmid = $data->coursemodule;
    $data->timemodified = time();
    ardora_set_display_options($data);

    $context = context_module::instance($cmid);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_ardora', 'content');
    $draftitemid = $data->files;
    $contextuser = context_user::instance($USER->id);
    $draftfiles = $fs->get_area_files($contextuser->id, 'user', 'draft', $draftitemid);
    $ardoraid = null;
    // Iterate over the files in the draft area.
    foreach ($draftfiles as $file) {
        $filename = $file->get_filename();
        // Check if the file is 'parameters.txt'.
        if ($filename == 'parameters.txt') {
            $content = $file->get_content();
            $lines = explode("\n", $content);
            if (isset($lines[1])) {
                $ardoraid = $lines[1]; // Store the second line in the ardora_id variable.
            }
            break; // Exit the loop since we found the file we were looking for.
        }
    }

    if ($ardoraid !== null) {
        $data->ardora_id = $ardoraid;
    }

    $data->id = $DB->insert_record('ardora', $data);
    ardora_grade_item_update($data);

    $DB->set_field('course_modules', 'instance', $data->id, ['id' => $cmid]);
    ardora_set_mainfile($data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'ardora', $data->id, $completiontimeexpected);

    return $data->id;
}
/**
 * Set grade item.
 * @param object $ardora
 */
function ardora_grade_item_update($ardora) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = [];
    $item['itemname'] = clean_param($ardora->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    // This value must be defined or put in a column in the table called grade and make $grade->grade.
    $item['grademax']  = 100;
    $item['grademin']  = 0;
    grade_update('mod/ardora', $ardora->course, 'mod', 'ardora', $ardora->id, 0, null, $item);
}

/**
 * Update ardora instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function ardora_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");
    $data->timemodified = time();
    $data->id           = $data->instance;
    $data->revision++;

    ardora_set_display_options($data);

    $DB->update_record('ardora', $data);
    ardora_set_mainfile($data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'ardora', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Updates display options based on form input.
 *
 * Shared code used by ardora_add_instance and ardora_update_instance.
 *
 * @param object $data Data object
 */
function ardora_set_display_options($data) {
    global $CFG;

    $displayoptions = [];
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if ($data->display == RESOURCELIB_DISPLAY_EMBED) {
        $displayoptions['embedwidth']  = $data->embedwidth;
        $displayoptions['embedheight'] = $data->embedheight;
    }
    if (in_array($data->display, [RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME])) {
        $displayoptions['printintro'] = (int)!empty($data->printintro);
    }
    if (!empty($data->showsize)) {
        $displayoptions['showsize'] = 1;
    }
    if (!empty($data->showtype)) {
        $displayoptions['showtype'] = 1;
    }
    if (!empty($data->showdate)) {
        $displayoptions['showdate'] = 1;
    }
    $data->displayoptions = serialize($displayoptions);
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info info
 */
function ardora_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/filelib.php");
    require_once("$CFG->dirroot/mod/ardora/locallib.php");
    require_once($CFG->libdir.'/completionlib.php');
    $context = context_module::instance($coursemodule->id);
    if (!$ardora = $DB->get_record('ardora', ['id' => $coursemodule->instance],
    'id, name, display, displayoptions, tobemigrated, revision, intro, introformat')) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $ardora->name;
    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('ardora', $ardora, $coursemodule->id, false);
    }
    if ($ardora->tobemigrated) {
        $info->icon = 'i/invalid';
        return $info;
    }
    // See if there is at least one file.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_ardora', 'content', 0, 'sortorder DESC, id ASC', false, 0, 0, 1);
    if (count($files) >= 1) {
        $mainfile = reset($files);
        $info->icon = file_file_icon($mainfile, 24);
        $ardora->mainfile = $mainfile->get_filename();
    }

    $display = ardora_get_final_display_type($ardora);

    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $fullurl = "$CFG->wwwroot/mod/ardora/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($ardora->displayoptions) ? [] : unserialize($ardora->displayoptions);
        $width  = empty($options['popupwidth']) ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height," .
        "toolbar=no,location=no,menubar=no," .
        "copyhistory=no,status=no,directories=no," .
        "scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $fullurl = "$CFG->wwwroot/mod/ardora/view.php?id=$coursemodule->id&amp;redirect=1";
        $info->onclick = "window.open('$fullurl'); return false;";

    } else if ($display == RESOURCELIB_DISPLAY_EMBED) {
        $fullurl = "$CFG->wwwroot/mod/ardora/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($ardora->displayoptions) ? [] : unserialize($ardora->displayoptions);
        $width = empty($options['embedwidth']) ? '100%' : $options['embedwidth'];
        $height = empty($options['embedheight']) ? 600 : $options['embedheight'];
        // Let's assume that $content is the variable that will contain the HTML to be embedded.
        $content = '<iframe src="' . $fullurl . '" width="' . $width . '" height="' . $height . '"></iframe>';
        // The content can now be added to $info in some way.
        $info->content = $content;
    }

    // If any optional extra details are turned on, store in custom data,
    // add some file details as well to be used later by ardora_get_optional_details() without retriving.
    // Do not store filedetails if this is a reference - they will still need to be retrieved every time.
    if (($filedetails = ardora_get_file_details($ardora, $coursemodule)) && empty($filedetails['isref'])) {
        $displayoptions = @unserialize($ardora->displayoptions);
        $displayoptions['filedetails'] = $filedetails;
        $info->customdata = serialize($displayoptions);
    } else {
        $info->customdata = $ardora->displayoptions;
    }

    return $info;
}

/**
 * Called when viewing course page. Shows extra details after the link if
 * enabled.
 *
 * @param cm_info $cm Course module information
 */
function ardora_cm_info_view(cm_info $cm) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/ardora/locallib.php');

    $ardora = (object)['displayoptions' => $cm->customdata];
    $details = ardora_get_optional_details($ardora, $cm);
    if ($details) {
        $cm->set_after_link(' ' . html_writer::tag('span', $details,
                ['class' => 'ardoralinkdetails']));
    }
}

/**
 * Lists all browsable file areas
 *
 * @package  mod_ardora
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function ardora_get_file_areas($course, $cm, $context) {
    $areas = [];
    $areas['content'] = get_string('ardoracontent', 'ardora');
    return $areas;
}

/**
 * File browsing support for ardora module content area.
 *
 * @package  mod_ardora
 * @category files
 * @param stdClass $browser file browser instance
 * @param stdClass $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info instance or null if not found
 */
function ardora_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // Students can not peak here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_ardora', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' && $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_ardora', 'content', 0);
            } else {
                // Not found.
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/ardora/locallib.php");
        return new ardora_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
    }

    // Note: ardora_intro handled in file_browser automatically.

    return null;
}

/**
 * Serves the ardora files.
 *
 * @package  mod_ardora
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function ardora_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=[]) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/ardora:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        // Intro is handled automatically in pluginfile.php.
        return false;
    }

    array_shift($args); // Ignore revision - designed to prevent caching problems only.

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = rtrim("/$context->id/mod_ardora/$filearea/0/$relativepath", '/');
    do {
        if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
            if ($fs->get_file_by_hash(sha1("$fullpath/."))) {
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.htm"))) {
                    break;
                }
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.html"))) {
                    break;
                }
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/Default.htm"))) {
                    break;
                }
            }
            $ardora = $DB->get_record('ardora', ['id' => $cm->instance], 'id, legacyfiles', MUST_EXIST);
            if ($ardora->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
                return false;
            }
            if (!$file = RESOURCELIB_try_file_migration('/'.$relativepath, $cm->id, $cm->course, 'mod_ardora', 'content', 0)) {
                return false;
            }
            // File migrate - update flag.
            $ardora->legacyfileslast = time();
            $DB->update_record('ardora', $ardora);
        }
    } while (false);

    // Should we apply filters?
    $mimetype = $file->get_mimetype();
    if ($mimetype === 'text/html' || $mimetype === 'text/plain' || $mimetype === 'application/xhtml+xml') {
        $filter = $DB->get_field('ardora', 'filterfiles', ['id' => $cm->instance]);
        $CFG->embeddedsoforcelinktarget = true;
    } else {
        $filter = 0;
    }

    // Finally send the file.
    send_stored_file($file, null, $filter, $forcedownload, $options);
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function ardora_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = ['mod-ardora-*' => get_string('page-mod-ardora-x', 'ardora')];
    return $modulepagetype;
}

/**
 * Export file ardora contents
 *
 * @return array of file content
 */
function ardora_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    $contents = [];
    $context = context_module::instance($cm->id);
    $ardora = $DB->get_record('ardora', ['id' => $cm->instance], '*', MUST_EXIST);

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_ardora', 'content', 0, 'sortorder DESC, id ASC', false);
    $cfgroot = "$CFG->wwwroot/" . $baseurl;
    foreach ($files as $fileinfo) {
        $file = [];
        $file['type'] = 'file';
        $file['filename']     = $fileinfo->get_filename();
        $file['filepath']     = $fileinfo->get_filepath();
        $file['filesize']     = $fileinfo->get_filesize();
        $file['fileurl']      = file_encode_url($cfgroot, '/'.$context->id.'/mod_ardora/content/'.$ardora->revision.
        $fileinfo->get_filepath().$fileinfo->get_filename(), true);
        $file['timecreated']  = $fileinfo->get_timecreated();
        $file['timemodified'] = $fileinfo->get_timemodified();
        $file['sortorder']    = $fileinfo->get_sortorder();
        $file['userid']       = $fileinfo->get_userid();
        $file['author']       = $fileinfo->get_author();
        $file['license']      = $fileinfo->get_license();
        $file['mimetype']     = $fileinfo->get_mimetype();
        $file['isexternalfile'] = $fileinfo->is_external_file();
        if ($file['isexternalfile']) {
            $file['repositorytype'] = $fileinfo->get_repository_type();
        }
        $contents[] = $file;
    }

    return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function ardora_dndupload_register() {
    return ['files' => [
        ['extension' => '*', 'message' => get_string('dnduploadardora', 'mod_ardora')],
    ]];
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function ardora_dndupload_handle($uploadinfo) {
    // Gather the required info.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '';
    $data->introformat = FORMAT_HTML;
    $data->coursemodule = $uploadinfo->coursemodule;
    $data->files = $uploadinfo->draftitemid;

    // Set the display options to the site defaults.
    $config = get_config('ardora');
    $data->display = $config->display;
    $data->popupheight = $config->popupheight;
    $data->popupwidth = $config->popupwidth;
    $data->printintro = $config->printintro;
    $data->showsize = (isset($config->showsize)) ? $config->showsize : 0;
    $data->showtype = (isset($config->showtype)) ? $config->showtype : 0;
    $data->showdate = (isset($config->showdate)) ? $config->showdate : 0;
    $data->filterfiles = $config->filterfiles;

    // Nota: $data->numberid = $formdata->numberid; so para pór number id como obrigatorio.
    return ardora_add_instance($data, null);
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $ardora   ardora object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function ardora_view($ardora, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = [
        'context' => $context,
        'objectid' => $ardora->id,
    ];

    $event = \mod_ardora\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('ardora', $ardora);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function ardora_check_updates_since(cm_info $cm, $from, $filter = []) {
    $updates = course_check_module_updates_since($cm, $from, ['content'], $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_ardora_core_calendar_provide_event_action(calendar_event $event, \core_calendar\action_factory $factory, $userid = 0) {

    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['ardora'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/ardora/view.php', ['id' => $cm->id]),
        1,
        true
    );
}


/**
 * Given an array with a file path, it returns the itemid and the filepath for the defined filearea.
 *
 * @param  string $filearea The filearea.
 * @param  array  $args The path (the part after the filearea and before the filename).
 * @return array The itemid and the filepath inside the $args path, for the defined filearea.
 */
function mod_ardora_get_path_from_pluginfile(string $filearea, array $args): array {
    // Ardora never has an itemid (the number represents the revision but it's not stored in database).
    array_shift($args);

    // Get the filepath.
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    return [
        'itemid' => 0,
        'filepath' => $filepath,
    ];
}


/**
 * Saves a job in the Ardora module.
 *
 * This function handles saving a job with the specified parameters in the Ardora module.
 *
 * @param stdClass $datajob The data associated with the job.
 * @param int $father The ID of the parent element.
 * @param int $type The type of job.
 * @param string $paqname The name of the package.
 * @param string $activity The activity related to the job.
 * @param int $hstart The start time of the job.
 * @param int $hend The end time of the job.
 * @param int $attemps The number of attempts allowed.
 * @param float $points The points associated with the job.
 * @param int $state The state of the job.
 * @param int $ardoraid The ID of the Ardora instance.
 * @param int $typegrade The type of grading for the job.
 * @return bool True if the job was saved successfully, false otherwise.
 */
function mod_ardora_save_job(
    $datajob,
    $father,
    $type,
    $paqname,
    $activity,
    $hstart,
    $hend,
    $attemps,
    $points,
    $state,
    $ardoraid,
    $typegrade
) {
    global $DB, $USER, $COURSE;
    require_once(__DIR__ . '/../../lib/gradelib.php');
    require_login();
    $userid = $USER->id;
    // Note: $courseid = $COURSE->id; On pop-ups this does not actually return the course you are on.
    $courseid = $_SESSION['courseid'];

    $datajob = clean_param($datajob, PARAM_NOTAGS);
    $father = clean_param($father, PARAM_NOTAGS);
    $type = clean_param($type, PARAM_NOTAGS);
    $paqname = clean_param($paqname, PARAM_NOTAGS);
    $activity = clean_param($activity, PARAM_NOTAGS);
    $hstart = clean_param($hstart, PARAM_NOTAGS);
    $hend = clean_param($hend, PARAM_NOTAGS);
    $attemps = clean_param($attemps, PARAM_NOTAGS);
    $points = clean_param($points, PARAM_NOTAGS);
    $state = clean_param($state, PARAM_NOTAGS);
    $ardoraid = clean_param($ardoraid, PARAM_NOTAGS);
    $ardorarecord = $DB->get_record('ardora', ['ardora_id' => $ardoraid], 'id', IGNORE_MISSING);
    if ($ardorarecord) {
        $instanceid = $ardorarecord->id;
    }

    $ccontext = context_course::instance($courseid);
    $isstudent = user_has_role_assignment($userid, 5);

    if ($isstudent) {
        $sql = "SELECT * FROM {ardora_jobs} WHERE "
        . "datajob = :datajob AND "
        . "type = :type AND "
        . "father = :father AND "
        . "paq_name = :paq_name AND "
        . "ardora_id = :ardora_id AND "
        . "activity = :activity AND "
        . "hstart = :hstart AND "
        . "state IN ('exec', 'erro') AND "
        . "userid = :userid AND "
        . "courseid = :courseid";

        $params = [
            'datajob' => $datajob,
            'type' => $type,
            'father' => $father,
            'paq_name' => $paqname,
            'ardora_id' => $ardoraid,
            'activity' => $activity,
            'hstart' => $hstart,
            'userid' => $userid,
            'courseid' => $courseid,
        ];
        $existingjob = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
        if (!$existingjob) {
            $newjob = new stdClass();
            $newjob->courseid = $courseid;
            $newjob->userid = $userid;
            $newjob->datajob = $datajob;
            $newjob->type = $type;
            $newjob->father = $father;
            $newjob->paq_name = $paqname;
            $newjob->ardora_id = $ardoraid;
            $newjob->activity = $activity;
            $newjob->hstart = $hstart;
            $newjob->hend = $hend;
            $newjob->state = $state;
            $newjob->attemps = $attemps;
            $newjob->points = $points;
            $DB->insert_record("ardora_jobs", $newjob);
        } else {
            $existingjob->hend = $hend;
            $existingjob->attemps = $attemps;
            $existingjob->points = $points;
            $existingjob->state = $state;
            $DB->update_record("ardora_jobs", $existingjob);
        }
        // Update the grade book.
        $gradeitem = $DB->get_record('grade_items', [
            'itemtype' => 'mod',
            'itemmodule' => 'ardora',
            'iteminstance' => $ardorarecord->id,
        ]);
        if ($gradeitem) {
            $itemid = $gradeitem->id;
        }

        $newgrade = 15.6; // New qualification.
        // WE OBTAIN QUALIFICATION.
        // $newgrade -> qualification.
        // $typegrade="points" -> points obtained.
        // $typegrade="acts" -> successfully completed activities.
        // $activityvalues -> array with activity numbers.

        // We obtain all the records.
        $ardorajobs = $DB->get_records('ardora_jobs', [
        'ardora_id' => $ardoraid,
        'courseid' => $courseid,
        'userid' => $userid]);
        $activityvalues = array_unique(array_column($ardorajobs, 'activity'));
        if ($typegrade == "points") {
            // Array to store the maximum values of “points” for each value of “activity”.
            $maxpoints = [];
            // Scroll through each value of “activity”.
            foreach ($activityvalues as $activity) {
                $maxpoints[$activity] = 0; // Initial value for the maximum number of points for each activity.
                // Scroll through the records in $ardora_jobs and find the maximum value of “points” for each “activity”.
                foreach ($ardorajobs as $ardorajob) {
                    if ($ardorajob->activity == $activity && intval($ardorajob->points) > $maxpoints[$activity]) {
                        $maxpoints[$activity] = intval($ardorajob->points);
                    }
                }
            }
            $newgrade = array_sum($maxpoints);
        }
        if ($typegrade == "acts") {
            $maxacts = [];
            foreach ($activityvalues as $activity) {
                $maxacts[$activity] = "--";
                foreach ($ardorajobs as $ardorajob) {
                    if ($ardorajob->activity == $activity && strcasecmp($ardorajob->state, "ok") === 0) {
                        $maxacts[$activity] = "ok";
                    }
                }
            }
            $countok = 0;
            foreach ($maxacts as $value) {
                // Check if the value is “ok” (case insensitive).
                if (strcasecmp($value, "ok") === 0) {
                    // The value is “ok”, increment the counter.
                    $countok++;
                }
            }
            $newgrade = $countok++;
        }
        // Check if a record with the specified itemid and userid exists.
        $existinggrade = $DB->get_record('grade_grades', ['itemid' => $itemid, 'userid' => $userid]);
        $currenttime = time();
        if ($existinggrade) {
            // Update the existing registry.
            $existinggrade->rawgrade = $newgrade;
            $existinggrade->finalgrade = $newgrade;
            $existinggrade->timemodified = $currenttime;
            $DB->update_record('grade_grades', $existinggrade);
        } else {
            // Create a new record.
            $newgraderecord = new stdClass();
            $newgraderecord->itemid = $itemid;
            $newgraderecord->userid = $userid;
            $newgraderecord->rawgrade = $newgrade;
            $newgraderecord->finalgrade = $newgrade;
            $newgraderecord->rawgrademax = $gradeitem->grademax;
            $newgraderecord->rawgrademin = $gradeitem->grademin;
            $newgraderecord->timecreated = $currenttime;
            $newgraderecord->timemodified = $currenttime;
            $DB->insert_record('grade_grades', $newgraderecord);
        }
    }

    // Moodle roles table.
    // +------+--------+------------------+---------------+-------------+------------------+.
    // | "id" | "name" |   "shortname"    | "description" | "sortorder" |   "archetype"    |.
    // +------+--------+------------------+---------------+-------------+------------------+.
    // | "1"  | ""     | "manager"        | ""            | "1"         | "manager"        |.
    // | "2"  | ""     | "coursecreator"  | ""            | "2"         | "coursecreator"  |.
    // | "3"  | ""     | "editingteacher" | ""            | "3"         | "editingteacher" |.
    // | "4"  | ""     | "teacher"        | ""            | "4"         | "teacher"        |.
    // | "5"  | ""     | "student"        | ""            | "5"         | "student"        |.
    // | "6"  | ""     | "guest"          | ""            | "6"         | "guest"          |.
    // | "7"  | ""     | "user"           | ""            | "7"         | "user"           |.
    // | "8"  | ""     | "frontpage"      | ""            | "8"         | "frontpage"      |.
    // +------+--------+------------------+---------------+-------------+------------------.
}
/**
 * Retrieves the user's Ardora jobs.
 *
 * This function retrieves job records related to a specific Ardora activity
 * based on the provided parameters.
 *
 * @param int $type The type of job.
 * @param int $father The ID of the parent element.
 * @param string $paqname The name of the package associated with the jobs.
 * @param int $ardoraid The ID of the Ardora instance.
 * @return array An array of job records, or an empty array if no jobs are found.
 */
function get_user_ardora_jobs($type, $father, $paqname, $ardoraid) {
    global $DB, $USER, $COURSE, $_SESSION;
    require_login();
    $userid = $USER->id;
    $courseid = $_SESSION['courseid'];

    $sql = "SELECT * FROM {ardora_jobs} WHERE userid = :userid AND courseid = :courseid AND
    type = :type AND father = :father AND paq_name = :paq_name AND ardora_id = :ardora_id";
    $params = [
        'userid' => $userid,
        'courseid' => $courseid,
        'type' => $type,
        'father' => $father,
        'paq_name' => $paqname,
        'ardora_id' => $ardoraid,
    ];
    return $DB->get_records_sql($sql, $params);
}

/**
 * Retrieves the user's Ardora evaluation data.
 *
 * This function retrieves evaluation data related to a specific Ardora activity
 * based on the provided parameters.
 *
 * @param int $type The type of evaluation.
 * @param int $father The ID of the parent element.
 * @param string $paqname The name of the package associated with the evaluation.
 * @param int $ardoraid The ID of the Ardora instance.
 * @return mixed The evaluation data, or null if not found.
 */
function get_user_ardora_eval($type, $father, $paqname, $ardoraid) {
    global $DB, $USER, $COURSE;
    require_once(__DIR__ . '/../../config.php');
    require_once(__DIR__ . '/../../user/lib.php');
    require_once(__DIR__ . '/../../lib/accesslib.php');
    require_login();
    $userid = $USER->id;
    $courseid = $_SESSION['courseid'];
    $record = $DB->get_record('ardora', ['ardora_id' => $ardoraid, 'course' => $courseid], '*', MUST_EXIST);

    $courserecord = $DB->get_record('course', ['id' => $courseid]);

    if (user_has_role_assignment($userid, 5)) { // Student, user data only.

        $user = $DB->get_record('user', ['id' => $userid]);

        $sql = "SELECT g.*
        FROM {groups} g
        JOIN {groups_members} gm ON gm.groupid = g.id
        WHERE g.courseid = :courseid AND gm.userid = :userid";
        $params = ['courseid' => $courseid, 'userid' => $userid];
        $groups = $DB->get_records_sql($sql, $params);
        $groupnames = [];
        foreach ($groups as $group) {
            $groupnames[] = $group->name;
        }
        $groupname = implode(', ', $groupnames);
        if (empty($groupname)) {
            $groupname = '--';
        }
        $users = new stdClass();
        $users->{$user->id} = (object) [
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'groups' => $groupname,
        ];

        // Get data from ardora_jobs filtering by ardora_id, courseid and userid.
        $sqlardorajobs = "SELECT aj.*, u.firstname AS username, u.lastname, c.fullname AS coursename
            FROM {ardora_jobs} aj
            INNER JOIN {user} u ON u.id = aj.userid
            INNER JOIN {course} c ON c.id = aj.courseid
            WHERE aj.ardora_id = :ardora_id
            AND aj.paq_name = :paq_name
            AND aj.courseid = :courseid
            AND aj.userid = :userid";

        $paramsardorajobs = [
            'userid' => $userid,
            'ardora_id' => $ardoraid,
            'paq_name' => $paqname,
            'courseid' => $courseid,
        ];

        $ardorajobs = $DB->get_records_sql($sqlardorajobs, $paramsardorajobs);

        $result = [
            'users' => $users,
            'ardora_jobs' => $ardorajobs,
            'coursename' => $courserecord->fullname,
            'usertype' => "student",
            'name' => $record->name,
        ];

        return $result;
    }

    if (
        user_has_role_assignment($userid, 1) ||
        user_has_role_assignment($userid, 2) ||
        user_has_role_assignment($userid, 3) ||
        user_has_role_assignment($userid, 4)
    ) {
        // Teacher, editor, administrator: send the data of all students enrolled in the course.
        $sqlardorajobs = "SELECT * FROM {ardora_jobs} WHERE courseid = :courseid
        AND father = :father
        AND paq_name = :paq_name
        AND ardora_id = :ardora_id";

        $paramsardorajobs = [
            'courseid' => $courseid,
            'father' => $father,
            'paq_name' => $paqname,
            'ardora_id' => $ardoraid,
        ];

        $ardorajobs = $DB->get_records_sql($sqlardorajobs, $paramsardorajobs);

        // Prepare a list of the userids you need to search for.
        $userids = array_unique(array_map(function($job) {
            return $job->userid;
        }, $ardorajobs));

        // Constructs a string with the unique IDs for the SQL query.
        $useridlist = implode(',', $userids);

        // Make a single query to obtain all user data.
        $sql = "SELECT u.id, u.firstname, u.lastname, g.name as groupname
                FROM {user} u
                INNER JOIN {groups_members} gm ON gm.userid = u.id
                INNER JOIN {groups} g ON g.id = gm.groupid AND g.courseid = :courseid
                WHERE u.id IN ($useridlist)";

        $params = ['courseid' => $courseid];
        $userdata = $DB->get_records_sql($sql, $params);

        // Now, each user's data is in a single object, but we need to separate the group names.
        $users = [];
        foreach ($userdata as $data) {
            // If this user has already been added to the array, it adds the new group name to its group string.
            if (isset($users[$data->id])) {
                $users[$data->id]['groups'] .= ', ' . $data->groupname;
            } else {
                // If this is the user's first group, it adds a new element to the user array.
                $users[$data->id] = [
                    'id' => $data->id,
                    'firstname' => $data->firstname,
                    'lastname' => $data->lastname,
                    // Assign '--' if the group name is NULL.
                    'groups' => $data->groupname ? $data->groupname : '--',
                ];
            }
        }
        $result = [
            'users' => $users,
            'ardora_jobs' => $ardorajobs,
            'coursename' => $courserecord->fullname,
            'usertype' => "teacher",
            'name' => $record->name,
        ];
        return $result;
    }
}

/**
 * Retrieves user information for a specific Ardora activity.
 *
 * This function fetches information related to the user's involvement in a specific
 * Ardora activity based on the provided parameters.
 *
 * @param int $type The type of information to retrieve.
 * @param int $ardoraid The ID of the Ardora instance.
 * @return mixed The user information, or null if no data is found.
 */
function get_user_ardora_info($type, $ardoraid) {
    global $DB, $USER, $COURSE;
    require_once(__DIR__ . '/../../config.php');
    require_once(__DIR__ . '/../../user/lib.php');
    require_once(__DIR__ . '/../../lib/accesslib.php');

    $userid = $USER->id;
    $courseid = $_SESSION['courseid'];

    if (user_has_role_assignment($userid, 5)) { // Student, user data only.

        $user = $DB->get_record('user', ['id' => $userid]);

        $sql = "SELECT g.*
        FROM {groups} g
        JOIN {groups_members} gm ON gm.groupid = g.id
        WHERE g.courseid = :courseid AND gm.userid = :userid";
        $params = ['courseid' => $courseid, 'userid' => $userid];
        $groups = $DB->get_records_sql($sql, $params);
        $groupnames = [];
        foreach ($groups as $group) {
            $groupnames[] = $group->name;
        }
        $groupname = implode(', ', $groupnames);
        if (empty($groupname)) {
            $groupname = '--';
        }
        $users = new stdClass();
        $users->{$user->id} = (object) [
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'groups' => $groupname,
        ];

        // Get data from ardora_jobs filtering by ardora_id, courseid and userid.
        $sqlardorajobs = "SELECT aj.*, u.firstname AS username, u.lastname, c.fullname AS coursename
            FROM {ardora_jobs} aj
            INNER JOIN {user} u ON u.id = aj.userid
            INNER JOIN {course} c ON c.id = aj.courseid
            WHERE aj.ardora_id = :ardora_id
            AND aj.courseid = :courseid
            AND aj.userid = :userid";
        $paramsardorajobs = [
            'userid' => $userid,
            'ardora_id' => $ardoraid,
            'courseid' => $courseid,
        ];
        $ardorajobs = $DB->get_records_sql($sqlardorajobs, $paramsardorajobs);
        $result = [
            'users' => $users,
            'ardora_jobs' => $ardorajobs,
            'coursename' => $COURSE->fullname,
            'usertype' => "student",
        ];

        return $result;
    }

    if (user_has_role_assignment($userid, 1) || user_has_role_assignment($userid, 2) ||
    user_has_role_assignment($userid, 3) || user_has_role_assignment($userid, 4)) {
        // Teacher, editor, administrator: send the data of all students enrolled in the course.
        $sqlardorajobs = "SELECT * FROM {ardora_jobs} WHERE courseid = :courseid
        AND ardora_id = :ardora_id";

        $paramsardorajobs = [
            'courseid' => $courseid,
            'ardora_id' => $ardoraid,
        ];

        $ardorajobs = $DB->get_records_sql($sqlardorajobs, $paramsardorajobs);

        // Prepare a list of the userids you need to search for.
        $userids = array_unique(array_map(function($job) {
            return $job->userid;
        }, $ardorajobs));
        // Constructs a string with the unique IDs for the SQL query.
        $useridlist = implode(',', $userids);

        // Make a single query to obtain all user data.
        $sql = "SELECT u.id, u.firstname, u.lastname, g.name as groupname
                FROM {user} u
                INNER JOIN {groups_members} gm ON gm.userid = u.id
                INNER JOIN {groups} g ON g.id = gm.groupid AND g.courseid = :courseid
                WHERE u.id IN ($useridlist)";

        $params = ['courseid' => $courseid];
        $userdata = $DB->get_records_sql($sql, $params);

        // Now, each user's data is in a single object, but we need to separate the group names.
        $users = [];
        foreach ($userdata as $data) {
            // If this user has already been added to the array, it adds the new group name to its group string.
            if (isset($users[$data->id])) {
                $users[$data->id]['groups'] .= ', ' . $data->groupname;
            } else {
                // If this is the user's first group, it adds a new element to the user array.
                $users[$data->id] = [
                    'id' => $data->id,
                    'firstname' => $data->firstname,
                    'lastname' => $data->lastname,
                    // Assign '--' if the group name is NULL.
                    'groups' => $data->groupname ? $data->groupname : '--',
                ];
            }
        }
        $result = [
            'users' => $users,
            'ardora_jobs' => $ardorajobs,
            'coursename' => $COURSE->fullname,
            'usertype' => "teacher",
        ];

        return $result;
    }
}
/**
 * Deletes a user's job from the Ardora activity.
 *
 * This function deletes a job record associated with a specific user and Ardora
 * activity instance.
 *
 * @param int $userid The ID of the user.
 * @param stdClass $datajob The data associated with the job.
 * @param int $ardoraid The ID of the Ardora instance.
 * @return bool True if the job was successfully deleted, false otherwise.
 */
function del_user_ardora_job($userid, $datajob, $ardoraid) {
    global $DB, $USER, $COURSE;
    $actualuserid = $USER->id;
    if (user_has_role_assignment($actualuserid, 1) || user_has_role_assignment($actualuserid, 2) ||
    user_has_role_assignment($actualuserid, 3) || user_has_role_assignment($actualuserid, 4)) {
        $select = "userid = :userid AND " .
        $DB->sql_compare_text('datajob') . " = :datajob AND " .
        $DB->sql_compare_text('ardora_id') . " = :ardora_id";
        $params = ['userid' => $userid, 'datajob' => $datajob, 'ardora_id' => $ardoraid];
        $DB->delete_records_select('ardora_jobs', $select, $params);
    }
}
