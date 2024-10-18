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
 * Cognitive depth indicator - ardora.
 * created from the "Resource module" version created by 2017 David Monllao {@link http://www.davidmonllao.com}
 * @package   mod_ardora
 * @copyright 2023 José Manuel Bouzán Matanza
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ardora\analytics\indicator;

/**
 * Cognitive Depth indicator for activities.
 *
 * This class extends the `activity_base` class and represents the cognitive depth indicator
 * in the context of Community of Inquiry activities. It calculates and provides analytics
 * related to the depth of cognitive engagement in activities.
 *
 * @package    local_ardora
 * @category   analytics
 */
class cognitive_depth extends activity_base {

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name(): \lang_string {
        return new \lang_string('indicator:cognitivedepth', 'mod_ardora');
    }
    /**
     * Gets the indicator type.
     *
     * This function returns the type of indicator that is represented by this class.
     *
     * @return string The indicator type.
     */
    public function get_indicator_type() {
        return self::INDICATOR_COGNITIVE;
    }
    /**
     * Gets cognitive depth level.
     *
     * This function returns the cognitive level.
     *
     * @return string The indicator type.
     */
    public function get_cognitive_depth_level(\cm_info $cm) {
        return self::COGNITIVE_LEVEL_1;
    }
}
