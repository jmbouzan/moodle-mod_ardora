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
 * Private ardora module utility functions
 * created from the "Resource module" version created by 2009 Petr Skoda  {@link http://skodak.org}
 * @package    mod_ardora
 * @copyright  2025 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/ardora/lib.php");

/**
 * Redirected to migrated ardora if needed,
 * return if incorrect parameters specified
 * @param int $oldid
 * @param int $cmid
 * @return void
 */
function ardora_redirect_if_migrated($oldid, $cmid) {
    global $DB, $CFG;

    if ($oldid) {
        $old = $DB->get_record('ardora_old', ['oldid' => $oldid]);
    } else {
        $old = $DB->get_record('ardora_old', ['cmid' => $cmid]);
    }

    if (!$old) {
        return;
    }

    redirect("$CFG->wwwroot/mod/$old->newmodule/view.php?id=".$old->cmid);
}

/**
 * Display embedded ardora file.
 * @param object $ardora
 * @param object $cm
 * @param object $course
 * @param stored_file $file main file
 * @return does not return
 */
function ardora_display_embed($ardora, $cm, $course, $file) {
    global $CFG, $PAGE, $OUTPUT;

    $clicktoopen = ardora_get_clicktoopen($file, $ardora->revision);

    $context = context_module::instance($cm->id);
    $moodleurl = moodle_url::make_pluginfile_url($context->id, 'mod_ardora', 'content', $ardora->revision,
            $file->get_filepath(), $file->get_filename());

    $mimetype = $file->get_mimetype();
    $title    = $ardora->name;

    $extension = resourcelib_get_extension($file->get_filename());

    $mediamanager = core_media_manager::instance($PAGE);
    $embedoptions = [
        core_media_manager::OPTION_TRUSTED => true,
        core_media_manager::OPTION_BLOCK => true,
    ];

    if (file_mimetype_in_typegroup($mimetype, 'web_image')) {  // It's an image.
        $code = resourcelib_embed_image($moodleurl->out(), $title);

    } else if ($mimetype === 'application/pdf') {
        // PDF document.
        $code = resourcelib_embed_pdf($moodleurl->out(), $title, $clicktoopen);

    } else if ($mediamanager->can_embed_url($moodleurl, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediamanager->embed_url($moodleurl, $title, 0, 0, $embedoptions);

    } else {
        // We need a way to discover if we are loading remote docs inside an iframe.
        $moodleurl->param('embed', 1);

        // Anything else - just try object tag enlarged as much as possible.
        $code = resourcelib_embed_general($moodleurl, $title, $clicktoopen, $mimetype);
    }

    ardora_print_header($ardora, $cm, $course);
    ardora_print_heading($ardora, $cm, $course);

    echo $code;

    ardora_print_intro($ardora, $cm, $course);

    echo $OUTPUT->footer();
    die;
}

/**
 * Display ardora frames.
 * @param object $ardora
 * @param object $cm
 * @param object $course
 * @param stored_file $file main file
 * @return does not return
 */
function ardora_display_frame($ardora, $cm, $course, $file) {
    global $PAGE, $OUTPUT, $CFG;

    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        ardora_print_header($ardora, $cm, $course);
        ardora_print_heading($ardora, $cm, $course);
        ardora_print_intro($ardora, $cm, $course);
        echo $OUTPUT->footer();
        die;

    } else {
        $config = get_config('ardora');
        $context = context_module::instance($cm->id);
        $path = '/'.$context->id.'/mod_ardora/content/'.$ardora->revision.$file->get_filepath().$file->get_filename();
        $fileurl = (new moodle_url('/pluginfile.php' . $path))->out(false);
        $navurl = "$CFG->wwwroot/mod/ardora/view.php?id=$cm->id&amp;frameset=top";
        $title = strip_tags(format_string($course->shortname.': '.$ardora->name));
        $framesize = $config->framesize;
        $contentframetitle = s(format_string($ardora->name));
        $modulename = s(get_string('modulename', 'ardora'));
        $dir = get_string('thisdirection', 'langconfig');

        $file = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navurl" title="$modulename" />
    <frame src="$fileurl" title="$contentframetitle" />
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $file;
        die;
    }
}

/**
 * Internal function - creates "click to open" text with a link.
 *
 * @param stored_file $file The file object representing the file to link to.
 * @param int $revision The revision number of the file.
 * @param string $extra Additional attributes for the HTML link tag.
 * @return string The HTML string with the "click to open" link.
 */
function ardora_get_clicktoopen($file, $revision, $extra='') {
    global $CFG;

    $filename = $file->get_filename();
    $path = '/'.$file->get_contextid().'/mod_ardora/content/'.$revision.$file->get_filepath().$file->get_filename();
    $fullurl = (new moodle_url('/pluginfile.php' . $path))->out(false);

    $string = get_string('clicktoopen2', 'ardora', "<a href=\"$fullurl\" $extra>$filename</a>");

    return $string;
}

/**
 * Internal function - creates "click to download" text with a link.
 *
 * @param stored_file $file The file object representing the file to link to.
 * @param int $revision The revision number of the file.
 * @return string The HTML string with the "click to download" link.
 */
function ardora_get_clicktodownload($file, $revision) {
    global $CFG;

    $filename = $file->get_filename();
    $path = '/'.$file->get_contextid().'/mod_ardora/content/'.$revision.$file->get_filepath().$file->get_filename();
    $fullurl = (new moodle_url('/pluginfile.php' . $path))->out(false);

    $string = get_string('clicktodownload', 'ardora', "<a href=\"$fullurl\">$filename</a>");

    return $string;
}

/**
 * Print ardora info and workaround link when JS not available.
 * @param object $ardora
 * @param object $cm
 * @param object $course
 * @param stored_file $file main file
 * @return does not return
 */
function ardora_print_workaround($ardora, $cm, $course, $file) {
    global $CFG, $OUTPUT;

    ardora_print_header($ardora, $cm, $course);
    ardora_print_heading($ardora, $cm, $course, true);
    ardora_print_intro($ardora, $cm, $course, true);

    $ardora->mainfile = $file->get_filename();
    echo '<div class="ardoraworkaround">';
    switch (ardora_get_final_display_type($ardora)) {
        case RESOURCELIB_DISPLAY_POPUP:
            $path = '/'.$file->get_contextid().'/mod_ardora/content/'.$ardora->revision.$file->get_filepath().$file->get_filename();
            $fullurl = (new moodle_url('/pluginfile.php' . $path))->out(false);
            $options = empty($ardora->displayoptions) ? [] : json_decode($ardora->displayoptions, true);
            $width  = empty($options['popupwidth']) ? 620 : $options['popupwidth'];
            $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
            $wh = "width=$width,height=$height," .
            "toolbar=no,location=no,menubar=no," .
            "copyhistory=no,status=no,directories=no," .
            "scrollbars=yes,resizable=yes";
            $extra = "onclick=\"window.open('$fullurl', '', '$wh'); return false;\"";
            echo ardora_get_clicktoopen($file, $ardora->revision, $extra);
            break;

        case RESOURCELIB_DISPLAY_NEW:
            $extra = 'onclick="this.target=\'_blank\'"';
            echo ardora_get_clicktoopen($file, $ardora->revision, $extra);
            break;

        case RESOURCELIB_DISPLAY_DOWNLOAD:
            echo ardora_get_clicktodownload($file, $ardora->revision);
            break;

        case RESOURCELIB_DISPLAY_OPEN:
        default:
            echo ardora_get_clicktoopen($file, $ardora->revision);
            break;
    }
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Print ardora header.
 * @param object $ardora
 * @param object $cm
 * @param object $course
 * @return void
 */
function ardora_print_header($ardora, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$ardora->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($ardora);
    echo $OUTPUT->header();
}

/**
 * Print ardora heading.
 * @param object $ardora
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used
 * @return void
 */
function ardora_print_heading($ardora, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($ardora->name), 2);
}


/**
 * Gets details of the file to cache in course cache to be displayed using {@link ardora_get_optional_details()}
 *
 * @param object $ardora ardora table row (only property 'displayoptions' is used here)
 * @param object $cm Course-module table row
 * @return string Size and type or empty string if show options are not enabled
 */
function ardora_get_file_details($ardora, $cm) {
    $options = empty($ardora->displayoptions) ? [] : json_decode($ardora->displayoptions, true);
    if (!is_array($options)) {
        $options = [];
    }
    $filedetails = [];
    if (!empty($options['showsize']) || !empty($options['showtype']) || !empty($options['showdate'])) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_ardora', 'content', 0, 'sortorder DESC, id ASC', false);
        // For a typical file ardora, the sortorder is 1 for the main file
        // and 0 for all other files. This sort approach is used just in case
        // there are situations where the file has a different sort order.
        $mainfile = $files ? reset($files) : null;
        if (!empty($options['showsize'])) {
            $filedetails['size'] = 0;
            foreach ($files as $file) {
                // This will also synchronize the file size for external files if needed.
                $filedetails['size'] += $file->get_filesize();
                if ($file->get_repository_id()) {
                    // If file is a reference the 'size' attribute can not be cached.
                    $filedetails['isref'] = true;
                }
            }
        }
        if (!empty($options['showtype'])) {
            if ($mainfile) {
                $filedetails['type'] = get_mimetype_description($mainfile);
                $filedetails['mimetype'] = $mainfile->get_mimetype();
                // Only show type if it is not unknown.
                if ($filedetails['type'] === get_mimetype_description('document/unknown')) {
                    $filedetails['type'] = '';
                }
            } else {
                $filedetails['type'] = '';
            }
        }
        if (!empty($options['showdate'])) {
            if ($mainfile) {
                // Modified date may be up to several minutes later than uploaded date just because
                // teacher did not submit the form promptly. Give teacher up to 5 minutes to do it.
                if ($mainfile->get_timemodified() > $mainfile->get_timecreated() + 5 * MINSECS) {
                    $filedetails['modifieddate'] = $mainfile->get_timemodified();
                } else {
                    $filedetails['uploadeddate'] = $mainfile->get_timecreated();
                }
                if ($mainfile->get_repository_id()) {
                    // If main file is a reference the 'date' attribute can not be cached.
                    $filedetails['isref'] = true;
                }
            } else {
                $filedetails['uploadeddate'] = '';
            }
        }
    }
    return $filedetails;
}

