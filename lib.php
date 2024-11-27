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
 * @copyright  2024 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns the features supported by the Ardora module.
 *
 * This function checks if specific Moodle module features are supported by the Ardora module,
 * returning the appropriate value or constant based on the feature being queried.
 *
 * @param string $feature One of Moodle's module feature constants, e.g., FEATURE_GROUPS.
 * @return bool|string|null True if the feature is supported, a specific constant if applicable, or null if unsupported.
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
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}
/**
 * Resets user-specific data for the Ardora module during a course reset.
 *
 * This function is called by the reset_course_userdata function in moodlelib
 * to remove or reset user data specific to this module as part of a course reset.
 *
 * @param object $data The data submitted from the reset course form.
 * @return array An array of status messages indicating the results of the reset.
 */
function ardora_reset_userdata($data) {
    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    return [];
}

/**
 * Lists the actions that count as a view for this module.
 *
 * This is used by the participation report to track user engagement.
 *
 * Note: This function is not used by Moodle's new logging system.
 * Events with CRUD action set to 'r' (read) and education level set to LEVEL_PARTICIPATING
 * will be automatically considered as view actions.
 *
 * @return array An array of action names representing views in this module, such as 'view' and 'view all'.
 */
function ardora_get_view_actions() {
    return ['view', 'view all'];
}

/**
 * Lists the actions that are considered "post" actions for this module.
 *
 * This function is used by the participation report to track user engagement that qualifies as a post.
 *
 * Note: This function is not used by Moodle's new logging system.
 * Events with CRUD action set to 'c' (create), 'u' (update), or 'd' (delete)
 * and education level set to LEVEL_PARTICIPATING will be automatically considered as post actions.
 *
 * @return array An array of actions representing post actions in this module, such as 'update' and 'add'.
 */
function ardora_get_post_actions() {
    return ['update', 'add'];
}

/**
 * Deletes an Ardora module instance and all related data.
 *
 * This function deletes a specified Ardora module instance along with all records
 * associated with it from the database, such as related entries in the ardora_jobs table.
 *
 * Note: To ensure this deletion process works as intended, the Moodle Recycle Bin
 * feature should be disabled at Site Administration > Plugins > Recycle Bin.
 *
 * @param int $id The ID of the Ardora instance to delete.
 * @return bool True if the deletion was successful, false if the instance does not exist or an error occurs.
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
 * Adds a new Ardora instance to the database.
 *
 * This function processes the provided data, sets display options, handles draft files
 * for the Ardora instance, and updates relevant fields in the database. It also configures
 * grading items and schedules a completion date event, if applicable.
 *
 * @param stdClass $data The form data submitted to create a new Ardora instance.
 *                       It should include at least coursemodule ID and potentially draft file areas.
 * @param MoodleQuickForm $mform The form used for the instance, which may contain additional data.
 * @return int The ID of the newly created Ardora instance.
 */
function ardora_add_instance($data, $mform) {
    global $CFG, $DB, $USER;
    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->libdir/gradelib.php");
    require_once("$CFG->dirroot/mod/ardora/locallib.php");

    $cmid = $data->coursemodule;
    $data->timemodified = time();
    ardora_set_display_options($data);

    // Handling of draft files.
    $context = context_module::instance($cmid);
    $fs = get_file_storage();
    $draftitemid = $data->files;
    $contextuser = context_user::instance($USER->id);
    $draftfiles = $fs->get_area_files($contextuser->id, 'user', 'draft', $draftitemid);
    $ardoraid = null;

    // Process the files in the draft area.
    foreach ($draftfiles as $file) {
        if ($file->get_filename() == 'parameters.txt') {
            $content = $file->get_content();
            $lines = explode("\n", $content);
            if (isset($lines[1])) {
                $ardoraid = $lines[1];
            }
            break;
        }
    }

    if ($ardoraid !== null) {
        $data->ardora_id = $ardoraid;
    }

    // Validate and assign values related to the qualification.
    $data->gradepass = isset($data->gradepass) && is_numeric($data->gradepass) ? floatval($data->gradepass) : 0;
    if ($data->gradepass < 0) {
        throw new moodle_exception('invalidgradepass', 'mod_ardora');
    }
    $data->id = $DB->insert_record('ardora', $data);
    $gradeitem = [
        'itemname' => clean_param($data->name, PARAM_NOTAGS),
        'grademin' => 0,
        'gradepass' => $data->gradepass,
    ];
    grade_update('mod/ardora', $data->course, 'mod', 'ardora', $data->id, 0, null, $gradeitem);

    $DB->set_field('course_modules', 'instance', $data->id, ['id' => $cmid]);

    ardora_set_mainfile($data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'ardora', $data->id, $completiontimeexpected);

    return $data->id;
}

