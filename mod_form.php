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
 * ardora configuration form
 * created from the "Resource module" version created by 2009 Petr Skoda  {@link http://skodak.org}
 * @package    mod_ardora
 * @copyright  2024 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/ardora/locallib.php');
require_once($CFG->libdir.'/filelib.php');
/**
 * Form for configuring the Ardora module settings.
 *
 * This class extends the moodleform_mod class and is used to define the settings form
 * for the Ardora module in the Moodle course setup.
 *
 * @package    mod_ardora
 * @category   form
 */
class mod_ardora_mod_form extends moodleform_mod {
    /**
     * Defines the settings form for the Ardora module.
     *
     * This function is used to define the elements in the settings form for
     * the Ardora module in the Moodle course setup.
     */
    public function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        $config = get_config('ardora');

        if ($this->current->instance && $this->current->tobemigrated) {
            // Ardora not migrated yet.
            $ardoraold = $DB->get_record('ardora_old', ['oldid' => $this->current->instance]);
            $mform->addElement('static', 'warning', '', get_string('notmigrated', 'ardora', $ardoraold->type));
            $mform->addElement('cancel');
            $this->standard_hidden_coursemodule_elements();
            return;
        }

        // -------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '48']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);
        $filemanageroptions = [];
        $filemanageroptions['accepted_types'] = '*';
        $filemanageroptions['maxbytes'] = 0;
        $filemanageroptions['maxfiles'] = -1;
        $filemanageroptions['mainfile'] = true;

        $mform->addElement('filemanager', 'files', get_string('selectfiles'), null, $filemanageroptions);

        // Add legacy files flag only if used.
        if (isset($this->current->legacyfiles) && $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
            $options = [RESOURCELIB_LEGACYFILES_DONE   => get_string('legacyfilesdone', 'ardora'),
            RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'ardora')];
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'ardora'), $options);
        }

        // -------------------------------------------------------
        $mform->addElement('header', 'optionssection', get_string('appearance'));

        if ($this->current->instance) {
            $options = RESOURCELIB_get_displayoptions(explode(',', $config->displayoptions), $this->current->display);
        } else {
            $options = RESOURCELIB_get_displayoptions(explode(',', $config->displayoptions));
        }

        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'ardora'), $options);
            $mform->setDefault('display', $config->display);
            $mform->addHelpButton('display', 'displayselect', 'ardora');
        }

        $mform->addElement('checkbox', 'showsize', get_string('showsize', 'ardora'));
        $mform->setDefault('showsize', $config->showsize);
        $mform->addHelpButton('showsize', 'showsize', 'ardora');
        $mform->addElement('checkbox', 'showtype', get_string('showtype', 'ardora'));
        $mform->setDefault('showtype', $config->showtype);
        $mform->addHelpButton('showtype', 'showtype', 'ardora');
        $mform->addElement('checkbox', 'showdate', get_string('showdate', 'ardora'));
        $mform->setDefault('showdate', $config->showdate);
        $mform->addHelpButton('showdate', 'showdate', 'ardora');

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'ardora'), ['size' => 3]);
            if (count($options) > 1) {
                $mform->hideIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);
            $mform->setAdvanced('popupwidth', true);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'ardora'), ['size' => 3]);
            if (count($options) > 1) {
                $mform->hideIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
            $mform->setAdvanced('popupheight', true);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_EMBED, $options)) {
            $mform->addElement('text', 'embedwidth', get_string('embedwidth', 'ardora'), ['size' => 3]);
            if (count($options) > 1) {
                $mform->hideIf('embedwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_EMBED);
            }
            $mform->setType('embedwidth', PARAM_INT);
            $mform->setDefault('embedwidth', $config->embedwidth);
            $mform->setAdvanced('embedwidth', true);

            $mform->addElement('text', 'embedheight', get_string('embedheight', 'ardora'), ['size' => 3]);
            if (count($options) > 1) {
                $mform->hideIf('embedheight', 'display', 'noteq', RESOURCELIB_DISPLAY_EMBED);
            }
            $mform->setType('embedheight', PARAM_INT);
            $mform->setDefault('embedheight', $config->embedheight);
            $mform->setAdvanced('embedheight', true);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_AUTO, $options) || array_key_exists(RESOURCELIB_DISPLAY_FRAME, $options)) {
            $mform->addElement('checkbox', 'printintro', get_string('printintro', 'ardora'));
            $mform->hideIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_POPUP);
            $mform->hideIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_DOWNLOAD);
            $mform->hideIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_OPEN);
            $mform->hideIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_NEW);
            $mform->setDefault('printintro', $config->printintro);
        }

        $options = ['0' => get_string('none'), '1' => get_string('allfiles'), '2' => get_string('htmlfilesonly')];
        $mform->addElement('select', 'filterfiles', get_string('filterfiles', 'ardora'), $options);
        $mform->setDefault('filterfiles', $config->filterfiles);
        $mform->setAdvanced('filterfiles', true);

        // -------------------------------------------------------
        $this->standard_coursemodule_elements();

        // -------------------------------------------------------
        $this->add_action_buttons();

        // -------------------------------------------------------
        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }
    /**
     * Preprocesses the form data before it is displayed.
     *
     * This function is used to preprocess the default values for the form
     * before they are displayed to the user.
     *
     * @param array $defaultvalues The array of default values to preprocess.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance && !$this->current->tobemigrated) {
            $draftitemid = file_get_submitted_draft_itemid('files');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_ardora', 'content', 0, ['subdirs' => true]);
            $defaultvalues['files'] = $draftitemid;
        }
        if (!empty($defaultvalues['displayoptions'])) {
            $displayoptions = unserialize($defaultvalues['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaultvalues['printintro'] = $displayoptions['printintro'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $defaultvalues['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $defaultvalues['popupheight'] = $displayoptions['popupheight'];
            }
            if (!empty($displayoptions['showsize'])) {
                $defaultvalues['showsize'] = $displayoptions['showsize'];
            } else {
                // Must set explicitly to 0 here otherwise it will use system
                // default which may be 1.
                $defaultvalues['showsize'] = 0;
            }
            if (!empty($displayoptions['showtype'])) {
                $defaultvalues['showtype'] = $displayoptions['showtype'];
            } else {
                $defaultvalues['showtype'] = 0;
            }
            if (!empty($displayoptions['showdate'])) {
                $defaultvalues['showdate'] = $displayoptions['showdate'];
            } else {
                $defaultvalues['showdate'] = 0;
            }
        }
    }
    /**
     * Performs actions after the form data has been set.
     *
     * This function is used to perform additional processing or modifications
     * to the form after the data has been set.
     *
     * @return void
     */
    public function definition_after_data() {
        if ($this->current->instance && $this->current->tobemigrated) {
            // Ardora not migrated yet.
            return;
        }

        parent::definition_after_data();
    }
    /**
     * Validates the form data.
     *
     * This function is used to validate the data submitted through the form.
     * It checks for any errors or inconsistencies in the data and returns an array
     * of error messages if validation fails.
     *
     * @param array $data The data to be validated.
     * @param array $files The files submitted with the form.
     * @return array An array of error messages, or an empty array if validation passes.
     */
    public function validation($data, $files) {
        global $USER;

        $errors = parent::validation($data, $files);

        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        if (!$files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['files'], 'sortorder, id', false)) {
            $errors['files'] = get_string('required');
            return $errors;
        }
        if (count($files) == 1) {
            // No need to select main file if only one picked.
            return $errors;
        } else if (count($files) > 1) {
            $mainfile = false;
            foreach ($files as $file) {
                if ($file->get_sortorder() == 1) {
                    $mainfile = true;
                    break;
                }
            }
            // Set a default main file.
            if (!$mainfile) {
                $file = reset($files);
                file_set_sortorder(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename(),
                    1
                );
            }
        }
        return $errors;
    }
}