/**
 * Gets optional details for a ardora, depending on ardora settings.
 *
 * Result may include the file size and type if those settings are chosen,
 * or blank if none.
 *
 * @param object $ardora ardora table row (only property 'displayoptions' is used here)
 * @param object $cm Course-module table row
 * @return string Size and type or empty string if show options are not enabled
 */
function ardora_get_optional_details($ardora, $cm) {
    global $DB;

    $details = '';

    $options = empty($ardora->displayoptions) ? [] : json_decode($ardora->displayoptions, true);
    if (!is_array($options)) {
        $options = [];
    }
    if (!empty($options['showsize']) || !empty($options['showtype']) || !empty($options['showdate'])) {
        if (!array_key_exists('filedetails', $options)) {
            $filedetails = ardora_get_file_details($ardora, $cm);
        } else {
            $filedetails = $options['filedetails'];
        }
        $size = '';
        $type = '';
        $date = '';
        $langstring = '';
        $infodisplayed = 0;
        if (!empty($options['showsize'])) {
            if (!empty($filedetails['size'])) {
                $size = display_size($filedetails['size']);
                $langstring .= 'size';
                $infodisplayed += 1;
            }
        }
        if (!empty($options['showtype'])) {
            if (!empty($filedetails['type'])) {
                $type = $filedetails['type'];
                $langstring .= 'type';
                $infodisplayed += 1;
            }
        }
        if (!empty($options['showdate']) && (!empty($filedetails['modifieddate']) || !empty($filedetails['uploadeddate']))) {
            if (!empty($filedetails['modifieddate'])) {
                $date = get_string('modifieddate', 'mod_ardora', userdate($filedetails['modifieddate'],
                    get_string('strftimedatetimeshort', 'langconfig')));
            } else if (!empty($filedetails['uploadeddate'])) {
                $date = get_string('uploadeddate', 'mod_ardora', userdate($filedetails['uploadeddate'],
                    get_string('strftimedatetimeshort', 'langconfig')));
            }
            $langstring .= 'date';
            $infodisplayed += 1;
        }

        if ($infodisplayed > 1) {
            $details = get_string("ardoradetails_{$langstring}", 'ardora',
                    (object)['size' => $size, 'type' => $type, 'date' => $date]);
        } else {
            // Only one of size, type and date is set, so just append.
            $details = $size . $type . $date;
        }
    }

    return $details;
}