/**
 * Updates the grade item for the Ardora activity.
 *
 * This function ensures that the grade item for the Ardora activity is created or updated
 * in the Moodle Gradebook with the correct settings and values.
 *
 * @param stdClass $ardora The Ardora instance containing grade and passing grade information.
 * @return void
 */
function ardora_grade_item_update($ardora) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    // Prepare the grade item array.
    $item = [
        'itemname' => clean_param($ardora->name, PARAM_NOTAGS),
        'gradepass' => $ardora->gradepass ?? 0, // Set grade pass value or default to 0.
    ];

    // Call grade_update to create or update the grade item.
    grade_update(
        'mod/ardora',        // Component.
        $ardora->course,     // Course ID.
        'mod',               // Item type (module).
        'ardora',            // Module name.
        $ardora->id,         // Instance ID of the module.
        0,                   // No specific itemnumber (0 for primary grade item).
        null,                // No grades to update at this time.
        $item                // Grade item configuration.
    );
}

/**
 * Updates an existing Ardora instance with new data.
 *
 * This function saves updated data for the specified Ardora instance, modifies display options,
 * updates the main file, and schedules a completion date event if needed.
 *
 * @param stdClass $data An object containing the updated Ardora instance data, including the course module ID.
 * @param MoodleQuickForm $mform The form object containing any additional submission data.
 * @return bool True on success.
 */
function ardora_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->libdir/gradelib.php");

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->revision++;

    ardora_set_display_options($data);

    // Validate and assign values related to the qualification.
    $data->gradepass = isset($data->gradepass) && is_numeric($data->gradepass) ? floatval($data->gradepass) : 0;

    // Validate the gradepass rank.
    if ($data->gradepass < 0) {
        throw new moodle_exception('invalidgradepass', 'mod_ardora');
    }

    $DB->update_record('ardora', $data);

    // Synchronise with the gradebook.
    $gradeitem = [
        'itemname' => clean_param($data->name, PARAM_NOTAGS),
        'grademin' => 0,
        'gradepass' => $data->gradepass,
    ];
    grade_update('mod/ardora', $data->course, 'mod', 'ardora', $data->id, 0, null, $gradeitem);

    ardora_set_mainfile($data);

    // Update the expected end date of events..
    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'ardora', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Sets display options for the Ardora instance based on form data.
 *
 * This function configures display options such as popup size, embed size,
 * and whether to show the intro, size, type, or date, based on the user's selections
 * in the form. The resulting options are saved as a JSON-encoded string in `$data->displayoptions`.
 *
 * @param stdClass $data Data object containing display settings (e.g., display type,
 *                       popup dimensions, embed dimensions, and flags for showing
 *                       intro, size, type, or date).
 * @return void
 */
function ardora_set_display_options($data) {
    global $CFG;

    $displayoptions = [];
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = isset($data->popupwidth) ? $data->popupwidth : 620;
        $displayoptions['popupheight'] = isset($data->popupheight) ? $data->popupheight : 450;
    }
    if ($data->display == RESOURCELIB_DISPLAY_EMBED) {
        $displayoptions['embedwidth']  = isset($data->embedwidth) ? $data->embedwidth : '100%';
        $displayoptions['embedheight'] = isset($data->embedheight) ? $data->embedheight : 600;
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
    $data->displayoptions = json_encode($displayoptions, true);
}

/**
 * Retrieves extra information for displaying the Ardora module in a course listing.
 *
 * This function provides additional information for the module when it is being printed
 * in a course listing, such as custom display options (popup, embed), file details,
 * and more. It returns a `cached_cm_info` object with the necessary data, or null if
 * the module record is not found.
 *
 * @param stdClass $coursemodule Course module object
 * @return cached_cm_info|null Information for course module display, or null if not found
 */
