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

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/ardora/locallib.php');
require_once($CFG->libdir . '/filelib.php');

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
     */
    public function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        $config = get_config('ardora');

        if ($this->current->instance && $this->current->tobemigrated) {
            // Activity not migrated yet.
            $ardoraold = $DB->get_record('ardora_old', ['oldid' => $this->current->instance]);
            $mform->addElement('static', 'warning', '', get_string('notmigrated', 'ardora', $ardoraold->type));
            $mform->addElement('cancel');
            $this->standard_hidden_coursemodule_elements();
            return;
        }

        // -------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '48']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $filemanageroptions = [
            'accepted_types' => '*',
            'maxbytes' => 0,
            'maxfiles' => -1,
            'mainfile' => true,
        ];
        $mform->addElement('filemanager', 'files', get_string('selectfiles'), null, $filemanageroptions);

        // Add legacy files flag only if used.
        if (isset($this->current->legacyfiles) && $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
            $options = [
                RESOURCELIB_LEGACYFILES_DONE   => get_string('legacyfilesdone', 'ardora'),
                RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'ardora'),
            ];
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'ardora'), $options);
        }

        // -------------------------------------------------------
        $mform->addElement('header', 'optionssection', get_string('appearance'));

        $options = $this->current->instance
            ? resourcelib_get_displayoptions(explode(',', $config->displayoptions), $this->current->display)
            : resourcelib_get_displayoptions(explode(',', $config->displayoptions));

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

        $config = get_config('ardora');
        // Popup options if RESOURCELIB_DISPLAY_POPUP is available.
        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            // Usa los valores configurados en el plugin como valores predeterminados.
            $defaultpopupwidth = !empty($config->popupwidth) ? $config->popupwidth : 620; // 620 es un valor fallback.
            $defaultpopupheight = !empty($config->popupheight) ? $config->popupheight : 450; // 450 es un valor fallback.
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'ardora'), ['size' => 3]);
            $mform->setType('popupwidth', PARAM_INT);
            $defaultpopupwidth = !empty($config->popupwidth) ? $config->popupwidth : 620;
            $mform->setDefault('popupwidth', $defaultpopupwidth);
            $mform->hideIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            $mform->addElement('text', 'popupheight', get_string('popupheight', 'ardora'), ['size' => 3]);
            $mform->setType('popupheight', PARAM_INT);
            $defaultpopupheight = !empty($config->popupheight) ? $config->popupheight : 450;
            $mform->setDefault('popupheight', $defaultpopupheight);
            $mform->hideIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
        }

        // Grading options.
        $mform->addElement('header', 'gradingsection', get_string('gradingoptions', 'mod_ardora'));

        // Passing grade (Grade to pass).
        $mform->addElement('text', 'gradepass', get_string('gradepass', 'grades'), ['size' => 5]);
        $mform->setType('gradepass', PARAM_FLOAT);
        $mform->setDefault('gradepass', 0);
        $mform->addHelpButton('gradepass', 'gradepass', 'grades');

        // -------------------------------------------------------
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();

        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }

    /**
     * Preprocesses the form data before it is displayed.
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance && !$this->current->tobemigrated) {
            $draftitemid = file_get_submitted_draft_itemid('files');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_ardora', 'content', 0, ['subdirs' => true]);
            $defaultvalues['files'] = $draftitemid;
        }
    }

    /**
     * Validates the form data.
     */
    public function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