/**
 * Print ardora introduction.
 * @param object $ardora
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function ardora_print_intro($ardora, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($ardora->displayoptions) ? [] : json_decode($ardora->displayoptions, true);

    $extraintro = ardora_get_optional_details($ardora, $cm);
    if ($extraintro) {
        // Put a paragaph tag around the details.
        $extraintro = html_writer::tag('p', $extraintro, ['class' => 'ardoradetails']);
    }

    if ($ignoresettings || !empty($options['printintro']) || $extraintro) {
        $gotintro = trim(strip_tags($ardora->intro));
        if ($gotintro || $extraintro) {
            echo $OUTPUT->box_start('mod_introbox', 'ardoraintro');
            if ($gotintro) {
                echo format_module_intro('ardora', $ardora, $cm->id);
            }
            echo $extraintro;
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Print warning that instance not migrated yet.
 * @param object $ardora
 * @param object $cm
 * @param object $course
 * @return void, does not return
 */
function ardora_print_tobemigrated($ardora, $cm, $course) {
    global $DB, $OUTPUT;

    $ardoraold = $DB->get_record('ardora_old', ['oldid' => $ardora->id]);
    ardora_print_header($ardora, $cm, $course);
    ardora_print_heading($ardora, $cm, $course);
    ardora_print_intro($ardora, $cm, $course);
    echo $OUTPUT->notification(get_string('notmigrated', 'ardora', $ardoraold->type));
    echo $OUTPUT->footer();
    die;
}