function ardora_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    require_once("$CFG->libdir/filelib.php");
    require_once("$CFG->dirroot/mod/ardora/locallib.php");
    require_once($CFG->libdir . '/completionlib.php');

    $context = context_module::instance($coursemodule->id);

    // Fetch the Ardora instance from the database.
    $ardora = $DB->get_record('ardora', ['id' => $coursemodule->instance],
        'id, name, display, displayoptions, tobemigrated, revision, intro, introformat');
    if (!$ardora) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $ardora->name;

    if ($coursemodule->showdescription) {
        // Convert intro to HTML.
        $info->content = format_module_intro('ardora', $ardora, $coursemodule->id, false);
    }

    if ($ardora->tobemigrated) {
        $info->icon = 'i/invalid';
        return $info;
    }

    // Check if there is at least one file associated with the activity.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_ardora', 'content', 0, 'sortorder DESC, id ASC', false, 0, 0, 1);
    if (count($files) >= 1) {
        $mainfile = reset($files);
        $info->icon = file_file_icon($mainfile);
        $ardora->mainfile = $mainfile->get_filename();
    }

    // Determine display type and configure options.
    $display = ardora_get_final_display_type($ardora);

    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $fullurl = "$CFG->wwwroot/mod/ardora/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($ardora->displayoptions) ? [] : json_decode($ardora->displayoptions, true);
        $width = empty($options['popupwidth']) ? 620 : $options['popupwidth'];
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
        $options = empty($ardora->displayoptions) ? [] : json_decode($ardora->displayoptions, true);
        $width = empty($options['embedwidth']) ? '100%' : $options['embedwidth'];
        $height = empty($options['embedheight']) ? 600 : $options['embedheight'];
        $content = '<iframe src="' . $fullurl . '" width="' . $width . '" height="' . $height . '"></iframe>';
        $info->content = $content;
    }

    // Add completion grade requirement if defined.
    $gradeitem = $DB->get_record('grade_items', ['iteminstance' => $coursemodule->instance, 'itemmodule' => 'ardora'], 'gradepass');
    if (!empty($gradeitem->gradepass)) {
        $info->completiongrade = true; // Enable passing grade requirement in completion settings.
    }

    // Store additional file details if present.
    if (($filedetails = ardora_get_file_details($ardora, $coursemodule)) && empty($filedetails['isref'])) {
        $displayoptions = !empty($ardora->displayoptions) ? json_decode($ardora->displayoptions, true) : [];
        if (!is_array($displayoptions)) {
            $displayoptions = [];
        }
        $displayoptions['filedetails'] = $filedetails;
        $info->customdata = json_encode($displayoptions);
    } else {
        $info->customdata = $ardora->displayoptions;
    }

    return $info;
}

/**
 * Appends extra details after the course module link if available.
 *
 * This function adds optional information after the module's link in the
 * course view, by setting additional HTML content if specific details
 * are configured in `customdata`.
 *
 * @param cm_info $cm Course module information.
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
 * Lists all browsable file areas for the Ardora module.
 *
 * This function defines the file areas available in the Ardora module, allowing
 * for file management within these areas in the Moodle file picker.
 *
 * @package  mod_ardora.
 * @param stdClass $course Course object.
 * @param stdClass $cm Course module object.
 * @param stdClass $context Context object.
 * @return array List of browsable file areas.
 */
function ardora_get_file_areas($course, $cm, $context) {
    $areas = [];
    $areas['content'] = get_string('ardoracontent', 'ardora');
    return $areas;
}

/**
 * Retrieves file browsing information for the content area of the Ardora module.
 *
 * This function is used to browse files within the 'content' area of the Ardora module,
 * checking for permissions and returning file information or a virtual root if no file is specified.
 *
 * @package   mod_ardora
 * @category  files
 * @param file_browser $browser Instance of file browser.
 * @param array $areas Array of file areas in Ardora.
 * @param stdClass $course Course object containing the module.
 * @param cm_info $cm Course module object.
 * @param context $context Context instance for the module.
 * @param string $filearea The file area name.
 * @param int $itemid The item ID in the file area.
 * @param string $filepath The file path within the area.
 * @param string $filename The name of the file.
 * @return file_info|virtual_root_file|null Returns file info object, virtual root file, or null if not found.
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
 * This function serves files in the 'content' area of the Ardora module,
 * with optional filtering and forced download if specified.
 *
 * @package  mod_ardora
 * @category files
 * @param stdClass $course Course object
 * @param stdClass $cm Course module object
 * @param stdClass $context Context object
 * @param string $filearea File area
 * @param array $args Extra arguments
 * @param bool $forcedownload Whether or not to force download
 * @param array $options Additional options affecting file serving
 * @return bool false if file not found; sends the file if found
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
            if (!$file = resourcelib_try_file_migration('/'.$relativepath, $cm->id, $cm->course, 'mod_ardora', 'content', 0)) {
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
 * Returns a list of page types where the Ardora module can be used.
 *
 * This function provides a list of page types for blocks or other components
 * that can interact with the Ardora module.
 *
 * @param string $pagetype The current page type.
 * @param stdClass $parentcontext The block's parent context.
 * @param stdClass $currentcontext The current context of the block.
 * @return array An associative array of page types and their descriptions.
 */
