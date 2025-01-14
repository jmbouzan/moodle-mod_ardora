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
 * Social breadth indicator - ardora.
 * created from the "Resource module" version created by 2017 David Monllao {@link http://www.davidmonllao.com}
 * @package   mod_ardora
 * @copyright 2025 José Manuel Bouzán Matanza
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ardora\analytics\indicator;

/**
 * Class representing the social breadth indicator in the context of activities.
 *
 * This class extends the activity_base class and is used to calculate and analyze
 * the social breadth in a Community of Inquiry activity.
 *
 * @package    local_yourpluginname
 * @category   analytics
 */
class social_breadth extends activity_base {

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name(): \lang_string {
        return new \lang_string('indicator:socialbreadth', 'mod_ardora');
    }
    /**
     * Returns the type of indicator.
     *
     * This function returns the type of indicator that this class represents.
     *
     * @return string The indicator type.
     */
    public function get_indicator_type() {
        return self::INDICATOR_SOCIAL;
    }
    /**
     * Returns the type of social breadth.
     *
     * This function returns the type of social breadth.
     *
     * @param \cm_info $cm The course module information.
     * @return string The cognitive depth level.
     */
    public function get_social_breadth_level(\cm_info $cm) {
        return self::SOCIAL_LEVEL_1;
    }
}