/**
 * Print warning that file can not be found.
 * @param object $ardora
 * @param object $cm
 * @param object $course
 * @return void, does not return
 */
function ardora_print_filenotfound($ardora, $cm, $course) {
    global $DB, $OUTPUT;

    $ardoraold = $DB->get_record('ardora_old', ['oldid' => $ardora->id]);
    ardora_print_header($ardora, $cm, $course);
    ardora_print_heading($ardora, $cm, $course);
    ardora_print_intro($ardora, $cm, $course);
    if ($ardoraold) {
        echo $OUTPUT->notification(get_string('notmigrated', 'ardora', $ardoraold->type));
    } else {
        echo $OUTPUT->notification(get_string('filenotfound', 'ardora'));
    }
    echo $OUTPUT->footer();
    die;
}

/**
 * Decide the best display format.
 * @param object $ardora
 * @return int display type constant
 */
function ardora_get_final_display_type($ardora) {
    global $CFG, $PAGE;

    if ($ardora->display != RESOURCELIB_DISPLAY_AUTO) {
        return $ardora->display;
    }

    if (empty($ardora->mainfile)) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    } else {
        $mimetype = mimeinfo('type', $ardora->mainfile);
    }

    if (file_mimetype_in_typegroup($mimetype, 'archive')) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    }
    if (file_mimetype_in_typegroup($mimetype, ['web_image', '.htm', 'web_video', 'web_audio'])) {
        return RESOURCELIB_DISPLAY_EMBED;
    }

    // Let the browser deal with it somehow.
    return RESOURCELIB_DISPLAY_OPEN;
}

/**
 * File browsing support class
 */
class ardora_content_file_info extends file_info_stored {
    /**
     * Retrieves the parent element or object.
     *
     * This function returns the parent object or element associated with the current instance.
     *
     * @return mixed The parent object or element, or null if not applicable.
     */
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' && $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    /**
     * Retrieves the visible name of the item.
     *
     * This function returns the human-readable name of the item associated with
     * the current instance, which can be displayed in the user interface.
     *
     * @return string The visible name of the item.
     */
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' && $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}
/**
 * Sets the main file for the Ardora activity.
 *
 * This function sets or updates the main file associated with the Ardora activity
 * based on the provided data.
 *
 * @param stdClass $data Data object containing the information to identify and set the main file.
 * @return bool True if the main file was set successfully, false otherwise.
 */
function ardora_set_mainfile($data) {
    global $DB;
    $fs = get_file_storage();
    $cmid = $data->coursemodule;
    $draftitemid = $data->files;

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $options = ['subdirs' => true, 'embed' => false];
        if ($data->display == RESOURCELIB_DISPLAY_EMBED) {
            $options['embed'] = true;
        }
        file_save_draft_area_files($draftitemid, $context->id, 'mod_ardora', 'content', 0, $options);
    }
    $files = $fs->get_area_files($context->id, 'mod_ardora', 'content', 0, 'sortorder', false);
    if (count($files) == 1) {
        // Only one file attached, set it as main file automatically.
        $file = reset($files);
        file_set_sortorder($context->id, 'mod_ardora', 'content', 0, $file->get_filepath(), $file->get_filename(), 1);
    }
}