function ardora_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = ['mod-ardora-*' => get_string('page-mod-ardora-x', 'ardora')];
    return $modulepagetype;
}

/**
 * Exports the contents of the Ardora module.
 *
 * This function retrieves all files stored in the 'content' area of the module,
 * including metadata such as filenames, file sizes, URLs, and more.
 *
 * @param cm_info $cm The course module instance for the Ardora module.
 * @param string $baseurl The base URL to prepend to file links.
 * @return array An array of file information, where each element contains metadata
 *               about a single file (e.g., filename, filepath, filesize, URL, etc.).
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
        $file['fileurl'] = moodle_url::make_pluginfile_url(
            $context->id,
            'mod_ardora',
            'content',
            $ardora->revision,
            $fileinfo->get_filepath(),
            $fileinfo->get_filename()
        )->out(false);
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
 * Registers the ability to handle drag-and-drop file uploads for the Ardora module.
 *
 * This function specifies the file types and extensions that the Ardora module can handle
 * when files are dragged and dropped into a course.
 *
 * @return array An array containing file types and associated messages for drag-and-drop uploads.
 */
function ardora_dndupload_register() {
    return ['files' => [
        ['extension' => '*', 'message' => get_string('dnduploadardora', 'mod_ardora')],
    ]];
}

/**
 * Handles a file uploaded via drag-and-drop for the Ardora module.
 *
 * This function processes the uploaded file and creates a new Ardora instance
 * in the specified course. Default display options and settings are applied
 * from the module's configuration.
 *
 * @param stdClass $uploadinfo An object containing details of the uploaded file, including:
 *                             - `course`: The course object where the file was uploaded.
 *                             - `displayname`: The name to display for the uploaded file.
 *                             - `coursemodule`: The course module ID.
 *                             - `draftitemid`: The ID of the draft file item.
 * @return int The instance ID of the newly created Ardora module.
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

    return ardora_add_instance($data, null);
}

/**
 * Marks the Ardora activity as viewed and triggers the corresponding event.
 *
 * This function logs the event that a course module has been viewed,
 * takes snapshots of relevant records for logging purposes, and marks the
 * module as completed for the user, if applicable.
 *
 * @param stdClass $ardora The Ardora instance object, including its ID.
 * @param stdClass $course The course object containing the module.
 * @param stdClass $cm The course module object.
 * @param context $context The context object for the module.
 * @return void
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
 * Checks for updates in the Ardora module affecting the current user since a given time.
 *
 * This function determines if the content of the Ardora module or other areas
 * has been updated since the specified timestamp, optionally filtered by specific areas.
 *
 * @param cm_info $cm The course module data for the Ardora instance.
 * @param int $from The Unix timestamp to check updates from.
 * @param array $filter Optional. An array of areas to limit the update check.
 *                       Default is an empty array, which checks all areas.
 * @return stdClass An object indicating whether updates occurred in the module,
 *                  grouped by areas (e.g., 'content').
 * @since Moodle 3.2
 */
function ardora_check_updates_since(cm_info $cm, $from, $filter = []) {
    $updates = course_check_module_updates_since($cm, $from, ['content'], $filter);
    return $updates;
}

