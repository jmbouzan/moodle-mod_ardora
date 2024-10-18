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
 * ardora module admin settings and defaults
 * created from the "Resource module" version created by 2009 Petr Skoda  {@link http://skodak.org}
 * @package    mod_ardora
 * @copyright  2024 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = RESOURCELIB_get_displayoptions([RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ]);
    $defaultdisplayoptions = [RESOURCELIB_DISPLAY_POPUP,
                                   RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_NEW,
                                   RESOURCELIB_DISPLAY_OPEN,
                                                        ];

    $settings->add(new admin_setting_configtext('ardora/embedwidth',
    get_string('embedwidth', 'ardora'), get_string('embedwidthexplain', 'ardora'), 1010, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('ardora/embedheight',
    get_string('embedheight', 'ardora'), get_string('embedheightexplain', 'ardora'), 650, PARAM_INT, 7));



    $settings->add(new admin_setting_configmultiselect('ardora/displayoptions',
        get_string('displayoptions', 'ardora'), get_string('configdisplayoptions', 'ardora'),
        $defaultdisplayoptions, $displayoptions));

    // Modedit defaults.

    $settings->add(new admin_setting_heading('ardoramodeditdefaults',
        get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));
    $settings->add(new admin_setting_configcheckbox('ardora/printintro',
        get_string('printintro', 'ardora'), get_string('printintroexplain', 'ardora'), 1));
    $settings->add(new admin_setting_configselect('ardora/display',
        get_string('displayselect', 'ardora'), get_string('displayselectexplain', 'ardora'), RESOURCELIB_DISPLAY_POPUP,
        $displayoptions));
    $settings->add(new admin_setting_configcheckbox('ardora/showsize',
        get_string('showsize', 'ardora'), get_string('showsize_desc', 'ardora'), 0));
    $settings->add(new admin_setting_configcheckbox('ardora/showtype',
        get_string('showtype', 'ardora'), get_string('showtype_desc', 'ardora'), 0));
    $settings->add(new admin_setting_configcheckbox('ardora/showdate',
        get_string('showdate', 'ardora'), get_string('showdate_desc', 'ardora'), 0));
    $settings->add(new admin_setting_configtext('ardora/popupwidth',
        get_string('popupwidth', 'ardora'), get_string('popupwidthexplain', 'ardora'), 1010, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('ardora/popupheight',
        get_string('popupheight', 'ardora'), get_string('popupheightexplain', 'ardora'), 650, PARAM_INT, 7));

    $options = ['0' => get_string('none'), '1' => get_string('allfiles'), '2' => get_string('htmlfilesonly')];
    $settings->add(new admin_setting_configselect('ardora/filterfiles',
        get_string('filterfiles', 'ardora'), get_string('filterfilesexplain', 'ardora'), 0, $options));
}