/**
 * Provides the action for a calendar event in the Ardora module.
 *
 * This function determines the appropriate action to associate with a calendar event
 * for the Ardora module. If the event is marked as complete for the user, no action is
 * returned (i.e., the event will not be displayed in the block).
 *
 * @param calendar_event $event The calendar event object.
 * @param \core_calendar\action_factory $factory The action factory to create the event action.
 * @param int $userid Optional. The ID of the user. Defaults to the current user if not provided.
 * @return \core_calendar\local\event\entities\action_interface|null The event action, or null if none is applicable.
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
 * Extracts the itemid and filepath from the pluginfile path for the Ardora module.
 *
 * This function processes the file path passed in the `pluginfile.php` URL to extract
 * the itemid and the filepath for files in the specified file area. For the Ardora module,
 * the itemid is always 0, as it represents the revision but is not stored in the database.
 *
 * @param string $filearea The file area being accessed (e.g., 'content').
 * @param array $args The path components after the filearea and before the filename.
 * @return array An associative array containing the itemid (always 0) and the filepath.
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
 * Saves or updates a job in the Ardora module.
 *
 * This function saves a new job or updates an existing one in the Ardora module,
 * handles grades based on the specified grading type, and updates the gradebook.
 *
 * @param string $datajob The data associated with the job.
 * @param int $father The ID of the parent element.
 * @param int $type The type of job.
 * @param string $paqname The name of the package.
 * @param string $activity The activity related to the job.
 * @param int $hstart The start time of the job.
 * @param int $hend The end time of the job.
 * @param int $attemps The number of attempts allowed.
 * @param float $points The points associated with the job.
 * @param int $state The state of the job (e.g., 'ok', 'exec', 'erro').
 * @param int $ardoraid The ID of the Ardora instance.
 * @param string $typegrade The type of grading ('points' or 'acts').
 * @return bool True if the job was saved successfully, false otherwise.
 * @throws moodle_exception If the course ID cannot be determined.
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
    $cache = \cache::make('mod_ardora', 'courseid_cache');
    $courseid = $cache->get('current_courseid');
    if (!$courseid) {
        throw new moodle_exception('courseidnotfound', 'mod_ardora');
    }

    // Clean and validate input parameters.
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

    // Retrieve the Ardora instance.
    $ardorarecord = $DB->get_record('ardora', ['ardora_id' => $ardoraid], 'id', IGNORE_MISSING);
    if ($ardorarecord) {
        $instanceid = $ardorarecord->id;
    }

    // Retrieve the course module ID (cmid) associated with the Ardora instance.
    $moduleid = $DB->get_field('modules', 'id', ['name' => 'ardora']);
    $cmid = $DB->get_field(
        'course_modules',
        'id',
        [
            'instance' => $instanceid,
            'module' => $moduleid,
        ]
    );
    if (!$cmid) {
        throw new moodle_exception('cmidnotfound', 'mod_ardora');
    }

    // Verify the user's capability.
    $context = context_module::instance($cmid);
    $isstudent = has_capability('mod/ardora:student', $context, $userid);
    if ($isstudent) {
        // Check for existing jobs with the same parameters.
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
            // Update the existing job.
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
            // Insert a new job.
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
        $activityvalues = [];

        if ($typegrade == "points") {
            foreach ($ardorajobs as $job) {
                $key = $job->courseid . '-' . $job->userid . '-' . $job->paq_name . '-' . $job->ardora_id . '-' . $job->activity;
                $points = is_numeric($job->points) ? (int)$job->points : 0;
                if (!isset($activityvalues[$key]) || $points > $activityvalues[$key]->points) {
                    $activityvalues[$key] = $job;
                    $activityvalues[$key]->points = $points;
                }
            }
            $newgrade = array_sum(array_column($activityvalues, 'points'));
        }

        if ($typegrade == "acts") {
            foreach ($ardorajobs as $job) {
                $key = $job->courseid . '-' . $job->userid . '-' . $job->paq_name . '-' . $job->ardora_id . '-' . $job->activity;
                if (!isset($activityvalues[$key])) {
                    if ($job->state === 'ok') {
                        $activityvalues[$key] = $job;
                    }
                } else if ($job->state === 'ok') {
                    $activityvalues[$key] = $job;
                }
            }
            $newgrade = count($activityvalues);
        }
        require_once($CFG->libdir . '/gradelib.php');
        $gradedata = new stdClass();
        $gradedata->userid = $userid;
        $gradedata->rawgrade = $newgrade;
        $result = grade_update('mod/ardora', $courseid, 'mod', 'ardora', $instanceid, 0, $gradedata);
    }
}

/**
 * Retrieves the user's Ardora jobs.
 *
 * This function retrieves job records related to a specific Ardora activity
 * for the current user, based on the provided parameters.
 *
 * @param int $type The type of job.
 * @param int $father The ID of the parent element.
 * @param string $paqname The name of the package associated with the jobs.
 * @param int $ardoraid The ID of the Ardora instance.
 * @return array An array of job records, or an empty array if no jobs are found.
 * @throws moodle_exception If the course ID cannot be determined.
 */
function get_user_ardora_jobs($type, $father, $paqname, $ardoraid) {
    global $DB, $USER, $COURSE, $_SESSION;
    require_login();
    // Get the current user and course ID from the cache.
    $userid = $USER->id;
    // Note: $courseid = $COURSE->id; On pop-ups this does not actually return the course you are on.
    $cache = \cache::make('mod_ardora', 'courseid_cache');
    $courseid = $cache->get('current_courseid');
    if (!$courseid) {
            throw new moodle_exception('courseidnotfound', 'mod_ardora');
    }

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
 * based on the user's role and the provided parameters. Students see only their data,
 * while teachers, editors, and administrators see data for all students in the course.
 *
 * - Students (roleid = 5) will see only their own data.
 * - Teachers, editors, and administrators (roleids = 1, 2, 3, 4) will see data for all students.
 *
 * If the course ID cannot be determined, an exception is thrown.
 *
 * @param int $father The ID of the parent element.
 * @param int $type Unused parameter. Reserved for future use.
 * @param string $paqname The name of the package associated with the evaluation.
 * @param int $ardoraid The ID of the Ardora instance.
 * @return array An array containing evaluation data with keys:
 *               - `users`: Array of user information.
 *               - `ardora_jobs`: Array of Ardora job data.
 *               - `coursename`: Name of the course.
 *               - `usertype`: Role of the user ('student' or 'teacher').
 *               - `name`: Name of the Ardora instance.
 * @throws moodle_exception If the course ID cannot be determined.
 */
function get_user_ardora_eval($type, $father, $paqname, $ardoraid) {
    global $DB, $USER, $COURSE;
    require_once(__DIR__ . '/../../config.php');
    require_once(__DIR__ . '/../../user/lib.php');
    require_once(__DIR__ . '/../../lib/accesslib.php');
    require_login();
    $userid = $USER->id;
    // Note: $courseid = $COURSE->id; On pop-ups this does not actually return the course you are on.
    $cache = \cache::make('mod_ardora', 'courseid_cache');
    $courseid = $cache->get('current_courseid');
    if (!$courseid) {
        throw new moodle_exception('courseidnotfound', 'mod_ardora');
    }
    $record = $DB->get_record('ardora', ['ardora_id' => $ardoraid, 'course' => $courseid], '*', MUST_EXIST);
    $courserecord = $DB->get_record('course', ['id' => $courseid]);

    // Get the course module ID (cmid) associated with the Ardora instance.
    $cmid = $DB->get_field('course_modules', 'id', [
        'instance' => $record->id,
        'module' => $DB->get_field('modules', 'id', ['name' => 'ardora']),
    ]);
    if (!$cmid) {
        throw new moodle_exception('cmidnotfound', 'mod_ardora');
    }

    $context = context_module::instance($cmid);

    $roles = get_user_roles($context, $userid, true);
    $isstudent = false;
    foreach ($roles as $role) {
        if ($role->shortname === 'student') {
            $isstudent = true;
            break;
        }
    }

    if ($isstudent) { // Student, user data only.
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

    if (has_capability('mod/ardora:managejobs', $context)) {
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

        // If there are no userids, skip the query.
        if (empty($userids)) {
            // No records found, return or handle as appropriate.
            return [];
        }

        // Use Moodle's DML get_in_or_equal function for safe SQL query construction.
        list($sqlin, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid');

        // Combine courseid parameter with the user parameters.
        $params = array_merge(['courseid' => $courseid], $userparams);

        // Make a single query to obtain all user data.
        $sql = "SELECT u.id, u.firstname, u.lastname, g.name as groupname
                FROM {user} u
                INNER JOIN {groups_members} gm ON gm.userid = u.id
                INNER JOIN {groups} g ON g.id = gm.groupid AND g.courseid = :courseid
                WHERE u.id $sqlin";

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
 * Ardora activity based on their role and the provided parameters.
 *
 * - Students (roleid = 5) will see only their own data.
 * - Teachers, editors, and administrators (roleids = 1, 2, 3, 4) will see data for all students in the course.
 *
 * If the course ID cannot be determined, an exception is thrown.
 *
 * @param int $type Unused parameter. Reserved for future use.
 * @param int $ardoraid The ID of the Ardora instance.
 * @return array An array containing the following keys:
 *               - `users`: Array of user information.
 *               - `ardora_jobs`: Array of Ardora job data.
 *               - `coursename`: Name of the course.
 *               - `usertype`: Role of the user ('student' or 'teacher').
 * @throws moodle_exception If the course ID cannot be determined.
 */
function get_user_ardora_info($type, $ardoraid) {
    global $DB, $USER, $COURSE;
    require_once(__DIR__ . '/../../config.php');
    require_once(__DIR__ . '/../../user/lib.php');
    require_once(__DIR__ . '/../../lib/accesslib.php');

    $userid = $USER->id;
    // Note: $courseid = $COURSE->id; On pop-ups this does not actually return the course you are on.
    $cache = \cache::make('mod_ardora', 'courseid_cache');
    $courseid = $cache->get('current_courseid');
    if (!$courseid) {
        throw new moodle_exception('courseidnotfound', 'mod_ardora');
    }
    $ardorarecord = $DB->get_record('ardora', ['ardora_id' => $ardoraid, 'course' => $courseid], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('ardora', $ardorarecord->id, $courseid, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    // Get roles.
    $roles = get_user_roles($context, $userid);
    // Check if the user specifically has the student role.
    $isstudent = false;
    foreach ($roles as $role) {
        if ($role->shortname === 'student') {
            $isstudent = true;
            break;
        }
    }

    if ($isstudent) { // Student, user data only.
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

    if (has_capability('mod/ardora:managejobs', $context)) {
        // Teacher, editor, administrator: send the data of all students enrolled in the course.
        $sqlardorajobs = "SELECT * FROM {ardora_jobs} WHERE courseid = :courseid AND ardora_id = :ardora_id";

        $paramsardorajobs = [
            'courseid' => $courseid,
            'ardora_id' => $ardoraid,
        ];

        $ardorajobs = $DB->get_records_sql($sqlardorajobs, $paramsardorajobs);

        $userids = array_unique(array_map(function($job) {
            return $job->userid;
        }, $ardorajobs));

        if (empty($userids)) {
            return [];
        }

        list($sqlin, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid');

        $params = array_merge(['courseid' => $courseid], $userparams);

        $sql = "SELECT u.id, u.firstname, u.lastname, g.name as groupname
                FROM {user} u
                INNER JOIN {groups_members} gm ON gm.userid = u.id
                INNER JOIN {groups} g ON g.id = gm.groupid AND g.courseid = :courseid
                WHERE u.id $sqlin";

        $userdata = $DB->get_records_sql($sql, $params);

        $users = [];
        foreach ($userdata as $data) {
            if (isset($users[$data->id])) {
                $users[$data->id]['groups'] .= ', ' . $data->groupname;
            } else {
                $users[$data->id] = [
                    'id' => $data->id,
                    'firstname' => $data->firstname,
                    'lastname' => $data->lastname,
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
 * activity instance, but only if the current user has sufficient permissions
 * (roles: manager, course creator, editing teacher, or teacher).
 *
 * @param int $userid The ID of the user whose job is being deleted.
 * @param string $datajob The data associated with the job, typically a string identifier.
 * @param int $ardoraid The ID of the Ardora instance.
 * @throws dml_exception If there is a problem executing the database query.
 * @return void This function does not return a value.
 */
function del_user_ardora_job($userid, $datajob, $ardoraid) {
    global $DB, $USER;
    $actualuserid = $USER->id;
    $ardorarecord = $DB->get_record('ardora', ['ardora_id' => $ardoraid], 'id, course', MUST_EXIST);
    $cm = get_coursemodule_from_instance('ardora', $ardorarecord->id, $ardorarecord->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    if (has_capability('mod/ardora:managejobs', $context, $actualuserid)) {
        $select = "userid = :userid AND " .
            $DB->sql_compare_text('datajob') . " = :datajob AND " .
            $DB->sql_compare_text('ardora_id') . " = :ardora_id";
        $params = ['userid' => $userid, 'datajob' => $datajob, 'ardora_id' => $ardoraid];
        $DB->delete_records_select('ardora_jobs', $select, $params);
    } else {
        throw new moodle_exception('nopermissiontomanagejobs', 'mod_ardora');
    }
}

/**
 * Returns the features supported by the Ardora module.
 *
 * This function declares which features the Ardora module supports within Moodle.
 *
 * @param string $feature The name of the feature to check.
 * @return mixed True if the feature is supported, null if not.
 */
function mod_ardora_get_completion_active_rule_descriptions($cm) {
    return [get_string('completionrequiresgrade', 'mod_ardora')];
}

/**
 * Checks if the completion rule for requiring a passing grade is enabled.
 *
 * This function determines whether the completion rule related to a passing grade
 * is active for a given Ardora instance based on the provided module data.
 *
 * @param stdClass $data The data object for the module instance, containing configuration values.
 *                       Must include the `completiongrade` field.
 * @return bool True if the completion rule is enabled (completiongrade is set and greater than 0),
 *              otherwise false.
 */
function mod_ardora_completion_rule_enabled($data) {
    return !empty($data->completiongrade) && $data->completiongrade > 0;
}

/**
 * Determines the completion state for the Ardora activity.
 *
 * This function checks whether the user has met the completion criteria for the
 * Ardora activity. It evaluates factors such as passing grade, user grades, and
 * configured completion rules to determine the activity's completion state.
 *
 * @param object $course The course object containing the activity.
 * @param object $cm The course module object for the Ardora instance.
 * @param int $userid The ID of the user whose completion state is being checked.
 * @param bool $type Whether to return a boolean state or detailed report:
 *                   - `true` for a boolean state (default behavior).
 *                   - `false` for detailed information about the completion state.
 * @return bool|mixed
 *         - If `$type` is `true`, returns a boolean value (`true` if completed, `false` otherwise).
 *         - If `$type` is `false`, returns detailed information about the completion state.
 * @throws dml_exception If there is an issue querying the database for user grades or activity details.
 */
function mod_ardora_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Retrieve the grade item for this activity.
    $gradeitem = $DB->get_record(
        'grade_items',
        ['iteminstance' => $cm->instance, 'itemmodule' => 'ardora'],
        'id, gradepass',
        MUST_EXIST
    );
    $gradepass = isset($gradeitem->gradepass) ? (float) $gradeitem->gradepass : null;

    // Retrieve the user's grade for this activity.
    $usergrade = $DB->get_field('grade_grades', 'finalgrade', ['userid' => $userid, 'itemid' => $gradeitem->id]);

    // Check if the user grade meets the passing criteria, if applicable.
    $completionmet = true;
    if ($gradepass !== null) {
        $completionmet = ($usergrade !== null && $usergrade >= $gradepass);
    }

    // If the type is set to COMPLETION_AND, ensure completion is based on other rules as well.
    if ($type === COMPLETION_AND) {
        $completionrules = $DB->get_field('course_modules_completion', 'completion', ['coursemoduleid' => $cm->id]);
        if ($completionrules & COMPLETION_TRACKING_AUTOMATIC) {
            // Additional completion criteria can be added here if needed.
            $completionmet = $completionmet && !empty($usergrade);
        }
    }

    // Return detailed state if type is false, otherwise return the completion boolean.
    if (!$type) {
        return [
            'usergrade' => $usergrade,
            'gradepass' => $gradepass,
            'completionmet' => $completionmet,
        ];
    }

    return $completionmet;
}
/**
 * Sets a custom icon for the Ardora module in course views.
 *
 * This function modifies the course module information to set a custom icon
 * for the Ardora activity. It points to a custom icon located in the plugin's
 * pix/ directory (e.g., `pix/icon.png` or `pix/icon.svg`).
 *
 * @param cm_info $cm The course module object representing the Ardora instance.
 *                    This object is used to modify display properties for the module.
 *
 * @return void
 */
function mod_ardora_cm_info_view(cm_info $cm) {
    global $OUTPUT;

    // Set the custom icon URL for the Ardora module.
    $iconurl = new moodle_url('/mod/ardora/pix/icon.png');
    $cm->set_icon_url($iconurl);
}
